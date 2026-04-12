<?php

namespace RolandSolutions\ViltCms\Filament\Pages\Schemas;

use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class DefaultSiteSettingsSchema
{
    public static function fields(): array
    {
        return [
            Tabs::make('settings')
                ->tabs([
                    Tab::make(__('cms::cms.settings_section_general'))
                        ->schema([
                            TextInput::make('site_name')
                                ->label(__('cms::cms.settings_site_name'))
                                ->required(),
                            MediaPicker::make('logo')
                                ->label(__('cms::cms.settings_logo')),
                            MediaPicker::make('favicon')
                                ->label(__('cms::cms.settings_favicon')),
                        ]),

                    Tab::make(__('cms::cms.settings_section_social'))
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

                    Tab::make(__('cms::cms.settings_section_seo'))
                        ->schema([
                            MediaPicker::make('og_image')
                                ->label(__('cms::cms.settings_og_image')),
                            TextInput::make('twitter_handle')
                                ->label(__('cms::cms.settings_twitter_handle'))
                                ->placeholder('@yoursite'),
                            TextInput::make('title_format')
                                ->label(__('cms::cms.settings_title_format'))
                                ->placeholder('{title} – {site}')
                                ->helperText(__('cms::cms.settings_title_format_helper')),
                        ]),

                    Tab::make(__('cms::cms.settings_section_scripts'))
                        ->schema([
                            Textarea::make('head_scripts')
                                ->label(__('cms::cms.settings_head_scripts'))
                                ->helperText(__('cms::cms.settings_head_scripts_helper'))
                                ->rows(6)
                                ->columnSpanFull(),
                            Textarea::make('body_start_scripts')
                                ->label(__('cms::cms.settings_body_start_scripts'))
                                ->helperText(__('cms::cms.settings_body_start_scripts_helper'))
                                ->rows(6)
                                ->columnSpanFull(),
                            Textarea::make('body_end_scripts')
                                ->label(__('cms::cms.settings_body_end_scripts'))
                                ->helperText(__('cms::cms.settings_body_end_scripts_helper'))
                                ->rows(6)
                                ->columnSpanFull(),
                        ]),
                ])
                ->persistTabInQueryString('settings-tab')
                ->columnSpanFull(),
        ];
    }
}
