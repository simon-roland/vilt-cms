<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Support\Arrayable;

class PdfUpload extends FileUpload
{
    public function acceptedFileTypes(array | Arrayable | Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }
}
