<?php

namespace App\Filament\Resources\Sops\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class SopForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Informasi SOP / SE')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('SOP-001'),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('document')
                            ->label('Dokumen (PDF)')
                            ->disk(config('filesystems.default'))
                            ->directory('sop-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(3),
            ]);
    }
}
