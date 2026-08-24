<?php

namespace App\Filament\Resources\Audits\Tables;

use App\Models\Audit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('audit_number')
                    ->label('No. Audit')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('store.name')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('auditor.name')
                    ->label('Auditor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('audit_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('findings_count')
                    ->label('Findings')
                    ->counts('findings')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PLANNED'     => 'info',
                        'IN_PROGRESS' => 'warning',
                        'COMPLETED'   => 'success',
                        'CLOSED'      => 'gray',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PLANNED'     => 'Planned',
                        'IN_PROGRESS' => 'In Progress',
                        'COMPLETED'   => 'Completed',
                        'CLOSED'      => 'Closed',
                        default       => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PLANNED'     => 'Planned',
                        'IN_PROGRESS' => 'In Progress',
                        'COMPLETED'   => 'Completed',
                        'CLOSED'      => 'Closed',
                    ]),
                SelectFilter::make('store')
                    ->relationship('store', 'name')
                    ->label('Toko')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('audit_date', 'desc');
    }
}
