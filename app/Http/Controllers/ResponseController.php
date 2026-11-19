<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseController extends Controller
{
    public function store(Request $request, Survey $survey): RedirectResponse
    {
        $user = $request->user();

        if ($survey->user_id === $user->id) {
            return redirect()->route('surveys.index')
                ->with('error', 'You cannot answer your own survey.');
        }

        if (! $survey->isAcceptingResponses()) {
            return redirect()->route('surveys.index')
                ->with('error', 'This survey is closed or no longer accepting responses.');
        }

        if (! $survey->isEligibleUser($user)) {
            return redirect()->route('surveys.index')
                ->with('error', 'You do not meet the target Program or Year Level requirements for this survey.');
        }

        if ($survey->responses()->where('user_id', $user->id)->exists()) {
            return redirect()->route('surveys.index')
                ->with('error', 'You have already submitted a response to this survey.');
        }

        $request->validate([
            'answers_json' => ['required', 'json'],
        ]);

        $answers = json_decode($request->answers_json, true);

        if (! is_array($answers)) {
            return back()->withInput()->withErrors([
                'answers' => 'The submitted answers must be a JSON object or array.',
            ]);
        }

        foreach ($survey->schema['sections'] ?? [] as $section) {
            foreach ($section['questions'] ?? [] as $question) {
                if (! empty($question['required']) && blank($answers[$question['id']] ?? null)) {
                    return back()->withInput()->withErrors([
                        'answers' => "Please answer all required questions ({$question['label']}).",
                    ]);
                }
            }
        }

        try {
            DB::transaction(function () use ($survey, $user, $answers) {
                Response::create([
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                    'answers' => $answers,
                ]);

                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                $lockedUser->increment('wallet_balance', 1);

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'professor_id' => null,
                    'type' => 'survey_answer',
                    'amount' => 1,
                    'description' => "Answered: {$survey->title}",
                ]);

                if ($survey->target_respondents !== null
                    && $survey->responses()->count() >= $survey->target_respondents) {
                    $survey->update(['is_closed' => true]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return redirect()->route('surveys.index')
                ->with('error', 'You have already submitted a response to this survey.');
        }

        return redirect()->route('surveys.index')
            ->with('success', '+1 pt added to your Wallet!');
    }

    public function results(Request $request, Survey $survey): View
    {
        abort_if($survey->user_id !== $request->user()->id, 403);

        $responses = $survey->responses()->with('respondent')->latest()->get();
        $questions = $this->flattenQuestions($survey);
        $summary = [];

        foreach ($questions as $question) {
            $questionId = $question['id'];
            $type = $question['type'] ?? '';
            $answeredResponses = $responses->filter(function (Response $response) use ($questionId) {
                return ! blank($response->answers[$questionId] ?? null);
            });

            $questionSummary = [
                'id' => $questionId,
                'label' => $question['label'] ?? '',
                'type' => $type,
                'total_answered' => $answeredResponses->count(),
            ];

            if (in_array($type, ['multiple_choice', 'checkboxes', 'dropdown'], true)) {
                $counts = [];
                $choiceLabels = $this->choiceLabels($question['options'] ?? []);

                foreach ($choiceLabels as $label) {
                    $counts[$label] = 0;
                }

                foreach ($answeredResponses as $response) {
                    $answer = $response->answers[$questionId];
                    $selectedOptions = is_array($answer) ? $answer : [$answer];

                    foreach ($selectedOptions as $selectedOption) {
                        $label = $choiceLabels[(string) $selectedOption] ?? (string) $selectedOption;
                        $counts[$label] = ($counts[$label] ?? 0) + 1;
                    }
                }

                $questionSummary['counts'] = [];
                foreach ($counts as $option => $count) {
                    $questionSummary['counts'][$option] = [
                        'count' => $count,
                        'percentage' => $questionSummary['total_answered'] > 0
                            ? round(($count / $questionSummary['total_answered']) * 100, 2)
                            : 0,
                    ];
                }
            } elseif ($type === 'linear_scale') {
                $counts = [];
                $minimum = $question['min'] ?? $question['scale_min'] ?? null;
                $maximum = $question['max'] ?? $question['scale_max'] ?? null;

                if (is_numeric($minimum) && is_numeric($maximum) && $maximum >= $minimum) {
                    for ($value = (int) $minimum; $value <= (int) $maximum; $value++) {
                        $counts[(string) $value] = 0;
                    }
                }

                $sum = 0;
                $numericAnswerCount = 0;
                foreach ($answeredResponses as $response) {
                    $answer = $response->answers[$questionId];

                    if (! is_numeric($answer)) {
                        continue;
                    }

                    $value = (string) $answer;
                    $counts[$value] = ($counts[$value] ?? 0) + 1;
                    $sum += (float) $answer;
                    $numericAnswerCount++;
                }

                $questionSummary['counts'] = $counts;
                $questionSummary['weighted_mean'] = $numericAnswerCount > 0
                    ? round($sum / $numericAnswerCount, 2)
                    : null;
            } elseif (in_array($type, ['multiple_choice_grid', 'checkbox_grid'], true)) {
                $rowLabels = $this->choiceLabels($question['rows'] ?? []);
                $columnLabels = $this->choiceLabels($question['columns'] ?? $question['options'] ?? []);
                $counts = [];

                foreach ($rowLabels as $rowLabel) {
                    foreach ($columnLabels as $columnLabel) {
                        $counts[$rowLabel][$columnLabel] = 0;
                    }
                }

                foreach ($answeredResponses as $response) {
                    $answer = $response->answers[$questionId];

                    if (! is_array($answer)) {
                        continue;
                    }

                    foreach ($rowLabels as $rowValue => $rowLabel) {
                        $rowAnswer = $answer[$rowValue] ?? $answer[$rowLabel] ?? null;
                        $selectedColumns = is_array($rowAnswer) ? $rowAnswer : [$rowAnswer];

                        foreach ($selectedColumns as $selectedColumn) {
                            if (blank($selectedColumn)) {
                                continue;
                            }

                            $columnLabel = $columnLabels[(string) $selectedColumn] ?? (string) $selectedColumn;
                            $counts[$rowLabel][$columnLabel] = ($counts[$rowLabel][$columnLabel] ?? 0) + 1;
                        }
                    }
                }

                $questionSummary['counts'] = $counts;
            } elseif (in_array($type, ['short_answer', 'paragraph', 'date', 'time'], true)) {
                $questionSummary['entries'] = $answeredResponses
                    ->map(fn (Response $response) => (string) $response->answers[$questionId])
                    ->values()
                    ->all();
            }

            $summary[$questionId] = $questionSummary;
        }

        $individualResponses = $responses->values()->map(function (Response $response, int $index) use ($survey) {
            $respondent = $response->respondent;
            $identity = $survey->collect_respondent_info
                ? [
                    'name' => $respondent?->name,
                    'student_id' => $respondent?->student_id,
                ]
                : [
                    'name' => 'Respondent #'.($index + 1),
                ];

            return [
                ...$identity,
                'program' => $respondent?->program,
                'year_level' => $respondent?->year_level,
                'submitted_at' => $response->created_at,
                'answers' => $response->answers,
            ];
        });

        return view('inbox.survey-results', compact('survey', 'summary', 'individualResponses'));
    }

    public function exportCsv(Request $request, Survey $survey): StreamedResponse
    {
        abort_if($survey->user_id !== $request->user()->id, 403);

        return response()->streamDownload(function () use ($survey) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $questions = $this->flattenQuestions($survey);
            $header = ['Submitted At'];

            if ($survey->collect_respondent_info) {
                $header = [...$header, 'Full Name', 'Student ID'];
            }

            $header = [...$header, 'Program', 'Year Level'];
            foreach ($questions as $question) {
                $header[] = $question['label'] ?? '';
            }

            fputcsv($handle, $header);

            $responses = $survey->responses()->with('respondent')->latest()->get();

            foreach ($responses as $response) {
                $respondent = $response->respondent;
                $row = [$response->created_at?->format('Y-m-d H:i:s') ?? ''];

                if ($survey->collect_respondent_info) {
                    $row[] = $respondent?->name ?? '';
                    $row[] = $respondent?->student_id ?? '';
                }

                $row[] = $respondent?->program ?? '';
                $row[] = $respondent?->year_level ?? '';

                foreach ($questions as $question) {
                    $answer = $response->answers[$question['id']] ?? null;

                    if (is_array($answer)) {
                        $answer = json_encode($answer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } elseif (is_bool($answer)) {
                        $answer = $answer ? 'true' : 'false';
                    } elseif (! is_scalar($answer)) {
                        $answer = '';
                    }

                    $row[] = $answer;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, "pupulse-survey-{$survey->id}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flattenQuestions(Survey $survey): array
    {
        return collect($survey->schema['sections'] ?? [])
            ->flatMap(fn (array $section) => $section['questions'] ?? [])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function choiceLabels(array $choices): array
    {
        $labels = [];

        foreach ($choices as $choice) {
            if (is_array($choice)) {
                $value = $choice['value'] ?? $choice['id'] ?? $choice['label'] ?? '';
                $label = $choice['label'] ?? $choice['text'] ?? $value;
            } else {
                $value = $choice;
                $label = $choice;
            }

            if ($value !== '' && $label !== '') {
                $labels[(string) $value] = (string) $label;
            }
        }

        return $labels;
    }
}
