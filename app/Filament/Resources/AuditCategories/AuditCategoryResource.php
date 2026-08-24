<?php

namespace App\Filament\Resources\AuditCategories;

use App\Filament\Resources\AuditCategories\Pages\CreateAuditCategory;
use App\Filament\Resources\AuditCategories\Pages\EditAuditCategory;
use App\Filament\Resources\AuditCategories\Pages\ListAuditCategories;
use App\Filament\Resources\AuditCategories\Schemas\AuditCategoryForm;
use App\Filament\Resources\AuditCategories\Tables\AuditCategoriesTable;
use App\Models\AuditCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuditCategoryResource extends Resource
{
    protected static ?string $model = AuditCategory::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Kategori Audit';
    protected static ?string $pluralModelLabel = 'Kategori Audit';

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function form(Schema $schema): Schema
    {
        return AuditCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditCategories::route('/'),
            'create' => CreateAuditCategory::route('/create'),
            'edit' => EditAuditCategory::route('/{record}/edit'),
        ];
    }
}
