<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE reviews MODIFY rating TINYINT UNSIGNED NULL');
        DB::statement('ALTER TABLE reviews MODIFY published_at TIMESTAMP NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::table('reviews')->whereNull('rating')->update(['rating' => 0]);
        DB::table('reviews')->whereNull('published_at')->update(['published_at' => DB::raw('created_at')]);

        DB::statement('ALTER TABLE reviews MODIFY rating TINYINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE reviews MODIFY published_at TIMESTAMP NOT NULL');
    }
};
