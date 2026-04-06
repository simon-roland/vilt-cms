<?php

namespace RolandSolutions\ViltCms\Filament\Pages\Schemas;

use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class DefaultSiteSettingsSchema
{
    public static function fields(): array
    {
        return [
            Section::make(__('cms::cms.settings_section_general'))
                ->schema([
                    MediaPicker::make('logo')
                        ->label(__('cms::cms.settings_logo')),
                    MediaPicker::make('favicon')
                        ->label(__('cms::cms.settings_favicon')),
                ]),

            Section::make(__('cms::cms.settings_section_social'))
                ->schema([
                    TextInput::make('facebook_url')
                        ->label('Facebook')
                        ->url(),
                    TextInput::make('instagram_url')
                        ->label('Instagram')
                        ->url(),
                    TextInput::make('linkedin_url')
                        ->label('LinkedIn')
                        ->url(),
                    TextInput::make('x_url')
                        ->label('X (Twitter)')
                        ->url(),
                    TextInput::make('youtube_url')
                        ->label('YouTube')
                        ->url(),
                ]),

            Section::make(__('cms::cms.settings_section_seo'))
                ->schema([
                    MediaPicker::make('og_image')
                        ->label(__('cms::cms.settings_og_image')),
                ]),
        ];
    }
}
