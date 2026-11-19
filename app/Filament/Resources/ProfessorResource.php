<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessorResource\Pages\CreateProfessor;
use App\Filament\Resources\ProfessorResource\Pages\EditProfessor;
use App\Filament\Resources\ProfessorResource\Pages\ListProfessors;
use App\Models\Professor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProfessorResource extends Resource
{
    protected static ?string $model = Professor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Professors';

    protected static ?string $navigationGroup = 'Wallet & Rewards';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Toggle::make('is_active')
                ->label('Active for Redemption')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('wallet_transactions_count')
                    ->counts('walletTransactions')
                    ->label('Total Redemptions'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Professor $record): bool => $record->walletTransactions()->count() === 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfessors::route('/'),
            'create' => CreateProfessor::route('/create'),
            'edit' => EditProfessor::route('/{record}/edit'),
        ];
    }
}
