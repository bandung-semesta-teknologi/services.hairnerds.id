<?php

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('lesson crud api', function () {
    beforeEach(function () {
        Storage::fake('public');

        $this->admin = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'admin']);

        $this->instructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->otherInstructor = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'instructor']);

        $this->student = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->otherStudent = User::factory()
            ->has(UserCredential::factory()->emailCredential())
            ->create(['role' => 'student']);

        $this->categories = Category::factory()->count(2)->create();

        $this->publishedCourse = Course::factory()->published()->create();
        $this->publishedCourse->categories()->attach($this->categories->first()->id);
        $this->publishedCourse->instructors()->attach($this->instructor->id);

        $this->draftCourse = Course::factory()->draft()->create();
        $this->draftCourse->categories()->attach($this->categories->last()->id);
        $this->draftCourse->instructors()->attach($this->instructor->id);

        $this->otherCourse = Course::factory()->published()->create();
        $this->otherCourse->categories()->attach($this->categories->first()->id);
        $this->otherCourse->instructors()->attach($this->otherInstructor->id);

        $this->publishedSection = Section::factory()->create(['course_id' => $this->publishedCourse->id]);
        $this->draftSection = Section::factory()->create(['course_id' => $this->draftCourse->id]);
        $this->otherSection = Section::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id
        ]);
    });

    describe('guest access (forbidden)', function () {
        it('guest user cannot view lessons', function () {
            getJson('/api/lessons')
                ->assertUnauthorized();
        });

        it('guest user cannot view single lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertUnauthorized();
        });

        it('guest user cannot create lesson', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Introduction Video',
                'url' => 'https://youtube.com/watch?v=example'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot update lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            postJson("/api/lessons/{$lesson->id}", [
                'title' => 'Updated title'
            ])
                ->assertUnauthorized();
        });

        it('guest user cannot delete lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertUnauthorized();
        });
    });

    describe('admin access', function () {
        beforeEach(function () {
            actingAs($this->admin);
        });

        it('admin can see all lessons from all courses', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(7, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'section_id',
                            'section',
                            'course_id',
                            'course',
                            'sequence',
                            'type',
                            'title',
                            'url',
                            'summary',
                            'datetime',
                            'attachments',
                            'attachments_count',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]);
        });

        it('admin can create new lesson', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Introduction to Laravel',
                'url' => 'https://youtube.com/watch?v=example',
                'summary' => 'Basic introduction to Laravel framework'
            ];

            postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson created successfully')
                ->assertJsonPath('data.title', 'Introduction to Laravel')
                ->assertJsonPath('data.type', 'youtube')
                ->assertJsonPath('data.sequence', 1);

            $this->assertDatabaseHas('lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'title' => 'Introduction to Laravel',
                'type' => 'youtube'
            ]);
        });

        it('admin can create document lesson with attachments', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'document',
                'title' => 'Fade Techniques Manual',
                'summary' => 'Complete guide to fade techniques',
                'attachment_types' => ['document', 'youtube'],
                'attachment_titles' => ['Fade Manual PDF', 'Demo Video'],
                'attachment_files' => [UploadedFile::fake()->create('manual.pdf', 1024)],
                'attachment_urls' => [null, 'https://youtube.com/watch?v=demo']
            ];

            $response = postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('data.attachments_count', 2);

            $lesson = Lesson::latest()->first();
            expect($lesson->attachments)->toHaveCount(2);
            Storage::disk('public')->assertExists($lesson->attachments->first()->url);
        });

        it('validates required attachments for document type', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'document',
                'title' => 'Test Document Lesson',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['attachment_types', 'attachment_titles', 'attachment_files']);
        });

        it('validates required attachments for audio type', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'audio',
                'title' => 'Test Audio Lesson',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['attachment_types', 'attachment_titles', 'attachment_files']);
        });

        it('validates file size for attachments', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'document',
                'title' => 'Test Document',
                'attachment_types' => ['document'],
                'attachment_titles' => ['Large File'],
                'attachment_files' => [UploadedFile::fake()->create('large.pdf', 11000)]
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['attachment_files.0']);
        });

        it('validates file type for document attachments', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'document',
                'title' => 'Test Document',
                'attachment_types' => ['document'],
                'attachment_titles' => ['Invalid File'],
                'attachment_files' => [UploadedFile::fake()->create('file.exe', 1024)]
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['attachment_files.0']);
        });

        it('admin can update any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            $updateData = [
                'title' => 'Updated Lesson Title',
                'summary' => 'Updated lesson summary'
            ];

            postJson("/api/lessons/{$lesson->id}", $updateData)
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson updated successfully')
                ->assertJsonPath('data.title', 'Updated Lesson Title')
                ->assertJsonPath('data.summary', 'Updated lesson summary');

            $this->assertDatabaseHas('lessons', [
                'id' => $lesson->id,
                'title' => 'Updated Lesson Title',
                'summary' => 'Updated lesson summary'
            ]);
        });

        it('admin can add attachments when updating lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'type' => 'youtube'
            ]);

            $file = UploadedFile::fake()->create('material.pdf', 1024);

            postJson('/api/attachments', [
                'lesson_id' => $lesson->id,
                'type' => 'document',
                'title' => 'Additional Material',
                'file' => $file
            ])
                ->assertCreated();

            $lesson->refresh();
            expect($lesson->attachments)->toHaveCount(1);
            expect($lesson->attachments->first()->title)->toBe('Additional Material');
        });

        it('admin can delete any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson deleted successfully');

            $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
        });

        it('admin can view any lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });
    });

    describe('instructor access', function () {
        beforeEach(function () {
            actingAs($this->instructor);
        });

        it('instructor can see lessons only from their own courses', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('instructor can create lesson for their own course', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Course Materials Video',
                'url' => 'https://youtube.com/watch?v=example123'
            ];

            postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('status', 'success');
        });

        it('instructor can create audio lesson with attachments', function () {
            $lessonData = [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'audio',
                'title' => 'Customer Service Podcast',
                'attachment_types' => ['audio'],
                'attachment_titles' => ['Podcast Episode 1'],
                'attachment_files' => [UploadedFile::fake()->create('podcast.mp3', 2048)]
            ];

            postJson('/api/lessons', $lessonData)
                ->assertCreated()
                ->assertJsonPath('data.attachments_count', 1);
        });

        it('instructor can update lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            postJson("/api/lessons/{$lesson->id}", [
                'title' => 'Instructor Updated Lesson',
                'summary' => 'Updated by instructor'
            ])
                ->assertCreated()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson updated successfully')
                ->assertJsonPath('data.title', 'Instructor Updated Lesson');
        });

        it('instructor can delete lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('message', 'Lesson deleted successfully');

            $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
        });

        it('instructor can view lesson from their own course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });

        it('instructor cannot update lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            postJson("/api/lessons/{$lesson->id}", [
                'title' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('instructor cannot delete lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('instructor cannot view lesson from other instructor course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });
    });

    describe('student access', function () {
        beforeEach(function () {
            actingAs($this->student);
        });

        it('student can view lessons from enrolled published courses only', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);
            Lesson::factory()->count(2)->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson('/api/lessons')
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can view lesson with attachments', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            Attachment::factory()->count(2)->create([
                'lesson_id' => $lesson->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id)
                ->assertJsonPath('data.attachments_count', 2)
                ->assertJsonStructure([
                    'data' => [
                        'attachments' => [
                            '*' => ['id', 'type', 'title', 'url', 'full_url']
                        ]
                    ]
                ]);
        });

        it('student can view single lesson from enrolled published course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $lesson->id);
        });

        it('student cannot view lesson from non-enrolled course', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->otherSection->id,
                'course_id' => $this->otherCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('student cannot view lesson from draft course even if enrolled', function () {
            Enrollment::factory()->create([
                'user_id' => $this->student->id,
                'course_id' => $this->draftCourse->id
            ]);

            $lesson = Lesson::factory()->create([
                'section_id' => $this->draftSection->id,
                'course_id' => $this->draftCourse->id
            ]);

            getJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('student can filter lessons by enrolled course', function () {
            Lesson::factory()->count(3)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons?course_id={$this->publishedCourse->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('student can filter lessons by section', function () {
            Lesson::factory()->count(2)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson("/api/lessons?section_id={$this->publishedSection->id}")
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('student cannot create lesson', function () {
            postJson('/api/lessons', [
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id,
                'sequence' => 1,
                'type' => 'youtube',
                'title' => 'Unauthorized lesson',
                'url' => 'https://youtube.com/watch?v=example'
            ])
                ->assertForbidden();
        });

        it('student cannot update lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            postJson("/api/lessons/{$lesson->id}", [
                'title' => 'Unauthorized update'
            ])
                ->assertForbidden();
        });

        it('student cannot delete lesson', function () {
            $lesson = Lesson::factory()->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            deleteJson("/api/lessons/{$lesson->id}")
                ->assertForbidden();
        });

        it('returns 404 when lesson not found', function () {
            getJson('/api/lessons/99999')
                ->assertNotFound();
        });

        it('student can set custom per_page for pagination', function () {
            Lesson::factory()->count(10)->create([
                'section_id' => $this->publishedSection->id,
                'course_id' => $this->publishedCourse->id
            ]);

            getJson('/api/lessons?per_page=4')
                ->assertOk()
                ->assertJsonCount(4, 'data');
        });
    });
});
