<?php

namespace App\Filament\Resources\ActionPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('finding.audit.audit_number')
                    ->label('No. Audit')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('finding.audit.store.name')
                    ->label('Toko')
                    ->searchable(),
                TextColumn::make('pic')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN'        => 'danger',
                        'IN_PROGRESS' => 'warning',
                        'COMPLETED'   => 'success',
                        'OVERDUE'     => 'danger',
                        default       => 'gray',
                    }),
                TextColumn::make('finding.status')
                    ->label('Status Finding')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'OPEN'        => 'Open',
                        'IN_PROGRESS' => 'In Progress',
                        'COMPLETED'   => 'Completed',
                        'OVERDUE'     => 'Overdue',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('deadline');
    }
}
