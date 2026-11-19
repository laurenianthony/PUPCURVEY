<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'All Users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('student_id')
                ->label('Student ID')
                ->nullable()
                ->unique(ignoreRecord: true),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('program')
                ->options([
                    'BSIT' => 'BSIT',
                    'BTLED' => 'BTLED',
                    'BSENT' => 'BSENT',
                    'ADMIN' => 'ADMIN',
                ])
                ->required(),
            Select::make('year_level')
                ->options([
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                ])
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('Admin Account'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('program')
                    ->badge()
                    ->sortable(),
                TextColumn::make('year_level')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->colors([
                        'warning' => 'admin',
                        'gray' => 'user',
                    ]),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'restricted',
                        'danger' => 'banned',
                    ]),
                TextColumn::make('wallet_balance')
                    ->label('Points')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'restricted' => 'Restricted',
                    'banned' => 'Banned',
                ]),
                SelectFilter::make('program')->options([
                    'BSIT' => 'BSIT',
                    'BTLED' => 'BTLED',
                    'BSENT' => 'BSENT',
                    'ADMIN' => 'ADMIN',
                ]),
                SelectFilter::make('role')->options([
                    'user' => 'User',
                    'admin' => 'Admin',
                ]),
            ])
            ->actions([
                Action::make('changeStatus')
                    ->label('Change Status')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'restricted' => 'Restricted',
                                'banned' => 'Banned',
                            ])
                            ->default(fn (User $record): string => $record->status)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('User status updated.')
                            ->success()
                            ->send();
                    }),
                Action::make('adjustPoints')
                    ->label('Adjust Points')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        TextInput::make('amount')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->notIn([0])
                            ->helperText('Use positive numbers to add points or negative numbers to deduct.'),
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $lockedUser = User::whereKey($record->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();
                            $requestedAmount = (int) $data['amount'];
                            $startingBalance = (int) $lockedUser->wallet_balance;
                            $endingBalance = max(0, $startingBalance + $requestedAmount);
                            $actualAdjustment = $endingBalance - $startingBalance;

                            $lockedUser->update(['wallet_balance' => $endingBalance]);

                            WalletTransaction::create([
                                'user_id' => $lockedUser->id,
                                'professor_id' => null,
                                'type' => 'admin_adjustment',
                                'amount' => $actualAdjustment,
                                'description' => $data['description'],
                            ]);
                        });

                        Notification::make()
                            ->title('Wallet points adjusted.')
                            ->success()
                            ->send();
                    }),
                Action::make('toggleAdmin')
                    ->label(fn (User $record): string => $record->role === 'admin' ? 'Demote to Student' : 'Promote to Admin')
                    ->icon('heroicon-o-key')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('promoteOrDemote', $record) ?? false)
                    ->action(function (User $record): void {
                        $newRole = $record->role === 'admin' ? 'user' : 'admin';
                        $record->update(['role' => $newRole]);

                        Notification::make()
                            ->title($newRole === 'admin' ? 'User promoted to admin.' : 'Admin demoted to student.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', '!=', 'super_admin')
            ->where('status', '!=', 'pending')
            ->latest();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
