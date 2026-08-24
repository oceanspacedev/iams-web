<?php

namespace App\Filament\Resources\ActionPlans\Pages;

use App\Filament\Resources\ActionPlans\ActionPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActionPlans extends ListRecords
{
    protected static string $resource = ActionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
