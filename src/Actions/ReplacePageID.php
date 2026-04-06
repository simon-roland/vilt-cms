<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\Page;

class ReplacePageID extends Action
{
    public function handle($items)
    {
        $pages = Page::all();

        $this->replacePageID($items, $pages);

        return $items;
    }

    protected function replacePageID(&$items, $pages)
    {
        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }

            // Flat repeater item (e.g. ActionsField): { label, link_type, page_id, ... }
            if (!empty($item['page_id']) && !array_key_exists('data', $item)) {
                $page = $pages->firstWhere('id', $item['page_id']);

                if ($page) {
                    $item['page'] = [
                        'slug' => $page->slug,
                        'frontpage' => (bool) $page->is_frontpage,
                    ];
                }

                unset($item['page_id']);
                continue;
            }

            // Block / layout item: { type, data: { ... } }
            if (empty($item['data']) || !is_array($item['data'])) {
                continue;
            }

            if (!empty($item['data']['page_id'])) {
                $page = $pages->firstWhere('id', $item['data']['page_id']);

                if ($page) {
                    $item['data']['page'] = [
                        'slug' => $page->slug,
                        'frontpage' => (bool) $page->is_frontpage,
                    ];
                }

                unset($item['data']['page_id']);
            }

            foreach ($item['data'] as &$data) {
                if (is_array($data)) {
                    $this->replacePageID($data, $pages);
                }
            }
        }
    }
}
