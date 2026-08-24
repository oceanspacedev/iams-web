<?php

namespace App\Filament\Resources\ActionPlans\Schemas;

use App\Models\Finding;
use Filament\Forms;
use Filament\Schemas\Schema;

class ActionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('finding_id')
                            ->label('Finding')
                            ->relationship('finding', 'finding')
                            ->options(Finding::all()->pluck('finding', 'id')->map(fn ($f) => \Str::limit($f, 60)))
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'OPEN'        => 'Open',
                                'IN_PROGRESS' => 'In Progress',
                                'COMPLETED'   => 'Completed',
                                'OVERDUE'     => 'Overdue',
                            ])
                            ->default('OPEN')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('pic')
                            ->label('PIC')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('deadline')
                            ->label('Deadline')
                            ->native(false),
                        Forms\Components\Textarea::make('action_plan')
                            ->label('Rencana Tindak Lanjut')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('response')
                            ->label('Response Toko')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
