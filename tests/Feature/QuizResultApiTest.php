<?php

use App\Jobs\AutoSubmitQuiz;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\AnswerBank;
use App\Models\QuizResult;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('quiz result api', function () {
    beforeEach(function () {
        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->otherStudent = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->category = Category::factory()->create();

        $this->course = Course::factory()->published()->create();
        $this->course->categories()->attach($this->category->id);
        $this->course->instructors()->attach($this->instructor->id);

        $this->section = Section::factory()->create(['course_id' => $this->course->id]);

        $this->lesson = Lesson::factory()->create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id
        ]);

        $this->quiz = Quiz::factory()->create([
            'section_id' => $this->section->id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->course->id,
            'duration' => '01:00:00'
        ]);

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id
        ]);

        $this->questions = Question::factory()->count(3)->create([
            'quiz_id' => $this->quiz->id
        ]);

        foreach ($this->questions as $question) {
            AnswerBank::factory()->count(3)->create([
                'question_id' => $question->id,
                'is_true' => false
            ]);
            AnswerBank::factory()->create([
                'question_id' => $question->id,
                'is_true' => true
            ]);
        }
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('can start quiz when enrolled', function () {
            Queue::fake();

            postJson('/api/academy/quiz-results', [
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'started_at' => now()
            ])
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz started successfully')
                ->assertJsonPath('data.user_id', $this->student->id);

            Queue::assertPushed(AutoSubmitQuiz::class);
        });

        it('can view own quiz results only', function () {
            QuizResult::factory()->count(2)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            getJson('/api/academy/quiz-results')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('can submit quiz with answers', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => false,
                'started_at' => now()
            ]);

            $answers = [];
            foreach ($this->questions as $question) {
                $correctAnswer = $question->answerBanks()->where('is_true', true)->first();
                $answers[] = [
                    'question_id' => $question->id,
                    'type' => 'single_choice',
                    'answers' => $correctAnswer->id
                ];
            }

            $response = postJson("/api/academy/quiz-results/{$quizResult->id}/submit", [
                'answers' => $answers
            ]);

            if ($response->status() !== 200) {
                dd($response->json());
            }

            $response->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Quiz submitted successfully')
                ->assertJsonPath('data.is_submitted', true);
                });

        it('cannot submit quiz without answers', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => false
            ]);

            postJson("/api/academy/quiz-results/{$quizResult->id}/submit")
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Quiz answers are required');
        });

        it('cannot update submitted quiz', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => true
            ]);

            putJson("/api/academy/quiz-results/{$quizResult->id}", [
                'answered' => 5
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Quiz already submitted');
        });

        it('cannot access other student quiz', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->otherStudent->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            getJson("/api/academy/quiz-results/{$quizResult->id}")
                ->assertForbidden();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('can see all quiz results', function () {
            QuizResult::factory()->count(3)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            getJson('/api/academy/quiz-results')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('can update any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => false
            ]);

            putJson("/api/academy/quiz-results/{$quizResult->id}", [
                'answered' => 15,
                'correct_answers' => 12,
                'total_obtained_marks' => 120
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });

        it('can delete any quiz result', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            deleteJson("/api/academy/quiz-results/{$quizResult->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('can see quiz results from own courses only', function () {
            QuizResult::factory()->count(2)->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ]);

            getJson('/api/academy/quiz-results')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('can update quiz result from own course', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => false
            ]);

            putJson("/api/academy/quiz-results/{$quizResult->id}", [
                'answered' => 10,
                'correct_answers' => 8,
                'total_obtained_marks' => 80
            ])
                ->assertOk()
                ->assertJsonPath('status', 'success');
        });
    });

    describe('guest access', function () {
        it('cannot access quiz results', function () {
            getJson('/api/academy/quiz-results')
                ->assertUnauthorized();
        });

        it('cannot create quiz result', function () {
            postJson('/api/academy/quiz-results', [
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id
            ])
                ->assertUnauthorized();
        });
    });

    describe('auto submit job', function () {
        it('handles quiz result correctly', function () {
            $quizResult = QuizResult::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'lesson_id' => $this->lesson->id,
                'is_submitted' => false,
                'started_at' => now()->subHours(2)
            ]);

            $job = new AutoSubmitQuiz($quizResult->id);
            $job->handle();

            $this->assertDatabaseHas('quiz_results', [
                'id' => $quizResult->id,
                'is_submitted' => true
            ]);
        });
    });
});
