<?php

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area')
                    ->label('Area')
                    ->searchable(),
                TextColumn::make('regional')
                    ->label('Regional')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active'   => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default    => $state,
                    }),
                TextColumn::make('auditees_count')
                    ->label('Auditee')
                    ->counts('auditees')
                    ->sortable(),
                TextColumn::make('audits_count')
                    ->label('Total Audit')
                    ->counts('audits')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'   => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }
}
