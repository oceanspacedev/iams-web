<?php

namespace App\Filament\Resources\ActionPlans\Pages;

use App\Filament\Resources\ActionPlans\ActionPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActionPlan extends EditRecord
{
    protected static string $resource = ActionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
