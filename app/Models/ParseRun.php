<?php

namespace App\Models;

use App\Enums\ParseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParseRun extends Model
{
    protected $fillable = [
        'organization_id',
        'status',
        'pages_total',
        'pages_done',
        'reviews_fetched',
        'attempts',
        'error_code',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ParseStatus::class,
        'pages_total' => 'integer',
        'pages_done' => 'integer',
        'reviews_fetched' => 'integer',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function markRunning(int $attempt): void
    {
        $this->update([
            'status' => ParseStatus::Running,
            'attempts' => $attempt,
            'started_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function updateProgress(int $pagesDone, ?int $pagesTotal, int $reviewsFetched): void
    {
        $this->update([
            'pages_done' => $pagesDone,
            'pages_total' => $pagesTotal,
            'reviews_fetched' => $reviewsFetched,
        ]);
    }

    public function markCompleted(): void
    {
        $this->finishWith(ParseStatus::Completed);
    }

    public function markPartial(string $code, string $message): void
    {
        $this->finishWith(ParseStatus::Partial, $code, $message);
    }

    public function markFailed(string $code, string $message, bool $final): void
    {
        $this->finishWith($final ? ParseStatus::Failed : ParseStatus::Pending, $code, $message, $final);
    }

    public function progressPercent(): int
    {
        if ($this->status === ParseStatus::Completed) {
            return 100;
        }

        if (! $this->pages_total) {
            return 0;
        }

        $percent = (int) floor($this->pages_done / $this->pages_total * 100);

        return min($this->status === ParseStatus::Partial ? 100 : 99, $percent);
    }

    private function finishWith(ParseStatus $status, ?string $code = null, ?string $message = null, bool $finished = true): void
    {
        $this->update([
            'status' => $status,
            'error_code' => $code,
            'error_message' => $message === null ? null : mb_substr($message, 0, 2000),
            'finished_at' => $finished ? now() : null,
        ]);
    }
}
