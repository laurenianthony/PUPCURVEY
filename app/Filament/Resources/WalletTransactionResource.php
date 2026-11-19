<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages\ListWalletTransactions;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Wallet Ledger';

    protected static ?string $navigationGroup = 'Wallet & Rewards';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('user.student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'survey_answer',
                        'warning' => 'prof_redemption',
                        'info' => 'admin_adjustment',
                    ]),
                TextColumn::make('professor.name')
                    ->label('Professor')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? '+'.$state : (string) $state)
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'danger'),
                TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'survey_answer' => 'Survey Answer',
                    'prof_redemption' => 'Professor Redemption',
                    'admin_adjustment' => 'Admin Adjustment',
                ]),
                SelectFilter::make('professor_id')
                    ->relationship('professor', 'name')
                    ->label('Professor'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'professor'])
            ->latest();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
        ];
    }
}
