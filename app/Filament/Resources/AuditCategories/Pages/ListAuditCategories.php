<?php

namespace App\Filament\Resources\AuditCategories\Pages;

use App\Filament\Resources\AuditCategories\AuditCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuditCategories extends ListRecords
{
    protected static string $resource = AuditCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
