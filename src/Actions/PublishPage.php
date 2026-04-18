<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\PageContent;

class PublishPage extends Action
{
    public function handle(PageContent $content): void
    {
        $content->update([
            'published_content' => [
                'name' => $content->name,
                'layout' => $content->layout,
                'blocks' => $content->blocks,
                'meta' => $content->meta,
            ],
            'published_at' => now(),
        ]);
    }
}
