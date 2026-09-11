<?php

namespace App\Jobs;

use App\Models\ParseRun;
use App\Services\ReviewSyncService;
use App\Services\YandexMaps\Exceptions\ParserException;
use App\Services\YandexMaps\YandexMapsParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ParseOrganizationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $uniqueFor = 900;

    public function __construct(public readonly ParseRun $run)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->run->organization_id;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(YandexMapsParser $parser, ReviewSyncService $sync): void
    {
        $this->run->markRunning($this->attempts());
        $organization = $this->run->organization;
        $seenAt = now();
        $sync->begin($organization);

        try {
            $result = $parser->parse(
                $organization->yandex_id,
                function (int $done, int $total, int $fetched, array $pageReviews) use ($sync, $organization, $seenAt) {
                    $sync->storeReviews($organization, $pageReviews, $seenAt);
                    $this->run->updateProgress($done, $total, $fetched);
                },
            );
        } catch (ParserException $e) {
            $this->handleFailure($e->errorCode(), $e->getMessage(), $e->isRetryable());

            return;
        } catch (Throwable $e) {
            $this->handleFailure('unexpected', $e->getMessage(), true);

            throw $e;
        }

        $sync->finish($organization, $this->run, $result->organization, $seenAt);
        $this->run->markCompleted();
    }

    public function failed(Throwable $e): void
    {
        $this->run->refresh();

        if ($this->run->finished_at === null) {
            $this->run->markFailed('unexpected', $e->getMessage(), true);
        }
    }

    private function handleFailure(string $code, string $message, bool $retryable): void
    {
        $canRetry = $retryable && $this->attempts() < $this->tries;
        $this->run->markFailed($code, $message, ! $canRetry);

        if ($canRetry) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);

            return;
        }

        $this->fail(new \RuntimeException("[{$code}] {$message}"));
    }
}
