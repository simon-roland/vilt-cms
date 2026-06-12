<?php

namespace RolandSolutions\ViltCms\Filament\Resources\User;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Filament\Resources\User\Pages\CreateUser;
use RolandSolutions\ViltCms\Filament\Resources\User\Pages\EditUser;
use RolandSolutions\ViltCms\Filament\Resources\User\Pages\ListUsers;
use RolandSolutions\ViltCms\Filament\Resources\User\Schemas\UserForm;
use RolandSolutions\ViltCms\Filament\Resources\User\Tables\UsersTable;

class UserResource extends Resource
{
    public static function getModel(): string
    {
        return config('cms.user_model', 'App\Models\User');
    }

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('cms::cms.user_navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('cms::cms.user_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cms::cms.user_plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
