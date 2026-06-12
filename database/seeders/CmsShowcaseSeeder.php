<?php

namespace RolandSolutions\ViltCms\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\SiteSettings;
use RolandSolutions\ViltCms\Support\Locales;

/**
 * Seeds a small published showcase site: a frontpage and an about page in
 * every configured locale, header/footer navigations per locale, and basic
 * site settings. Idempotent — does nothing when content already exists.
 */
class CmsShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::query()->exists() || Navigation::query()->exists()) {
            return;
        }

        DB::transaction(function () {
            $locales = array_keys(Locales::all()) ?: [Locales::default()];

            $home = Page::create(['name' => 'Home']);
            $about = Page::create(['name' => 'About']);

            foreach ($locales as $locale) {
                $copy = $this->copy($locale);

                $this->createPageContent($home, $locale, $copy['home'], isFrontpage: true);
                $this->createPageContent($about, $locale, $copy['about']);

                // The "More" group contains a nested group to showcase that
                // dropdowns can hold other dropdowns.
                $this->createNavigation('header', $locale, [
                    $this->pageLink($copy['nav']['home'], $home->id),
                    $this->pageLink($copy['nav']['about'], $about->id),
                    $this->dropdown($copy['nav']['more'], [
                        $this->urlLink($copy['nav']['example'], 'https://example.com'),
                        $this->dropdown($copy['nav']['resources'], [
                            $this->urlLink('Laravel', 'https://laravel.com'),
                            $this->urlLink('Filament', 'https://filamentphp.com'),
                        ]),
                    ]),
                ]);

                $this->createNavigation('footer', $locale, [
                    $this->pageLink($copy['nav']['home'], $home->id),
                    $this->pageLink($copy['nav']['about'], $about->id),
                ]);
            }

            $this->seedSiteSettings();
        });
    }

    /**
     * @param  array{slug: string, meta: array, blocks: array}  $content
     */
    private function createPageContent(Page $page, string $locale, array $content, bool $isFrontpage = false): void
    {
        $layout = [['type' => 'default', 'data' => ['id' => Str::uuid()->toString()]]];

        // Block definitions carry no IDs so each locale gets its own.
        $blocks = array_map(fn (array $block) => [
            'type' => $block['type'],
            'data' => ['id' => Str::uuid()->toString()] + $block['data'],
        ], $content['blocks']);

        $page->contents()->create([
            'locale' => $locale,
            'slug' => $content['slug'],
            'is_frontpage' => $isFrontpage ? true : null,
            'layout' => $layout,
            'blocks' => $blocks,
            'meta' => $content['meta'],
            'published_content' => [
                'layout' => $layout,
                'blocks' => $blocks,
                'meta' => $content['meta'],
            ],
            'published_at' => now(),
        ]);
    }

    private function createNavigation(string $type, string $locale, array $items): void
    {
        Navigation::create([
            'type' => $type,
            'locale' => $locale,
            'items' => $items,
        ]);
    }

    private function pageLink(string $label, int $pageId): array
    {
        return [
            'type' => 'link',
            'data' => [
                'id' => Str::uuid()->toString(),
                'label' => $label,
                'link_type' => 'page',
                'page_id' => $pageId,
                'target' => '_self',
            ],
        ];
    }

    private function urlLink(string $label, string $url): array
    {
        return [
            'type' => 'link',
            'data' => [
                'id' => Str::uuid()->toString(),
                'label' => $label,
                'link_type' => 'url',
                'url' => $url,
                'target' => '_blank',
            ],
        ];
    }

    private function dropdown(string $label, array $items): array
    {
        return [
            'type' => 'dropdown',
            'data' => [
                'id' => Str::uuid()->toString(),
                'label' => $label,
                'items' => $items,
            ],
        ];
    }

    private function seedSiteSettings(): void
    {
        $global = SiteSettings::global();

        if (! empty($global->data)) {
            return;
        }

        $global->update([
            'data' => [
                'site_name' => config('app.name', 'My Site'),
                'title_format' => '{title} — {site}',
            ],
        ]);
    }

    /**
     * Showcase copy per locale. Danish ships translated (matching the
     * package's bundled da lang files); any other locale falls back to
     * English placeholder copy. Slugs are unique per locale, so reusing
     * the English slugs across locales is safe.
     *
     * @return array{home: array, about: array, nav: array<string, string>}
     */
    private function copy(string $locale): array
    {
        if ($locale === 'da') {
            return [
                'home' => [
                    'slug' => 'forside',
                    'meta' => [
                        'title' => 'Forside',
                        'description' => 'Velkommen til dit nye website, bygget med VILT-CMS.',
                    ],
                    'blocks' => [
                        [
                            'type' => 'hero',
                            'data' => [
                                'headline' => 'Velkommen til dit nye website',
                                'text' => 'Dette er en demoside fra dit CMS. Redigér denne side i administrationspanelet for at komme i gang.',
                                'image' => [],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'data' => [
                                'content' => '<h2>Kom godt i gang</h2><p>Denne tekstblok bruger rich text-editoren. Du kan formatere indhold med <strong>fed</strong>, <em>kursiv</em> og meget mere. Gå til administrationspanelet for at redigere indholdet og udforske de tilgængelige blokke.</p>',
                            ],
                        ],
                        [
                            'type' => 'gallery',
                            'data' => ['headline' => 'Galleri', 'images' => []],
                        ],
                        [
                            'type' => 'video',
                            'data' => ['headline' => 'Udvalgt video', 'video' => []],
                        ],
                    ],
                ],
                'about' => [
                    'slug' => 'om-os',
                    'meta' => [
                        'title' => 'Om os',
                        'description' => 'Læs mere om, hvad vi laver, og hvordan vi kan hjælpe.',
                    ],
                    'blocks' => [
                        [
                            'type' => 'hero',
                            'data' => [
                                'headline' => 'Om os',
                                'text' => 'Læs mere om, hvad vi laver, og hvordan vi kan hjælpe.',
                                'image' => [],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'data' => [
                                'content' => '<p>Dette er om-siden. Erstat dette indhold med information om din organisation eller dit projekt.</p><p>Du kan tilføje flere blokke, ændre rækkefølgen eller fjerne dem helt fra administrationspanelet.</p>',
                            ],
                        ],
                    ],
                ],
                'nav' => [
                    'home' => 'Hjem',
                    'about' => 'Om os',
                    'more' => 'Mere',
                    'example' => 'Eksempel',
                    'resources' => 'Ressourcer',
                ],
            ];
        }

        return [
            'home' => [
                'slug' => 'home',
                'meta' => [
                    'title' => 'Home',
                    'description' => 'Welcome to your new site, built with VILT-CMS.',
                ],
                'blocks' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'headline' => 'Welcome to Your New Site',
                            'text' => 'This is a showcase of your CMS. Edit this page in the admin panel to start building your site.',
                            'image' => [],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'content' => '<h2>Getting Started</h2><p>This text block uses the rich text editor. You can format content with <strong>bold</strong>, <em>italic</em>, and more. Head to the admin panel to edit this content and explore the available blocks.</p><p>The CMS comes with a flexible block-based editor, a media library for managing images and videos, and customizable navigation menus.</p>',
                        ],
                    ],
                    [
                        'type' => 'gallery',
                        'data' => ['headline' => 'Gallery', 'images' => []],
                    ],
                    [
                        'type' => 'video',
                        'data' => ['headline' => 'Featured Video', 'video' => []],
                    ],
                ],
            ],
            'about' => [
                'slug' => 'about',
                'meta' => [
                    'title' => 'About',
                    'description' => 'Learn more about what we do and how we can help.',
                ],
                'blocks' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'headline' => 'About Us',
                            'text' => 'Learn more about what we do and how we can help.',
                            'image' => [],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'content' => '<p>This is the about page. Replace this content with information about your organization, project, or whatever suits your site.</p><p>You can add more blocks below this one, reorder them, or remove them entirely from the admin panel.</p>',
                        ],
                    ],
                ],
            ],
            'nav' => [
                'home' => 'Home',
                'about' => 'About',
                'more' => 'More',
                'example' => 'Example',
                'resources' => 'Resources',
            ],
        ];
    }
}
