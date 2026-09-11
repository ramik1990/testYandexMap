<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSnapshot;
use App\Models\ParseRun;
use App\Services\YandexMaps\Data\OrganizationInfo;
use App\Services\YandexMaps\Data\ReviewData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReviewSyncService
{
    private array $changes = [];

    private Collection $knownHashes;

    private array $before = [];

    public function begin(Organization $organization): self
    {
        $this->changes = ['added' => 0, 'updated' => 0, 'unchanged' => 0, 'missing' => 0];
        $this->knownHashes = $organization->reviews()->pluck('content_hash', 'external_id');
        $this->before = $organization->only(['rating', 'rating_count', 'review_count']);

        return $this;
    }

    public function storeReviews(Organization $organization, array $reviews, \DateTimeInterface $seenAt): void
    {
        DB::transaction(function () use ($organization, $reviews, $seenAt) {
            foreach ($reviews as $review) {
                $hash = $review->contentHash();
                $known = $this->knownHashes->get($review->id);

                $organization->reviews()->updateOrCreate(['external_id' => $review->id], $this->attributes($review, $hash, $seenAt));

                $this->changes[$known === null ? 'added' : ($known === $hash ? 'unchanged' : 'updated')]++;
            }
        });
    }

    public function finish(Organization $organization, ParseRun $run, OrganizationInfo $info, \DateTimeInterface $seenAt): OrganizationSnapshot
    {
        return DB::transaction(function () use ($organization, $run, $info, $seenAt) {
            $this->changes['missing'] = $organization->reviews()->where('last_seen_at', '<', $seenAt)->count();

            $organization->fill([
                'title' => $info->title ?: $organization->title,
                'address' => $info->address,
                'rating' => $info->rating,
                'rating_count' => $info->ratingCount,
                'review_count' => $info->reviewCount,
                'last_parsed_at' => $seenAt,
            ])->save();

            return $organization->snapshots()->create([
                'parse_run_id' => $run->id,
                'rating' => $organization->rating,
                'rating_count' => $organization->rating_count,
                'review_count' => $organization->review_count,
                'reviews_stored' => $organization->reviews()->count(),
                'changes' => $this->changes + ['before' => $this->before],
            ]);
        });
    }

    private function attributes(ReviewData $review, string $hash, \DateTimeInterface $seenAt): array
    {
        return [
            'author_name' => $review->authorName,
            'author_avatar' => $review->authorAvatar,
            'author_level' => $review->authorLevel,
            'rating' => $review->rating,
            'text' => $review->text,
            'published_at' => $review->publishedAt,
            'likes' => $review->likes,
            'dislikes' => $review->dislikes,
            'business_reply' => $review->businessReply,
            'business_reply_at' => $review->businessReplyAt,
            'content_hash' => $hash,
            'last_seen_at' => $seenAt,
        ];
    }
}
