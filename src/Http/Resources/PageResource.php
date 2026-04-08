<?php

namespace RolandSolutions\ViltCms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'layout' => $this->layout[0] ?? null,
            'is_frontpage' => $this->is_frontpage,
            'meta' => $this->meta,
            'blocks' => $this->blocks,
            'updated_at' => $this->updated_at,
        ];
    }
}
