<?php

namespace Database\Seeders;

use App\Models\Professor;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MockDataSeeder extends Seeder
{
    public function run(): void
    {
        $students = [];

        foreach ([
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@gmail.com',
                'student_id' => '2024-00101-CL-0',
                'program' => 'BSIT',
                'year_level' => 3,
                'status' => 'active',
                'wallet_balance' => 5,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@gmail.com',
                'student_id' => '2025-00202-CL-0',
                'program' => 'BTLED',
                'year_level' => 2,
                'status' => 'active',
                'wallet_balance' => 3,
            ],
            [
                'name' => 'Carlos Reyes',
                'email' => 'carlos.reyes@gmail.com',
                'student_id' => '2023-00303-CL-0',
                'program' => 'BSENT',
                'year_level' => 4,
                'status' => 'active',
                'wallet_balance' => 2,
            ],
            [
                'name' => 'Angela Mendoza',
                'email' => 'angela.mendoza@gmail.com',
                'student_id' => '2026-00404-CL-0',
                'program' => 'BSIT',
                'year_level' => 1,
                'status' => 'pending',
                'wallet_balance' => 0,
            ],
            [
                'name' => 'Mark Villanueva',
                'email' => 'mark.villanueva@gmail.com',
                'student_id' => '2025-00505-CL-0',
                'program' => 'BSENT',
                'year_level' => 2,
                'status' => 'banned',
                'wallet_balance' => 0,
            ],
        ] as $attributes) {
            $email = $attributes['email'];
            unset($attributes['email']);

            $students[$email] = User::firstOrCreate(
                ['email' => $email],
                [
                    ...$attributes,
                    'password' => Hash::make('password'),
                    'role' => 'student',
                ],
            );
        }

        $professors = collect([
            'Engr. Ricardo Dalisay',
            'Prof. Clarissa Bautista',
            'Prof. Emmanuel Soriano',
            'Prof. Teresa Magbanua',
            'Prof. Josefa Llanes',
        ])->map(function (string $name): Professor {
            $professor = Professor::firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );

            if (! $professor->is_active) {
                $professor->update(['is_active' => true]);
            }

            return $professor;
        });

        $juan = $students['juan.delacruz@gmail.com'];
        $maria = $students['maria.santos@gmail.com'];
        $carlos = $students['carlos.reyes@gmail.com'];

        $survey1Title = 'PUP Calauan Campus Wi-Fi & Laboratory Facilities Feedback';
        $survey1 = Survey::firstOrCreate(
            ['title' => $survey1Title],
            [
                'user_id' => $juan->id,
                'description' => 'Help us evaluate campus internet reliability and computer lab equipment for SY 2026-2027.',
                'target_programs' => ['ALL'],
                'target_year_levels' => ['ALL'],
                'target_respondents' => 50,
                'deadline_at' => null,
                'is_closed' => false,
                'shuffle_questions' => false,
                'collect_respondent_info' => true,
                'status' => 'approved',
                'rejection_reason' => null,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'sec_1',
                            'title' => 'Campus Connectivity',
                            'description' => 'Tell us about your campus Wi-Fi experience.',
                            'questions' => [
                                [
                                    'id' => 'q_1',
                                    'type' => 'multiple_choice',
                                    'label' => 'How often do you use campus Wi-Fi?',
                                    'required' => true,
                                    'options' => ['Every day', 'A few times a week', 'Rarely'],
                                ],
                                [
                                    'id' => 'q_2',
                                    'type' => 'checkboxes',
                                    'label' => 'What connection issues have you experienced?',
                                    'required' => false,
                                    'options' => ['Slow connection', 'Connection drops', 'Cannot connect', 'No issues'],
                                ],
                                [
                                    'id' => 'q_3',
                                    'type' => 'dropdown',
                                    'label' => 'Where do you use campus Wi-Fi most?',
                                    'required' => true,
                                    'options' => ['Library', 'Computer laboratory', 'Classroom', 'Other'],
                                ],
                                [
                                    'id' => 'q_4',
                                    'type' => 'linear_scale',
                                    'label' => 'Rate the reliability of campus Wi-Fi.',
                                    'required' => true,
                                    'min' => 1,
                                    'max' => 5,
                                    'min_label' => 'Very unreliable',
                                    'max_label' => 'Very reliable',
                                ],
                                [
                                    'id' => 'q_5',
                                    'type' => 'multiple_choice_grid',
                                    'label' => 'Rate the following Wi-Fi locations.',
                                    'required' => false,
                                    'rows' => ['Library', 'Computer laboratory'],
                                    'columns' => ['Poor', 'Fair', 'Good'],
                                ],
                            ],
                        ],
                        [
                            'id' => 'sec_2',
                            'title' => 'Laboratory Facilities',
                            'description' => 'Help us improve the campus computer laboratories.',
                            'questions' => [
                                [
                                    'id' => 'q_6',
                                    'type' => 'checkbox_grid',
                                    'label' => 'Which equipment needs improvement at each location?',
                                    'required' => false,
                                    'rows' => ['Computer laboratory 1', 'Computer laboratory 2'],
                                    'columns' => ['Computers', 'Internet', 'Chairs'],
                                ],
                                [
                                    'id' => 'q_7',
                                    'type' => 'short_answer',
                                    'label' => 'What is one improvement you would prioritize?',
                                    'required' => true,
                                ],
                                [
                                    'id' => 'q_8',
                                    'type' => 'paragraph',
                                    'label' => 'Describe any other feedback about the facilities.',
                                    'required' => false,
                                ],
                                [
                                    'id' => 'q_9',
                                    'type' => 'date',
                                    'label' => 'When did you last use a campus computer laboratory?',
                                    'required' => false,
                                ],
                                [
                                    'id' => 'q_10',
                                    'type' => 'time',
                                    'label' => 'What time do you usually use the laboratory?',
                                    'required' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $survey2Title = 'BSIT Capstone Project Readiness & Tech Stack Survey';
        $survey2 = Survey::firstOrCreate(
            ['title' => $survey2Title],
            [
                'user_id' => $juan->id,
                'description' => 'Assess your capstone project readiness and preferred technologies.',
                'target_programs' => ['BSIT'],
                'target_year_levels' => [3, 4],
                'target_respondents' => 30,
                'deadline_at' => null,
                'is_closed' => false,
                'shuffle_questions' => true,
                'collect_respondent_info' => false,
                'status' => 'approved',
                'rejection_reason' => null,
                'schema' => [
                    'sections' => [[
                        'id' => 'capstone_section_1',
                        'title' => 'Capstone Readiness',
                        'description' => 'Share your current project plans.',
                        'questions' => [
                            [
                                'id' => 'capstone_q_1',
                                'type' => 'multiple_choice',
                                'label' => 'How ready are you to begin your capstone project?',
                                'required' => true,
                                'options' => ['Ready', 'Need more preparation', 'Not sure'],
                            ],
                            [
                                'id' => 'capstone_q_2',
                                'type' => 'checkboxes',
                                'label' => 'Which technologies are you considering?',
                                'required' => false,
                                'options' => ['Laravel', 'React', 'Flutter', 'Python', 'Other'],
                            ],
                            [
                                'id' => 'capstone_q_3',
                                'type' => 'short_answer',
                                'label' => 'What is your proposed project topic?',
                                'required' => true,
                            ],
                        ],
                    ]],
                ],
            ],
        );

        $survey3Title = 'Student Council Intramurals 2026 T-Shirt Sizing';
        $survey3 = Survey::firstOrCreate(
            ['title' => $survey3Title],
            [
                'user_id' => $juan->id,
                'description' => 'Choose your preferred intramurals shirt size.',
                'target_programs' => ['ALL'],
                'target_year_levels' => ['ALL'],
                'target_respondents' => null,
                'deadline_at' => null,
                'is_closed' => true,
                'shuffle_questions' => false,
                'collect_respondent_info' => false,
                'status' => 'approved',
                'rejection_reason' => null,
                'schema' => [
                    'sections' => [[
                        'id' => 'sizing_section_1',
                        'title' => 'T-Shirt Size',
                        'description' => 'Select one size.',
                        'questions' => [[
                            'id' => 'q_1',
                            'type' => 'dropdown',
                            'label' => 'What shirt size do you need?',
                            'required' => true,
                            'options' => ['XS', 'S', 'M', 'L', 'XL'],
                        ]],
                    ]],
                ],
            ],
        );

        $this->seedResponseAndWalletTransaction(
            $survey1,
            $maria,
            [
                'q_1' => 'Every day',
                'q_2' => ['Slow connection', 'Connection drops'],
                'q_3' => 'Library',
                'q_4' => 3,
                'q_5' => [
                    'Library' => 'Good',
                    'Computer laboratory' => 'Fair',
                ],
                'q_6' => [
                    'Computer laboratory 1' => ['Internet'],
                    'Computer laboratory 2' => ['Computers', 'Chairs'],
                ],
                'q_7' => 'Improve the Wi-Fi access points.',
                'q_8' => 'The laboratory would benefit from newer computers and more charging outlets.',
                'q_9' => '2026-11-10',
                'q_10' => '14:30',
            ],
        );

        $this->seedResponseAndWalletTransaction(
            $survey1,
            $carlos,
            [
                'q_1' => 'A few times a week',
                'q_2' => ['No issues'],
                'q_3' => 'Computer laboratory',
                'q_4' => 4,
                'q_5' => [
                    'Library' => 'Fair',
                    'Computer laboratory' => 'Good',
                ],
                'q_6' => [
                    'Computer laboratory 1' => ['Computers'],
                    'Computer laboratory 2' => ['Internet'],
                ],
                'q_7' => 'Upgrade the older laboratory computers.',
                'q_8' => 'The facilities are useful, especially during project work.',
                'q_9' => '2026-11-12',
                'q_10' => '09:15',
            ],
        );

        $this->seedResponseAndWalletTransaction(
            $survey3,
            $juan,
            ['q_1' => 'L'],
        );

        $professor = $professors->first();

        WalletTransaction::firstOrCreate(
            [
                'user_id' => $juan->id,
                'professor_id' => $professor->id,
                'type' => 'prof_redemption',
                'description' => "Redeemed for {$professor->name}",
            ],
            ['amount' => -2],
        );
    }

    private function seedResponseAndWalletTransaction(Survey $survey, User $user, array $answers): void
    {
        Response::firstOrCreate(
            [
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ],
            ['answers' => $answers],
        );

        WalletTransaction::firstOrCreate(
            [
                'user_id' => $user->id,
                'professor_id' => null,
                'type' => 'survey_answer',
                'description' => "Answered: {$survey->title}",
            ],
            ['amount' => 1],
        );
    }
}
