<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $surveys = Survey::withCount('responses')
            ->where('status', 'approved')
            ->where('is_closed', false)
            ->whereDoesntHave('responses', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get()
            ->filter(fn (Survey $survey) => $survey->isAcceptingResponses() && $survey->isEligibleUser($user))
            ->values();

        return view('surveys.index', compact('surveys'));
    }

    public function mySurveys(Request $request): View
    {
        $surveys = $request->user()->surveys()->withCount('responses')->latest()->get();

        return view('inbox.my-surveys', compact('surveys'));
    }

    public function edit(Request $request, Survey $survey): View
    {
        abort_if($survey->user_id !== $request->user()->id, 403);

        return view('surveys.edit', compact('survey'));
    }

    public function show(Request $request, Survey $survey): View|RedirectResponse
    {
        $user = $request->user();

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
                ->with('error', 'You have already answered this survey.');
        }

        return view('surveys.show', compact('survey'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $activeCount = $request->user()->surveys()
            ->where(fn ($query) => $query
                ->where('status', 'pending_review')
                ->orWhere(fn ($subquery) => $subquery
                    ->where('status', 'approved')
                    ->where('is_closed', false)))
            ->count();

        if ($activeCount >= 2) {
            return redirect()->route('inbox.my-surveys')
                ->with('error', 'You can only have up to 2 active or pending surveys at a time.');
        }

        return view('surveys.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $activeCount = $request->user()->surveys()
            ->where(fn ($query) => $query
                ->where('status', 'pending_review')
                ->orWhere(fn ($subquery) => $subquery
                    ->where('status', 'approved')
                    ->where('is_closed', false)))
            ->count();

        if ($activeCount >= 2) {
            return redirect()->route('inbox.my-surveys')
                ->with('error', 'You can only have up to 2 active or pending surveys at a time.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_programs' => ['required', 'array', 'min:1'],
            'target_programs.*' => ['in:BSIT,BTLED,BSENT,ALL'],
            'target_year_levels' => ['required', 'array', 'min:1'],
            'target_year_levels.*' => ['in:1,2,3,4,ALL'],
            'schema_json' => ['required', 'json'],
            'target_respondents' => ['nullable', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date', 'after:now'],
            'shuffle_questions' => ['boolean'],
            'collect_respondent_info' => ['boolean'],
        ]);

        $request->user()->surveys()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_programs' => $validated['target_programs'],
            'target_year_levels' => $validated['target_year_levels'],
            'schema' => json_decode($validated['schema_json'], true),
            'target_respondents' => $validated['target_respondents'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'shuffle_questions' => $validated['shuffle_questions'] ?? false,
            'collect_respondent_info' => $validated['collect_respondent_info'] ?? false,
            'status' => 'pending_review',
            'is_closed' => false,
        ]);

        return redirect()->route('inbox.my-surveys')
            ->with('success', 'Survey submitted for Admin approval!');
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        abort_if($survey->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_programs' => ['required', 'array', 'min:1'],
            'target_programs.*' => ['in:BSIT,BTLED,BSENT,ALL'],
            'target_year_levels' => ['required', 'array', 'min:1'],
            'target_year_levels.*' => ['in:1,2,3,4,ALL'],
            'schema_json' => ['required', 'json'],
            'target_respondents' => ['nullable', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date'],
            'shuffle_questions' => ['boolean'],
            'collect_respondent_info' => ['boolean'],
        ]);

        $survey->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_programs' => $validated['target_programs'],
            'target_year_levels' => $validated['target_year_levels'],
            'schema' => json_decode($validated['schema_json'], true),
            'target_respondents' => $validated['target_respondents'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'shuffle_questions' => $validated['shuffle_questions'] ?? false,
            'collect_respondent_info' => $validated['collect_respondent_info'] ?? false,
        ]);

        return redirect()->route('inbox.my-surveys')
            ->with('success', 'Survey updated and changes are live!');
    }

    public function toggleClose(Request $request, Survey $survey): RedirectResponse
    {
        abort_if($survey->user_id !== $request->user()->id, 403);

        $survey->update(['is_closed' => ! $survey->is_closed]);

        return redirect()->back()->with(
            'success',
            $survey->is_closed ? 'Survey closed.' : 'Survey reopened.',
        );
    }
}
