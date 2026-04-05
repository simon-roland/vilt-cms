<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\LivewireField;

class MediaPicker extends LivewireField
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->component('media-picker-field')
            ->data(['multiple' => false]);
    }

    public function multiple(bool $multiple = true): static
    {
        $data = $this->getData();
        $data['multiple'] = $multiple;

        return $this->data($data);
    }

    public function reorderable(): static
    {
        return $this;
    }
}
