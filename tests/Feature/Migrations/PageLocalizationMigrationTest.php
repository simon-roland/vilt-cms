<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Round-trip test for the Stage 1 migration that splits `pages` into
 * `pages` + `page_contents`. Seeds the pre-split shape, runs the split,
 * asserts data survived, then rolls back and asserts the old shape
 * comes back with the same data.
 */
beforeEach(function () {
    // Roll back the three Stage 1 migrations so we're in the pre-split state.
    $this->artisan('migrate:rollback', ['--step' => 3])->assertSuccessful();
});

function seedOldStylePage(array $attrs): int
{
    return DB::table('pages')->insertGetId(array_merge([
        'name' => 'Untitled',
        'slug' => 'untitled-'.uniqid(),
        'layout' => json_encode([['type' => 'default', 'data' => ['id' => 'abc']]]),
        'blocks' => null,
        'meta' => null,
        'published_content' => null,
        'published_at' => null,
        'is_frontpage' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $attrs));
}

it('backfills all page states into page_contents under the default locale', function () {
    // Draft-only page
    $draftId = seedOldStylePage(['name' => 'Draft only', 'slug' => 'draft-only']);

    // Published page
    $publishedSnapshot = json_encode([
        'name' => 'Published', 'layout' => [['type' => 'default', 'data' => ['id' => 'p']]],
        'blocks' => null, 'meta' => null,
    ]);
    $publishedId = seedOldStylePage([
        'name' => 'Published', 'slug' => 'published',
        'published_content' => $publishedSnapshot,
        'published_at' => now(),
    ]);

    // Frontpage
    $frontpageId = seedOldStylePage([
        'name' => 'Home', 'slug' => 'home',
        'is_frontpage' => true,
        'published_content' => $publishedSnapshot,
        'published_at' => now(),
    ]);

    // Soft-deleted page
    $trashedId = seedOldStylePage([
        'name' => 'Trashed', 'slug' => 'trashed',
        'deleted_at' => now(),
    ]);

    // Run the Stage 1 migrations forward.
    $this->artisan('migrate')->assertSuccessful();

    // pages keeps `name` as an internal identifier; per-locale columns are gone.
    expect(Schema::hasColumn('pages', 'slug'))->toBeFalse();
    expect(Schema::hasColumn('pages', 'name'))->toBeTrue();
    expect(Schema::hasColumn('pages', 'is_frontpage'))->toBeFalse();

    // page_contents has no `name` and no soft-deletes.
    expect(Schema::hasColumn('page_contents', 'name'))->toBeFalse();
    expect(Schema::hasColumn('page_contents', 'deleted_at'))->toBeFalse();

    // page_contents should carry all four original pages under locale 'en'.
    $contents = DB::table('page_contents')->orderBy('page_id')->get();
    expect($contents)->toHaveCount(4);
    expect($contents->pluck('locale')->unique()->all())->toBe(['en']);

    $draft = $contents->firstWhere('page_id', $draftId);
    expect($draft->slug)->toBe('draft-only');
    expect($draft->published_content)->toBeNull();
    expect($draft->is_frontpage)->toBeNull();

    $published = $contents->firstWhere('page_id', $publishedId);
    expect($published->published_content)->not->toBeNull();
    expect($published->published_at)->not->toBeNull();

    $frontpage = $contents->firstWhere('page_id', $frontpageId);
    expect((bool) $frontpage->is_frontpage)->toBeTrue();

    // Soft-deleted pages retain their trashed state on `pages`; contents are untouched.
    expect(DB::table('pages')->where('id', $trashedId)->value('deleted_at'))->not->toBeNull();

    // Name survives on pages.
    expect(DB::table('pages')->where('id', $publishedId)->value('name'))->toBe('Published');
    expect(DB::table('pages')->where('id', $frontpageId)->value('name'))->toBe('Home');
});

it('rolls back cleanly, restoring pre-split shape and default-locale data', function () {
    $originalId = seedOldStylePage([
        'name' => 'Home', 'slug' => 'home', 'is_frontpage' => true,
    ]);

    $this->artisan('migrate')->assertSuccessful();
    $this->artisan('migrate:rollback', ['--step' => 3])->assertSuccessful();

    expect(Schema::hasColumn('pages', 'slug'))->toBeTrue();
    expect(Schema::hasColumn('pages', 'is_frontpage'))->toBeTrue();
    expect(Schema::hasTable('page_contents'))->toBeFalse();

    $row = DB::table('pages')->where('id', $originalId)->first();
    expect($row->slug)->toBe('home');
    expect((bool) $row->is_frontpage)->toBeTrue();
});
