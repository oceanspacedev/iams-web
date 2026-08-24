<?php

namespace App\Filament\Resources\AuditCategories\Pages;

use App\Filament\Resources\AuditCategories\AuditCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuditCategory extends EditRecord
{
    protected static string $resource = AuditCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
