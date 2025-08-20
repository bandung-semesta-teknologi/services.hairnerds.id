<?php

use App\Models\AnswerBank;
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

describe('answer bank crud api', function () {
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
        $this->question = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        actingAs($this->user);
    });

    it('can get all answer banks with pagination', function () {
        AnswerBank::factory()->count(10)->create(['question_id' => $this->question->id]);

        getJson('/api/answer-banks')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'question_id',
                        'question',
                        'answer',
                        'is_true',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    it('can filter answer banks by question', function () {
        $question2 = Question::factory()->create(['quiz_id' => $this->quiz->id]);

        AnswerBank::factory()->count(3)->create(['question_id' => $this->question->id]);
        AnswerBank::factory()->count(2)->create(['question_id' => $question2->id]);

        getJson("/api/answer-banks?question_id={$this->question->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter answer banks by correctness', function () {
        AnswerBank::factory()->count(3)->correct()->create(['question_id' => $this->question->id]);
        AnswerBank::factory()->count(2)->incorrect()->create(['question_id' => $this->question->id]);

        getJson('/api/answer-banks?is_correct=true')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        getJson('/api/answer-banks?is_correct=false')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('can search answer banks by answer text', function () {
        AnswerBank::factory()->create([
            'question_id' => $this->question->id,
            'answer' => 'Scissors are the primary tool'
        ]);
        AnswerBank::factory()->create([
            'question_id' => $this->question->id,
            'answer' => 'Clippers for short cuts'
        ]);

        getJson('/api/answer-banks?search=Scissors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.answer', 'Scissors are the primary tool');
    });

    it('can create correct answer bank', function () {
        $answerData = [
            'question_id' => $this->question->id,
            'answer' => 'Scissors',
            'is_true' => true
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertCreated()
            ->assertJsonPath('data.answer', 'Scissors')
            ->assertJsonPath('data.is_true', true);

        $this->assertDatabaseHas('answer_banks', [
            'question_id' => $this->question->id,
            'answer' => 'Scissors',
            'is_true' => true
        ]);
    });

    it('can create incorrect answer bank', function () {
        $answerData = [
            'question_id' => $this->question->id,
            'answer' => 'Wrong answer',
            'is_true' => false
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertCreated()
            ->assertJsonPath('data.answer', 'Wrong answer')
            ->assertJsonPath('data.is_true', false);
    });

    it('defaults is_true to false when not provided', function () {
        $answerData = [
            'question_id' => $this->question->id,
            'answer' => 'Default answer'
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertCreated()
            ->assertJsonPath('data.is_true', false);
    });

    it('validates required fields when creating answer bank', function () {
        postJson('/api/answer-banks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id', 'answer']);
    });

    it('validates question_id exists', function () {
        $answerData = [
            'question_id' => 99999,
            'answer' => 'Test answer'
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);
    });

    it('validates answer max length', function () {
        $answerData = [
            'question_id' => $this->question->id,
            'answer' => str_repeat('a', 256)
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answer']);
    });

    it('can get single answer bank with relationships', function () {
        $answerBank = AnswerBank::factory()->create(['question_id' => $this->question->id]);

        getJson("/api/answer-banks/{$answerBank->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $answerBank->id)
            ->assertJsonStructure([
                'data' => [
                    'question'
                ]
            ]);
    });

    it('returns 404 when answer bank not found', function () {
        getJson('/api/answer-banks/99999')
            ->assertNotFound();
    });

    it('can update answer bank', function () {
        $answerBank = AnswerBank::factory()->create(['question_id' => $this->question->id]);

        $updateData = [
            'answer' => 'Updated answer text',
            'is_true' => true
        ];

        putJson("/api/answer-banks/{$answerBank->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.answer', 'Updated answer text')
            ->assertJsonPath('data.is_true', true);

        $this->assertDatabaseHas('answer_banks', [
            'id' => $answerBank->id,
            'answer' => 'Updated answer text',
            'is_true' => true
        ]);
    });

    it('can partially update answer bank', function () {
        $answerBank = AnswerBank::factory()->create([
            'question_id' => $this->question->id,
            'answer' => 'Original answer'
        ]);

        putJson("/api/answer-banks/{$answerBank->id}", ['is_true' => true])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Original answer')
            ->assertJsonPath('data.is_true', true);
    });

    it('can update answer bank question_id', function () {
        $question2 = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $answerBank = AnswerBank::factory()->create(['question_id' => $this->question->id]);

        putJson("/api/answer-banks/{$answerBank->id}", ['question_id' => $question2->id])
            ->assertOk()
            ->assertJsonPath('data.question_id', $question2->id);

        $this->assertDatabaseHas('answer_banks', [
            'id' => $answerBank->id,
            'question_id' => $question2->id
        ]);
    });

    it('validates question_id exists on update', function () {
        $answerBank = AnswerBank::factory()->create(['question_id' => $this->question->id]);

        putJson("/api/answer-banks/{$answerBank->id}", ['question_id' => 99999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);
    });

    it('can delete answer bank', function () {
        $answerBank = AnswerBank::factory()->create(['question_id' => $this->question->id]);

        deleteJson("/api/answer-banks/{$answerBank->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Answer deleted successfully');

        $this->assertSoftDeleted('answer_banks', ['id' => $answerBank->id]);
    });

    it('returns 404 when deleting non-existent answer bank', function () {
        deleteJson('/api/answer-banks/99999')
            ->assertNotFound();
    });

    it('can set custom per_page for pagination', function () {
        AnswerBank::factory()->count(10)->create(['question_id' => $this->question->id]);

        getJson('/api/answer-banks?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('orders answer banks by latest first', function () {
        $older = AnswerBank::factory()->create([
            'question_id' => $this->question->id,
            'created_at' => now()->subDay()
        ]);

        $newer = AnswerBank::factory()->create([
            'question_id' => $this->question->id,
            'created_at' => now()
        ]);

        getJson('/api/answer-banks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('can create multiple answers for single choice question', function () {
        $singleChoiceQuestion = Question::factory()->singleChoice()->create(['quiz_id' => $this->quiz->id]);

        AnswerBank::factory()->correct()->create([
            'question_id' => $singleChoiceQuestion->id,
            'answer' => 'Correct answer'
        ]);

        AnswerBank::factory()->incorrect()->create([
            'question_id' => $singleChoiceQuestion->id,
            'answer' => 'Wrong answer 1'
        ]);

        AnswerBank::factory()->incorrect()->create([
            'question_id' => $singleChoiceQuestion->id,
            'answer' => 'Wrong answer 2'
        ]);

        getJson("/api/answer-banks?question_id={$singleChoiceQuestion->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can create multiple correct answers for multiple choice question', function () {
        $multipleChoiceQuestion = Question::factory()->multipleChoice()->create(['quiz_id' => $this->quiz->id]);

        AnswerBank::factory()->correct()->create([
            'question_id' => $multipleChoiceQuestion->id,
            'answer' => 'Correct answer 1'
        ]);

        AnswerBank::factory()->correct()->create([
            'question_id' => $multipleChoiceQuestion->id,
            'answer' => 'Correct answer 2'
        ]);

        AnswerBank::factory()->incorrect()->create([
            'question_id' => $multipleChoiceQuestion->id,
            'answer' => 'Wrong answer'
        ]);

        getJson("/api/answer-banks?question_id={$multipleChoiceQuestion->id}&is_correct=true")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('can create single answer for fill blank question', function () {
        $fillBlankQuestion = Question::factory()->fillBlank()->create(['quiz_id' => $this->quiz->id]);

        AnswerBank::factory()->correct()->create([
            'question_id' => $fillBlankQuestion->id,
            'answer' => '10'
        ]);

        getJson("/api/answer-banks?question_id={$fillBlankQuestion->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_true', true);
    });

    it('validates boolean values for is_true field', function () {
        $answerData = [
            'question_id' => $this->question->id,
            'answer' => 'Test answer',
            'is_true' => 'not-boolean'
        ];

        postJson('/api/answer-banks', $answerData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_true']);
    });

    it('accepts various boolean formats for is_true field', function () {
        $testCases = [
            ['is_true' => true, 'expected' => true],
            ['is_true' => false, 'expected' => false],
            ['is_true' => 1, 'expected' => true],
            ['is_true' => 0, 'expected' => false],
            ['is_true' => '1', 'expected' => true],
            ['is_true' => '0', 'expected' => false],
        ];

        foreach ($testCases as $index => $testCase) {
            $answerData = [
                'question_id' => $this->question->id,
                'answer' => "Test answer {$index}",
                'is_true' => $testCase['is_true']
            ];

            postJson('/api/answer-banks', $answerData)
                ->assertCreated()
                ->assertJsonPath('data.is_true', $testCase['expected']);
        }
    });
});
