<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\Page;

class PublishPage extends Action
{
    public function handle(Page $page): void
    {
        $page->update([
            'published_content' => [
                'name'   => $page->name,
                'layout' => $page->layout,
                'blocks' => $page->blocks,
                'meta'   => $page->meta,
            ],
            'published_at' => now(),
        ]);
    }
}
