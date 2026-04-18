<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Support\Locales;

class ReplacePageID extends Action
{
    public function handle($items, ?string $locale = null)
    {
        $locale ??= Locales::default();

        $contents = PageContent::where('locale', $locale)->get()->keyBy('page_id');

        $this->replacePageID($items, $contents);

        return $items;
    }

    protected function replacePageID(&$items, $contents)
    {
        foreach ($items as &$item) {
            if (! is_array($item)) {
                continue;
            }

            // Flat repeater item (e.g. ActionsField): { label, link_type, page_id, ... }
            if (! empty($item['page_id']) && ! array_key_exists('data', $item)) {
                $content = $contents->get($item['page_id']);

                if ($content) {
                    $item['page'] = [
                        'slug' => $content->slug,
                        'frontpage' => (bool) $content->is_frontpage,
                    ];
                }

                unset($item['page_id']);

                continue;
            }

            // Block / layout item: { type, data: { ... } }
            if (empty($item['data']) || ! is_array($item['data'])) {
                continue;
            }

            if (! empty($item['data']['page_id'])) {
                $content = $contents->get($item['data']['page_id']);

                if ($content) {
                    $item['data']['page'] = [
                        'slug' => $content->slug,
                        'frontpage' => (bool) $content->is_frontpage,
                    ];
                }

                unset($item['data']['page_id']);
            }

            foreach ($item['data'] as &$data) {
                if (is_array($data)) {
                    $this->replacePageID($data, $contents);
                }
            }
        }
    }
}
