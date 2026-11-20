<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = [
            [
                'name' => 'Education',
                'description' => 'Homework, reading, and studying.',
                'activities' => [
                    ['Complete homework', 'Finish assigned work carefully.', 3, 2],
                    ['Independent study', 'Revise lessons for 20–30 minutes.', 2, 1],
                    ['Exam preparation', 'Review lessons and prepare materials.', 3, 2],
                    ['Exam effort', 'Attempt the exam honestly and diligently.', 3, 0],
                    ['Correct mistakes', 'Review incorrect answers and learn from them.', 2, 1],
                    ['Reading habit', 'Read an appropriate book for 15–20 minutes.', 2, 1],
                    ['Ask for help', 'Seek assistance when a task is difficult.', 1, 0],
                ],
            ],
            [
                'name' => 'Personal Care',
                'description' => 'Personal hygiene, wellness, and healthy routines.',
                'activities' => [
                    ['Morning hygiene', 'Brush teeth, wash face, and maintain cleanliness.', 1, 1],
                    ['Make the bed', 'Arrange bedding after waking.', 1, 1],
                    ['Organize belongings', 'Put books, clothes, and shoes in place.', 1, 1],
                    ['Prepare for school', 'Pack schoolbag and learning materials.', 2, 1],
                    ['Wash personal items', 'Wash own cup, plate, or suitable clothing.', 2, 1],
                    ['Prepare own food', 'Safely prepare a simple snack or meal.', 2, 1],
                    ['Appropriate eating habits', 'Practise hygiene and respectful table manners.', 1, 0],
                ],
            ],
            [
                'name' => 'Household',
                'description' => 'Cleaning, organizing, and helping around the house.',
                'activities' => [
                    ['Complete a daily chore', 'Perform one agreed age-appropriate task.', 2, 1],
                    ['Keep study space clean', 'Arrange desk and learning materials.', 1, 1],
                    ['Help the family', 'Complete an agreed additional task.', 2, 1],
                ],
            ],
            [
                'name' => 'Trust & Character',
                'description' => 'Honesty, integrity, respect, and helping others.',
                'activities' => [
                    ['Keep a promise', 'Fulfil a clear, realistic commitment.', 3, 2],
                    ['Tell the truth', 'Report events honestly, including mistakes.', 4, 2],
                    ['Admit a mistake', 'Take responsibility and help correct it.', 3, 0],
                    ['Respect others', 'Communicate respectfully and listen.', 2, 1],
                    ['Help voluntarily', 'Offer reasonable help to a family member.', 2, 0],
                    ['Solve problems responsibly', 'Try a safe solution or ask for guidance.', 2, 0],
                ],
            ],
            [
                'name' => 'Time Management',
                'description' => 'Punctuality, scheduling, and morning/evening routines.',
                'activities' => [
                    ['Follow the routine', 'Balance study, play, chores, and rest.', 2, 1],
                    ['Prepare for tomorrow', 'Organize clothing, books, and school materials.', 1, 1],
                ],
            ],
            [
                'name' => 'Digital Responsibility',
                'description' => 'Safe, balanced use of devices and screen-time rules.',
                'activities' => [
                    ['Follow screen-time rules', 'Respect agreed device limits and safety rules.', 2, 1],
                ],
            ],
            [
                'name' => 'Financial Literacy',
                'description' => 'Saving points, budgeting, and making wise spending decisions.',
                'activities' => [
                    ['Prepare a budget', 'Plan how available money will be allocated.', 3, 0],
                    ['Record expenses', 'Record purchases and remaining balance.', 2, 1],
                    ['Save toward a goal', 'Make the planned contribution to savings.', 3, 0],
                    ['Compare prices', 'Compare options before a planned purchase.', 2, 0],
                    ['Thoughtful purchases', 'Explain why a purchase is worthwhile.', 2, 0],
                    ['Weekly financial review', 'Explain spending, savings, and lessons learned.', 3, 0],
                    ['Protect money', 'Keep money in an agreed safe place.', 1, 0],
                    ['Voluntary giving', 'Choose an affordable way to help others.', 2, 0],
                ],
            ],
        ];

        foreach ($catalog as $categoryData) {
            $now = now();
            $categoryId = DB::table('categories')
                ->whereNull('family_id')
                ->where('name', $categoryData['name'])
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('categories')->insertGetId([
                    'family_id' => null,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($categoryData['activities'] as [$title, $description, $defaultTp, $deduction]) {
                $exists = DB::table('commitments')
                    ->whereNull('family_id')
                    ->where('category_id', $categoryId)
                    ->where('title', $title)
                    ->exists();

                if (! $exists) {
                    DB::table('commitments')->insert([
                        'family_id' => null,
                        'category_id' => $categoryId,
                        'title' => $title,
                        'description' => $description,
                        'weight_multiplier' => $defaultTp,
                        'default_tp' => $defaultTp,
                        'suggested_deduction' => $deduction,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Keep the shared catalog intact because families may already use it.
    }
};
