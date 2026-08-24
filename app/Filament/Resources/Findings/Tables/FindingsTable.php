<?php

namespace App\Filament\Resources\Findings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FindingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('audit.audit_number')
                    ->label('No. Audit')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('audit.store.name')
                    ->label('Toko')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('finding')
                    ->label('Temuan')
                    ->limit(50),
                TextColumn::make('loss_amount')
                    ->label('Kerugian')
                    ->money('IDR')
                    ->placeholder('—')
                    ->sortable(),
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
                    ->label('Severity')
                    ->options([
                        'CRITICAL'    => 'Critical',
                        'MAJOR'       => 'Major',
                        'MINOR'       => 'Minor',
                        'OBSERVATION' => 'Observation',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'OPEN'                 => 'Open',
                        'IN_PROGRESS'          => 'In Progress',
                        'WAITING_VERIFICATION' => 'Waiting Verification',
                        'VERIFIED'             => 'Verified',
                        'CLOSED'               => 'Closed',
                    ]),
                SelectFilter::make('audit')
                    ->relationship('audit', 'audit_number')
                    ->label('Audit')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
