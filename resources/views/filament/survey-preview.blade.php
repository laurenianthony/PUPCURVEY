<div class="space-y-6">
    <section class="space-y-2">
        <h2 class="text-base font-semibold">Survey details</h2>
        <p class="whitespace-pre-line text-sm text-gray-600">{{ $survey->description ?: 'No description provided.' }}</p>

        <dl class="grid gap-2 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-medium">Target programs</dt>
                <dd>{{ implode(', ', $survey->target_programs ?? []) }}</dd>
            </div>
            <div>
                <dt class="font-medium">Target year levels</dt>
                <dd>{{ implode(', ', $survey->target_year_levels ?? []) }}</dd>
            </div>
            <div>
                <dt class="font-medium">Target respondents</dt>
                <dd>{{ $survey->target_respondents ?? 'No limit' }}</dd>
            </div>
            <div>
                <dt class="font-medium">Deadline</dt>
                <dd>{{ $survey->deadline_at?->format('M d, Y h:i A') ?? 'No deadline' }}</dd>
            </div>
        </dl>
    </section>

    <section class="space-y-4">
        <h2 class="text-base font-semibold">Questions</h2>

        @forelse ($survey->schema['sections'] ?? [] as $sectionIndex => $section)
            <div class="space-y-3 rounded-lg border border-gray-200 p-4">
                <h3 class="font-medium">{{ $section['title'] ?? $section['label'] ?? 'Section '.($sectionIndex + 1) }}</h3>

                @forelse ($section['questions'] ?? [] as $questionIndex => $question)
                    <article class="space-y-1 rounded-md bg-gray-50 p-3">
                        <p class="font-medium">{{ $question['label'] ?? 'Question '.($questionIndex + 1) }}</p>
                        <p class="text-sm text-gray-600">
                            Type: {{ $question['type'] ?? 'Unknown' }}
                            · {{ ! empty($question['required']) ? 'Required' : 'Optional' }}
                        </p>

                        @if (! empty($question['options']))
                            <ul class="list-inside list-disc text-sm text-gray-600">
                                @foreach ($question['options'] as $option)
                                    <li>{{ is_array($option) ? ($option['label'] ?? $option['value'] ?? json_encode($option, JSON_UNESCAPED_UNICODE)) : $option }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-gray-500">This section has no questions.</p>
                @endforelse
            </div>
        @empty
            <p class="text-sm text-gray-500">This survey has no sections.</p>
        @endforelse
    </section>
</div>
