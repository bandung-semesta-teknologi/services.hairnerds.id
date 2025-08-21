<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('question crud api', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create();

        $this->course = Course::factory()->create();
        $this->section = Section::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);
        $this->quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        actingAs($this->user);
    });

    it('can get all questions with pagination', function () {
        Question::factory()->count(10)->create(['quiz_id' => $this->quiz->id]);

        getJson('/api/questions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quiz_id',
                        'quiz',
                        'type',
                        'question',
                        'score',
                        'answer_banks',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('can filter questions by quiz', function () {
        $quiz2 = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id
        ]);

        Question::factory()->count(3)->create(['quiz_id' => $this->quiz->id]);
        Question::factory()->count(2)->create(['quiz_id' => $quiz2->id]);

        getJson("/api/questions?quiz_id={$this->quiz->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter questions by type', function () {
        Question::factory()->count(2)->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice'
        ]);
        Question::factory()->count(3)->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice'
        ]);
        Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'fill_blank'
        ]);

        getJson('/api/questions?type=single_choice')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        getJson('/api/questions?type=multiple_choice')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        getJson('/api/questions?type=fill_blank')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('can search questions by question text', function () {
        Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'question' => 'What is the best programming language?'
        ]);
        Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'question' => 'How to create a database?'
        ]);

        getJson('/api/questions?search=programming')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question', 'What is the best programming language?');
    });

    it('can create single choice question', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'What is the most important barbering tool?',
            'score' => 10
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'single_choice')
            ->assertJsonPath('data.question', 'What is the most important barbering tool?')
            ->assertJsonPath('data.score', 10);

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'What is the most important barbering tool?'
        ]);
    });

    it('can create multiple choice question', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'question' => 'Which are basic barbering tools?',
            'score' => 15
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'multiple_choice')
            ->assertJsonPath('data.score', 15);
    });

    it('can create fill blank question', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'fill_blank',
            'question' => 'The proper disinfection time is _____ minutes.',
            'score' => 5
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'fill_blank')
            ->assertJsonPath('data.question', 'The proper disinfection time is _____ minutes.');
    });

    it('validates required fields when creating question', function () {
        postJson('/api/questions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quiz_id', 'type', 'question']);
    });

    it('validates question type enum values', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'invalid_type',
            'question' => 'Test question'
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('validates quiz_id exists', function () {
        $questionData = [
            'quiz_id' => 99999,
            'type' => 'single_choice',
            'question' => 'Test question'
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quiz_id']);
    });

    it('can create question with answers in bulk', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'question' => 'Which are basic barbering tools?',
            'score' => 15,
            'answers' => [
                ['answer' => 'Scissors', 'is_true' => true],
                ['answer' => 'Comb', 'is_true' => true],
                ['answer' => 'Phone', 'is_true' => false],
                ['answer' => 'Computer', 'is_true' => false]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'multiple_choice')
            ->assertJsonPath('data.question', 'Which are basic barbering tools?')
            ->assertJsonPath('data.score', 15)
            ->assertJsonCount(4, 'data.answer_banks');

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'question' => 'Which are basic barbering tools?'
        ]);

        $this->assertDatabaseHas('answer_banks', [
            'answer' => 'Scissors',
            'is_true' => true
        ]);

        $this->assertDatabaseHas('answer_banks', [
            'answer' => 'Phone',
            'is_true' => false
        ]);
    });

    it('can create single choice question with answers', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'What is the most important barbering tool?',
            'score' => 10,
            'answers' => [
                ['answer' => 'Scissors', 'is_true' => true],
                ['answer' => 'Comb', 'is_true' => false],
                ['answer' => 'Razor', 'is_true' => false],
                ['answer' => 'Clippers', 'is_true' => false]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'single_choice')
            ->assertJsonCount(4, 'data.answer_banks');
    });

    it('can create fill blank question with single answer', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'fill_blank',
            'question' => 'The proper disinfection time is _____ minutes.',
            'score' => 5,
            'answers' => [
                ['answer' => '10', 'is_true' => true]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.type', 'fill_blank')
            ->assertJsonCount(1, 'data.answer_banks')
            ->assertJsonPath('data.answer_banks.0.answer', '10')
            ->assertJsonPath('data.answer_banks.0.is_true', true);
    });

    it('validates answers array structure when provided', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => 'Valid answer'],
                ['is_true' => true]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.is_true', 'answers.1.answer']);
    });

    it('validates answer text is required when answers provided', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => '', 'is_true' => true]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.answer']);
    });

    it('validates is_true is required when answers provided', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => 'Test answer']
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.is_true']);
    });

    it('validates answers array is not empty when provided', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => []
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers']);
    });

    it('validates answer text length', function () {
        $longAnswer = str_repeat('a', 256);

        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => $longAnswer, 'is_true' => true]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.answer']);
    });

    it('can create question without answers (backward compatibility)', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question without answers',
            'score' => 10
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.question', 'Test question without answers')
            ->assertJsonCount(0, 'data.answer_banks');

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $this->quiz->id,
            'question' => 'Test question without answers'
        ]);
    });

    it('creates question and answers atomically', function () {
        $questionData = [
            'quiz_id' => 99999,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => 'Test answer', 'is_true' => true]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable();

        $this->assertDatabaseMissing('questions', [
            'question' => 'Test question'
        ]);

        $this->assertDatabaseMissing('answer_banks', [
            'answer' => 'Test answer'
        ]);
    });

    it('can handle multiple correct answers for multiple choice', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'question' => 'Which are programming languages?',
            'answers' => [
                ['answer' => 'PHP', 'is_true' => true],
                ['answer' => 'JavaScript', 'is_true' => true],
                ['answer' => 'HTML', 'is_true' => false],
                ['answer' => 'CSS', 'is_true' => false]
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonCount(4, 'data.answer_banks');

        $question = Question::latest()->first();

        expect($question->answerBanks()->where('is_true', true)->count())->toBe(2);
        expect($question->answerBanks()->where('is_true', false)->count())->toBe(2);
    });

    it('validates is_true as boolean type', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => 'Test answer', 'is_true' => 'not_boolean']
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.is_true']);
    });

    it('accepts boolean values as string for is_true', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'answers' => [
                ['answer' => 'Test answer', 'is_true' => '1']
            ]
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.answer_banks.0.is_true', true);
    });

    it('can get single question with relationships', function () {
        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        getJson("/api/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $question->id)
            ->assertJsonStructure([
                'data' => [
                    'quiz',
                    'answer_banks'
                ]
            ]);
    });

    it('returns 404 when question not found', function () {
        getJson('/api/questions/99999')
            ->assertNotFound();
    });

    it('can update question', function () {
        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        $updateData = [
            'type' => 'multiple_choice',
            'question' => 'Updated question text',
            'score' => 20
        ];

        putJson("/api/questions/{$question->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.type', 'multiple_choice')
            ->assertJsonPath('data.question', 'Updated question text')
            ->assertJsonPath('data.score', 20);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'type' => 'multiple_choice',
            'question' => 'Updated question text',
            'score' => 20
        ]);
    });

    it('can partially update question', function () {
        $question = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'question' => 'Original question'
        ]);

        putJson("/api/questions/{$question->id}", ['score' => 25])
            ->assertOk()
            ->assertJsonPath('data.question', 'Original question')
            ->assertJsonPath('data.score', 25);
    });

    it('validates type enum on update', function () {
        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        putJson("/api/questions/{$question->id}", ['type' => 'invalid_type'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('can delete question', function () {
        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        deleteJson("/api/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Question deleted successfully');

        $this->assertSoftDeleted('questions', ['id' => $question->id]);
    });

    it('returns 404 when deleting non-existent question', function () {
        deleteJson('/api/questions/99999')
            ->assertNotFound();
    });

    it('can set custom per_page for pagination', function () {
        Question::factory()->count(10)->create(['quiz_id' => $this->quiz->id]);

        getJson('/api/questions?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('orders questions by latest first', function () {
        $older = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'created_at' => now()->subDay()
        ]);

        $newer = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'created_at' => now()
        ]);

        getJson('/api/questions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('defaults score to 0 when not provided', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question without score'
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.score', 0);
    });

    it('accepts valid score values', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'score' => 50
        ];

        postJson('/api/questions', $questionData)
            ->assertCreated()
            ->assertJsonPath('data.score', 50);
    });

    it('validates score is not negative', function () {
        $questionData = [
            'quiz_id' => $this->quiz->id,
            'type' => 'single_choice',
            'question' => 'Test question',
            'score' => -5
        ];

        postJson('/api/questions', $questionData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);
    });
});
