<?php

namespace App\Filament\Resources\Sops;

use App\Filament\Resources\Sops\Pages\CreateSop;
use App\Filament\Resources\Sops\Pages\EditSop;
use App\Filament\Resources\Sops\Pages\ListSops;
use App\Filament\Resources\Sops\Schemas\SopForm;
use App\Filament\Resources\Sops\Tables\SopsTable;
use App\Models\Sop;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SopResource extends Resource
{
    protected static ?string $model = Sop::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'SOP / SE';
    protected static ?string $pluralModelLabel = 'SOP / SE';

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function form(Schema $schema): Schema
    {
        return SopForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SopsTable::configure($table);
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
            'index' => ListSops::route('/'),
            'create' => CreateSop::route('/create'),
            'edit' => EditSop::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
