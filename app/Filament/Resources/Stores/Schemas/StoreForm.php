<?php

namespace App\Filament\Resources\Stores\Schemas;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Informasi Toko')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Toko')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('STR-001'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Toko')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('area')
                            ->label('Area')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('regional')
                            ->label('Regional')
                            ->maxLength(100),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active'   => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])->columns(2),
                Forms\Components\Section::make('Auditee / PIC Toko')
                    ->description('Hubungkan user Auditee yang bertanggung jawab atas toko ini')
                    ->schema([
                        Forms\Components\Select::make('auditees')
                            ->label('Auditee')
                            ->relationship('auditees', 'name')
                            ->options(
                                User::whereHas('roles', fn ($q) => $q->where('name', 'auditee'))
                                    ->pluck('name', 'id')
                            )
                            ->multiple()
                            ->preload()
                            ->native(false),
                    ]),
            ]);
    }
}
