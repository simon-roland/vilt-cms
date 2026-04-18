<?php

namespace RolandSolutions\ViltCms\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Support\Locales;

class CmsShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::count() > 0 || Navigation::count() > 0) {
            return;
        }

        $locale = Locales::default();

        $frontpageLayout = [['type' => 'default', 'data' => ['id' => Str::uuid()->toString()]]];
        $frontpageBlocks = [
            [
                'type' => 'hero',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'headline' => 'Welcome to Your New Site',
                    'text' => 'This is a showcase of your CMS. Edit this page in the admin panel to start building your site.',
                    'image' => [],
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'content' => '<h2>Getting Started</h2><p>This text block uses the rich text editor. You can format content with <strong>bold</strong>, <em>italic</em>, and more. Head to the admin panel to edit this content and explore the available blocks.</p><p>The CMS comes with a flexible block-based editor, a media library for managing images and videos, and customizable navigation menus.</p>',
                ],
            ],
            [
                'type' => 'gallery',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'headline' => 'Gallery',
                    'images' => [],
                ],
            ],
            [
                'type' => 'video',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'headline' => 'Featured Video',
                    'video' => [],
                ],
            ],
        ];

        $frontpage = Page::create([]);
        $frontpage->contents()->create([
            'locale' => $locale,
            'name' => 'Home',
            'slug' => 'frontpage',
            'is_frontpage' => true,
            'layout' => $frontpageLayout,
            'blocks' => $frontpageBlocks,
            'published_content' => [
                'name' => 'Home',
                'layout' => $frontpageLayout,
                'blocks' => $frontpageBlocks,
                'meta' => null,
            ],
            'published_at' => now(),
        ]);

        $aboutLayout = [['type' => 'default', 'data' => ['id' => Str::uuid()->toString()]]];
        $aboutBlocks = [
            [
                'type' => 'hero',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'headline' => 'About Us',
                    'text' => 'Learn more about what we do and how we can help.',
                    'image' => [],
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'id' => Str::uuid()->toString(),
                    'content' => '<p>This is the about page. Replace this content with information about your organization, project, or whatever suits your site.</p><p>You can add more blocks below this one, reorder them, or remove them entirely from the admin panel.</p>',
                ],
            ],
        ];

        $about = Page::create([]);
        $about->contents()->create([
            'locale' => $locale,
            'name' => 'About',
            'slug' => 'about',
            'layout' => $aboutLayout,
            'blocks' => $aboutBlocks,
            'published_content' => [
                'name' => 'About',
                'layout' => $aboutLayout,
                'blocks' => $aboutBlocks,
                'meta' => null,
            ],
            'published_at' => now(),
        ]);

        Navigation::create([
            'type' => 'header',
            'locale' => $locale,
            'items' => [
                [
                    'type' => 'link',
                    'data' => [
                        'id' => Str::uuid()->toString(),
                        'label' => 'Home',
                        'link_type' => 'page',
                        'page_id' => $frontpage->id,
                        'target' => '_self',
                    ],
                ],
                [
                    'type' => 'link',
                    'data' => [
                        'id' => Str::uuid()->toString(),
                        'label' => 'About',
                        'link_type' => 'page',
                        'page_id' => $about->id,
                        'target' => '_self',
                    ],
                ],
                [
                    'type' => 'dropdown',
                    'data' => [
                        'id' => Str::uuid()->toString(),
                        'label' => 'More',
                        'items' => [
                            [
                                'type' => 'link',
                                'data' => [
                                    'id' => Str::uuid()->toString(),
                                    'label' => 'Example',
                                    'link_type' => 'url',
                                    'url' => 'https://example.com',
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Navigation::create([
            'type' => 'footer',
            'locale' => $locale,
            'items' => [
                [
                    'type' => 'link',
                    'data' => [
                        'id' => Str::uuid()->toString(),
                        'label' => 'Home',
                        'link_type' => 'page',
                        'page_id' => $frontpage->id,
                        'target' => '_self',
                    ],
                ],
                [
                    'type' => 'link',
                    'data' => [
                        'id' => Str::uuid()->toString(),
                        'label' => 'About',
                        'link_type' => 'page',
                        'page_id' => $about->id,
                        'target' => '_self',
                    ],
                ],
            ],
        ]);
    }
}
