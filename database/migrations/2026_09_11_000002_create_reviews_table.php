<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->string('author_name');
            $table->string('author_avatar', 1024)->nullable();
            $table->string('author_level')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('text');
            $table->timestamp('published_at');
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('dislikes')->default(0);
            $table->text('business_reply')->nullable();
            $table->timestamp('business_reply_at')->nullable();
            $table->string('content_hash', 40);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['organization_id', 'external_id']);
            $table->index(['organization_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
