<?php

namespace App\Filament\Resources\Audits\Schemas;

use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Informasi Audit')
                    ->schema([
                        Forms\Components\TextInput::make('audit_number')
                            ->label('Nomor Audit')
                            ->default(fn () => \App\Models\Audit::generateNumber())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->disabled(fn (string $context) => $context === 'edit'),
                        Forms\Components\Select::make('store_id')
                            ->label('Toko')
                            ->relationship('store', 'name')
                            ->options(Store::active()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('auditor_id')
                            ->label('Auditor')
                            ->options(
                                User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('audit_date')
                            ->label('Tanggal Audit')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'PLANNED'     => 'Planned',
                                'IN_PROGRESS' => 'In Progress',
                                'COMPLETED'   => 'Completed',
                                'CLOSED'      => 'Closed',
                            ])
                            ->default('PLANNED')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
