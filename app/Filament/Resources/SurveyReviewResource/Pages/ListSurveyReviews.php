<?php

namespace App\Filament\Resources\SurveyReviewResource\Pages;

use App\Filament\Resources\SurveyReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListSurveyReviews extends ListRecords
{
    protected static string $resource = SurveyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
