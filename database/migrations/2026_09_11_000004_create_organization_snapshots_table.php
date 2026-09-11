<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parse_run_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->unsignedInteger('reviews_stored');
            $table->json('changes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_snapshots');
    }
};
