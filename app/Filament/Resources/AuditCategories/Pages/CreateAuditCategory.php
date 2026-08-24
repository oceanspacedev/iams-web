<?php

namespace App\Filament\Resources\AuditCategories\Pages;

use App\Filament\Resources\AuditCategories\AuditCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditCategory extends CreateRecord
{
    protected static string $resource = AuditCategoryResource::class;
}
