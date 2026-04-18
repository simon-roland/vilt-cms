<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('published_content')->nullable()->after('meta');
            $table->timestamp('published_at')->nullable()->after('published_content');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('unique_slug_status');
            $table->unique('slug');
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('id');
            $table->dropUnique('pages_slug_unique');
            $table->unique(['slug', 'status'], 'unique_slug_status');
            $table->dropColumn(['published_content', 'published_at']);
        });
    }
};
