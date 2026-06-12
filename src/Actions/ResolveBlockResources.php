<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Contracts\BlockResource;
use RolandSolutions\ViltCms\Models\PageContent;

class ResolveBlockResources extends Action
{
    public function handle(PageContent $page)
    {
        $blocks = $page->blocks;
        $this->resolveBlockResource($blocks);
        $page->blocks = $blocks;
    }

    protected function resolveBlockResource(&$blocks)
    {
        foreach ($blocks as &$block) {
            if (empty($block['data']) || ! is_array($block['data'])) {
                continue;
            }

            if (! empty($block['data']['_resource'])) {
                $resourceClass = $block['data']['_resource'];
                if (class_exists($resourceClass) && is_subclass_of($resourceClass, BlockResource::class)) {
                    $block['data']['_resource'] = new $resourceClass($block['data']);
                }
            }

            foreach ($block['data'] as &$data) {
                if (is_array($data)) {
                    $this->resolveBlockResource($data);
                }
            }
        }
    }
}
