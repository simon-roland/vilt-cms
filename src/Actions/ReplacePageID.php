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
            if (empty($item['data']) || !is_array($item['data'])) {
                continue;
            }

            if (!empty($item['data']['page_id'])) {
                $page = $pages->firstWhere('id', $item['data']['page_id']);

                if ($page) {
                    $item['data']['page'] = [
                        'slug' => $page->slug,
                        'frontpage' => $page->layout[0]['type'] === 'frontpage',
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
