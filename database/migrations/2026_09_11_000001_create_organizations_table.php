<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('yandex_id', 32);
            $table->string('source_url', 2048);
            $table->string('title')->nullable();
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->timestamp('last_parsed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'yandex_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
