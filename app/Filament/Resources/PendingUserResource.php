<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingUserResource\Pages\ListPendingUsers;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class PendingUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Pending Verifications';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('program')
                    ->badge()
                    ->sortable(),
                TextColumn::make('year_level')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->label('Registered'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update(['status' => 'active']);

                        Notification::make()
                            ->title('User approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Reject and permanently delete this unverified registration?')
                    ->action(function (User $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('Registration rejected')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'pending')
            ->whereIn('role', ['user', 'student']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = User::where('status', 'pending')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPendingUsers::route('/'),
        ];
    }
}
