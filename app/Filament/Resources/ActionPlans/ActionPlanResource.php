<?php

namespace App\Filament\Resources\ActionPlans;

use App\Filament\Resources\ActionPlans\Pages\CreateActionPlan;
use App\Filament\Resources\ActionPlans\Pages\EditActionPlan;
use App\Filament\Resources\ActionPlans\Pages\ListActionPlans;
use App\Filament\Resources\ActionPlans\Schemas\ActionPlanForm;
use App\Filament\Resources\ActionPlans\Tables\ActionPlansTable;
use App\Models\ActionPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActionPlanResource extends Resource
{
    protected static ?string $model = ActionPlan::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Action Plan';
    protected static ?string $pluralModelLabel = 'Action Plans';

    public static function getNavigationGroup(): ?string
    {
        return 'Audit';
    }

    public static function form(Schema $schema): Schema
    {
        return ActionPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActionPlansTable::configure($table);
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
            'index' => ListActionPlans::route('/'),
            'create' => CreateActionPlan::route('/create'),
            'edit' => EditActionPlan::route('/{record}/edit'),
        ];
    }
}
