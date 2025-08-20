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
