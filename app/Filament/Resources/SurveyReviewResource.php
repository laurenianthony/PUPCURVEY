<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SurveyReviewResource\Pages\ListSurveyReviews;
use App\Models\Survey;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SurveyReviewResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Survey Approvals';

    protected static ?string $navigationGroup = 'Survey Moderation';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label('Author')
                    ->searchable()
                    ->description(fn (Survey $record): ?string => $record->creator?->student_id),
                TextColumn::make('target_programs')
                    ->badge()
                    ->label('Programs'),
                TextColumn::make('target_year_levels')
                    ->badge()
                    ->label('Year Levels'),
                TextColumn::make('created_at')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->label('Submitted'),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Survey $record): string => $record->title)
                    ->modalContent(fn (Survey $record) => view('filament.survey-preview', [
                        'survey' => $record,
                    ]))
                    ->modalSubmitAction(false),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Survey $record): void {
                        $record->update([
                            'status' => 'approved',
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Survey approved and published!')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Survey $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Survey rejected.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('creator')
            ->where('status', 'pending_review')
            ->latest();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = Survey::where('status', 'pending_review')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveyReviews::route('/'),
        ];
    }
}
