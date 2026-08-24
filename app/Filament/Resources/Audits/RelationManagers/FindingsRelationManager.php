<?php

namespace App\Filament\Resources\Audits\RelationManagers;

use App\Models\AuditCategory;
use App\Models\Sop;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'findings';
    protected static ?string $title = 'Findings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Detail Finding')
                    ->schema([
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
                        Forms\Components\Textarea::make('finding')
                            ->label('Temuan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('loss_amount')
                            ->label('Nominal Kerugian (Rp)')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp'),
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
                    ])->columns(2),
                Forms\Components\Section::make('Opini & Rekomendasi')
                    ->schema([
                        Forms\Components\Textarea::make('auditor_opinion')
                            ->label('Opini Auditor')
                            ->rows(3),
                        Forms\Components\Textarea::make('recommendation')
                            ->label('Rekomendasi Perbaikan')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('finding')
                    ->label('Temuan')
                    ->limit(60),
                TextColumn::make('loss_amount')
                    ->label('Kerugian')
                    ->money('IDR')
                    ->placeholder('—'),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CRITICAL'    => 'danger',
                        'MAJOR'       => 'warning',
                        'MINOR'       => 'info',
                        'OBSERVATION' => 'gray',
                        default       => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN'                 => 'danger',
                        'IN_PROGRESS'          => 'warning',
                        'WAITING_VERIFICATION' => 'info',
                        'VERIFIED'             => 'primary',
                        'CLOSED'               => 'success',
                        default                => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options([
                        'CRITICAL'    => 'Critical',
                        'MAJOR'       => 'Major',
                        'MINOR'       => 'Minor',
                        'OBSERVATION' => 'Observation',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'OPEN'                 => 'Open',
                        'IN_PROGRESS'          => 'In Progress',
                        'WAITING_VERIFICATION' => 'Waiting Verification',
                        'VERIFIED'             => 'Verified',
                        'CLOSED'               => 'Closed',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Finding'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
