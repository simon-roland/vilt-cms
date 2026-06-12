<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use RolandSolutions\ViltCms\Filament\Fields\ID;

class Dropdown extends BaseBlock
{
    /**
     * Maximum levels of dropdown nesting offered in the builder UI.
     * The frontend components render arbitrary depth, so raising this
     * (or offering a different block set via NavigationFormSchema) is safe.
     */
    public const MAX_DEPTH = 3;

    public function __construct(protected int $depth = 1) {}

    public static function make(int $depth = 1): Block
    {
        return (new static($depth))->setup();
    }

    public function setup(): Block
    {
        $blocks = [Link::make()];

        if ($this->depth < self::MAX_DEPTH) {
            $blocks[] = static::make($this->depth + 1);
        }

        return Block::make('dropdown')
            ->label(fn (?array $state): string => empty($state['label']) ? __('cms::cms.block_dropdown') : __('cms::cms.block_dropdown').': '.$state['label'])
            ->schema([
                ID::make(),
                TextInput::make('label')
                    ->label(__('cms::cms.name'))
                    ->required(),
                Builder::make('items')
                    ->label(__('cms::cms.block_dropdown_items'))
                    ->blocks($blocks)
                    ->collapsible()
                    ->columnSpan(2)
                    ->required()
                    ->reorderable()
                    ->blockNumbers()
                    ->addActionLabel(__('cms::cms.add'))
                    ->addBetweenActionLabel(__('cms::cms.insert_between')),
            ]);
    }
}
