<?php

namespace App\Filament\Resources\Findings\Schemas;

use App\Models\AuditCategory;
use App\Models\Audit;
use App\Models\Sop;
use Filament\Forms;
use Filament\Schemas\Schema;

class FindingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Detail Finding')
                    ->schema([
                        Forms\Components\Select::make('audit_id')
                            ->label('Audit')
                            ->relationship('audit', 'audit_number')
                            ->options(Audit::orderBy('audit_number')->pluck('audit_number', 'id'))
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->options(AuditCategory::active()->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Forms\Components\Select::make('sop_id')
                            ->label('SOP / SE')
                            ->options(Sop::active()->pluck('title', 'id'))
                            ->nullable()
                            ->native(false)
                            ->searchable(),
                        Forms\Components\Select::make('severity')
                            ->label('Severity')
                            ->options([
                                'CRITICAL'    => 'Critical',
                                'MAJOR'       => 'Major',
                                'MINOR'       => 'Minor',
                                'OBSERVATION' => 'Observation',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'OPEN'                 => 'Open',
                                'IN_PROGRESS'          => 'In Progress',
                                'WAITING_VERIFICATION' => 'Waiting Verification',
                                'VERIFIED'             => 'Verified',
                                'CLOSED'               => 'Closed',
                            ])
                            ->default('OPEN')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('loss_amount')
                            ->label('Nominal Kerugian (Rp)')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp'),
                        Forms\Components\Textarea::make('finding')
                            ->label('Temuan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('auditor_opinion')
                            ->label('Opini Auditor')
                            ->rows(3),
                        Forms\Components\Textarea::make('recommendation')
                            ->label('Rekomendasi Perbaikan')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }
}
