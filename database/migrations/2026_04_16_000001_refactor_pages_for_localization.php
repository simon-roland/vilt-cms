<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultLocale = config('cms.default_locale', config('app.locale', 'en'));

        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->string('slug');
            $table->json('layout');
            $table->json('blocks')->nullable();
            $table->json('meta')->nullable();
            $table->json('published_content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_frontpage')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['locale', 'slug']);
            $table->unique(['locale', 'is_frontpage']);
        });

        // Backfill existing pages into page_contents under the default locale.
        DB::table('pages')->orderBy('id')->each(function ($page) use ($defaultLocale) {
            DB::table('page_contents')->insert([
                'page_id' => $page->id,
                'locale' => $defaultLocale,
                'name' => $page->name,
                'slug' => $page->slug,
                'layout' => $page->layout,
                'blocks' => $page->blocks,
                'meta' => $page->meta,
                'published_content' => $page->published_content,
                'published_at' => $page->published_at,
                'is_frontpage' => $page->is_frontpage,
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
                'deleted_at' => $page->deleted_at,
            ]);
        });

        // Strip per-locale columns from `pages` — it becomes a thin grouping entity.
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_slug_unique');
            $table->dropUnique('pages_is_frontpage_unique');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'slug',
                'layout',
                'blocks',
                'meta',
                'published_content',
                'published_at',
                'is_frontpage',
            ]);
        });
    }

    public function down(): void
    {
        $defaultLocale = config('cms.default_locale', config('app.locale', 'en'));

        Schema::table('pages', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('slug')->nullable()->after('name');
            $table->json('layout')->nullable()->after('slug');
            $table->json('meta')->nullable()->after('layout');
            $table->json('blocks')->nullable()->after('meta');
            $table->json('published_content')->nullable()->after('blocks');
            $table->timestamp('published_at')->nullable()->after('published_content');
            $table->boolean('is_frontpage')->nullable()->default(null)->after('published_at');
        });

        // Copy default-locale content back to pages.
        DB::table('page_contents')
            ->where('locale', $defaultLocale)
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('pages')->where('id', $row->page_id)->update([
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'layout' => $row->layout,
                    'meta' => $row->meta,
                    'blocks' => $row->blocks,
                    'published_content' => $row->published_content,
                    'published_at' => $row->published_at,
                    'is_frontpage' => $row->is_frontpage,
                ]);
            });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change();
            $table->json('layout')->nullable(false)->change();
            $table->unique('slug');
            $table->unique('is_frontpage');
        });

        Schema::dropIfExists('page_contents');
    }
};
