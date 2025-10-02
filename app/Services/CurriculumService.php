<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Lesson;
use App\Models\Attachment;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\AnswerBank;
use Illuminate\Support\Facades\Storage;

class CurriculumService
{
    public function createCurriculum(array $data): Section
    {
        $sectionData = [
            'course_id' => $data['course_id'],
            'sequence' => $data['sequence'],
            'title' => $data['title'],
            'objective' => $data['objective'] ?? null,
        ];

        $section = Section::create($sectionData);

        if (!empty($data['lessons'])) {
            foreach ($data['lessons'] as $lessonData) {
                $this->createLesson($section, $lessonData);
            }
        }

        return $section;
    }

    public function updateCurriculum(Section $section, array $data): Section
    {
        $sectionData = array_filter([
            'course_id' => $data['course_id'] ?? null,
            'sequence' => $data['sequence'] ?? null,
            'title' => $data['title'] ?? null,
            'objective' => $data['objective'] ?? null,
        ], function ($value) {
            return $value !== null;
        });

        if (!empty($sectionData)) {
            $section->update($sectionData);
        }

        if (isset($data['lessons'])) {
            $this->syncLessons($section, $data['lessons']);
        }

        return $section->fresh();
    }

    protected function createLesson(Section $section, array $lessonData): Lesson
    {
        $lesson = Lesson::create([
            'section_id' => $section->id,
            'course_id' => $section->course_id,
            'sequence' => $lessonData['sequence'],
            'type' => $lessonData['type'],
            'title' => $lessonData['title'],
            'url' => $lessonData['url'] ?? null,
            'summary' => $lessonData['summary'] ?? null,
            'datetime' => $lessonData['datetime'],
        ]);

        if (in_array($lessonData['type'], ['document', 'audio']) && !empty($lessonData['attachments'])) {
            foreach ($lessonData['attachments'] as $attachmentData) {
                $this->createAttachment($lesson, $attachmentData);
            }
        }

        if ($lessonData['type'] === 'quiz' && !empty($lessonData['quiz'])) {
            $this->createQuiz($lesson, $lessonData['quiz']);
        }

        return $lesson;
    }

    protected function updateLesson(Lesson $lesson, array $lessonData): Lesson
    {
        $updateData = [
            'sequence' => $lessonData['sequence'],
            'type' => $lessonData['type'],
            'title' => $lessonData['title'],
            'url' => $lessonData['url'] ?? null,
            'summary' => $lessonData['summary'] ?? null,
            'datetime' => $lessonData['datetime'] ?? null,
        ];

        $oldType = $lesson->type;
        $newType = $lessonData['type'];

        $lesson->update($updateData);

        if ($oldType !== $newType) {
            $this->handleLessonTypeChange($lesson, $oldType, $newType);
        }

        if (in_array($newType, ['document', 'audio'])) {
            if (isset($lessonData['attachments'])) {
                $this->syncAttachments($lesson, $lessonData['attachments']);
            }
        }

        if ($newType === 'quiz') {
            if (!empty($lessonData['quiz'])) {
                $this->syncQuiz($lesson, $lessonData['quiz']);
            }
        }

        return $lesson->fresh();
    }

    protected function syncLessons(Section $section, array $lessonsData): void
    {
        $existingLessonIds = $section->lessons()->pluck('id')->toArray();
        $submittedLessonIds = [];

        foreach ($lessonsData as $lessonData) {
            if (!empty($lessonData['id'])) {
                $lesson = $section->lessons()->find($lessonData['id']);
                if ($lesson) {
                    $this->updateLesson($lesson, $lessonData);
                    $submittedLessonIds[] = $lesson->id;
                }
            } else {
                $lesson = $this->createLesson($section, $lessonData);
                $submittedLessonIds[] = $lesson->id;
            }
        }

        $lessonsToDelete = array_diff($existingLessonIds, $submittedLessonIds);
        if (!empty($lessonsToDelete)) {
            foreach ($section->lessons()->whereIn('id', $lessonsToDelete)->get() as $lesson) {
                $this->deleteLesson($lesson);
            }
        }
    }

    protected function createAttachment(Lesson $lesson, array $attachmentData): Attachment
    {
        $url = $attachmentData['url'] ?? '';

        if (isset($attachmentData['file'])) {
            $file = $attachmentData['file'];
            $path = $file->store('lessons/attachments', 'public');
            $url = $path;
        }

        return Attachment::create([
            'lesson_id' => $lesson->id,
            'type' => $attachmentData['type'],
            'title' => $attachmentData['title'],
            'url' => $url,
        ]);
    }

    protected function updateAttachment(Attachment $attachment, array $attachmentData): Attachment
    {
        $updateData = [
            'type' => $attachmentData['type'],
            'title' => $attachmentData['title'],
        ];

        if (isset($attachmentData['file'])) {
            if (!filter_var($attachment->url, FILTER_VALIDATE_URL)) {
                if (Storage::exists($attachment->url)) {
                    Storage::delete($attachment->url);
                }
            }

            $file = $attachmentData['file'];
            $path = $file->store('lessons/attachments', 'public');
            $updateData['url'] = $path;
        } elseif (isset($attachmentData['url'])) {
            $updateData['url'] = $attachmentData['url'];
        }

        $attachment->update($updateData);

        return $attachment;
    }

    protected function syncAttachments(Lesson $lesson, array $attachmentsData): void
    {
        $existingAttachmentIds = $lesson->attachments()->pluck('id')->toArray();
        $submittedAttachmentIds = [];

        foreach ($attachmentsData as $attachmentData) {
            if (!empty($attachmentData['id'])) {
                $attachment = $lesson->attachments()->find($attachmentData['id']);
                if ($attachment) {
                    $this->updateAttachment($attachment, $attachmentData);
                    $submittedAttachmentIds[] = $attachment->id;
                }
            } else {
                $attachment = $this->createAttachment($lesson, $attachmentData);
                $submittedAttachmentIds[] = $attachment->id;
            }
        }

        $attachmentsToDelete = array_diff($existingAttachmentIds, $submittedAttachmentIds);
        if (!empty($attachmentsToDelete)) {
            foreach ($lesson->attachments()->whereIn('id', $attachmentsToDelete)->get() as $attachment) {
                $this->deleteAttachment($attachment);
            }
        }
    }

    protected function createQuiz(Lesson $lesson, array $quizData): Quiz
    {
        $quiz = Quiz::create([
            'section_id' => $lesson->section_id,
            'lesson_id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'title' => $quizData['title'],
            'instruction' => $quizData['instruction'] ?? null,
            'duration' => $quizData['duration'] ?? null,
            'total_marks' => $quizData['total_marks'] ?? null,
            'pass_marks' => $quizData['pass_marks'] ?? null,
            'max_retakes' => $quizData['max_retakes'] ?? null,
            'min_lesson_taken' => $quizData['min_lesson_taken'] ?? null,
        ]);

        if (!empty($quizData['questions'])) {
            foreach ($quizData['questions'] as $questionData) {
                $this->createQuestion($quiz, $questionData);
            }
        }

        return $quiz;
    }

    protected function updateQuiz(Quiz $quiz, array $quizData): Quiz
    {
        $updateData = array_filter([
            'title' => $quizData['title'] ?? null,
            'instruction' => $quizData['instruction'] ?? null,
            'duration' => $quizData['duration'] ?? null,
            'total_marks' => $quizData['total_marks'] ?? null,
            'pass_marks' => $quizData['pass_marks'] ?? null,
            'max_retakes' => $quizData['max_retakes'] ?? null,
            'min_lesson_taken' => $quizData['min_lesson_taken'] ?? null,
        ], function ($value) {
            return $value !== null;
        });

        if (!empty($updateData)) {
            $quiz->update($updateData);
        }

        if (isset($quizData['questions'])) {
            $this->syncQuestions($quiz, $quizData['questions']);
        }

        return $quiz->fresh();
    }

    protected function syncQuiz(Lesson $lesson, array $quizData): void
    {
        $existingQuiz = $lesson->quiz;

        if (!empty($quizData['id']) && $existingQuiz && $existingQuiz->id == $quizData['id']) {
            $this->updateQuiz($existingQuiz, $quizData);
        } else {
            if ($existingQuiz) {
                $this->deleteQuiz($existingQuiz);
            }
            $this->createQuiz($lesson, $quizData);
        }
    }

    protected function createQuestion(Quiz $quiz, array $questionData): Question
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'type' => $questionData['type'],
            'question' => $questionData['question'],
            'score' => $questionData['score'] ?? 0,
        ]);

        if (!empty($questionData['answers'])) {
            foreach ($questionData['answers'] as $answerData) {
                $this->createAnswer($question, $answerData);
            }
        }

        return $question;
    }

    protected function updateQuestion(Question $question, array $questionData): Question
    {
        $updateData = [
            'type' => $questionData['type'],
            'question' => $questionData['question'],
            'score' => $questionData['score'] ?? 0,
        ];

        $question->update($updateData);

        if (isset($questionData['answers'])) {
            $this->syncAnswers($question, $questionData['answers']);
        }

        return $question->fresh();
    }

    protected function syncQuestions(Quiz $quiz, array $questionsData): void
    {
        $existingQuestionIds = $quiz->questions()->pluck('id')->toArray();
        $submittedQuestionIds = [];

        foreach ($questionsData as $questionData) {
            if (!empty($questionData['id'])) {
                $question = $quiz->questions()->find($questionData['id']);
                if ($question) {
                    $this->updateQuestion($question, $questionData);
                    $submittedQuestionIds[] = $question->id;
                }
            } else {
                $question = $this->createQuestion($quiz, $questionData);
                $submittedQuestionIds[] = $question->id;
            }
        }

        $questionsToDelete = array_diff($existingQuestionIds, $submittedQuestionIds);
        if (!empty($questionsToDelete)) {
            $quiz->questions()->whereIn('id', $questionsToDelete)->delete();
        }
    }

    protected function createAnswer(Question $question, array $answerData): AnswerBank
    {
        return AnswerBank::create([
            'question_id' => $question->id,
            'answer' => $answerData['answer'],
            'is_true' => $answerData['is_true'] ?? false,
        ]);
    }

    protected function updateAnswer(AnswerBank $answer, array $answerData): AnswerBank
    {
        $answer->update([
            'answer' => $answerData['answer'],
            'is_true' => $answerData['is_true'] ?? false,
        ]);

        return $answer;
    }

    protected function syncAnswers(Question $question, array $answersData): void
    {
        $existingAnswerIds = $question->answerBanks()->pluck('id')->toArray();
        $submittedAnswerIds = [];

        foreach ($answersData as $answerData) {
            if (!empty($answerData['id'])) {
                $answer = $question->answerBanks()->find($answerData['id']);
                if ($answer) {
                    $this->updateAnswer($answer, $answerData);
                    $submittedAnswerIds[] = $answer->id;
                }
            } else {
                $answer = $this->createAnswer($question, $answerData);
                $submittedAnswerIds[] = $answer->id;
            }
        }

        $answersToDelete = array_diff($existingAnswerIds, $submittedAnswerIds);
        if (!empty($answersToDelete)) {
            $question->answerBanks()->whereIn('id', $answersToDelete)->delete();
        }
    }

    protected function handleLessonTypeChange(Lesson $lesson, string $oldType, string $newType): void
    {
        if (in_array($oldType, ['document', 'audio']) && !in_array($newType, ['document', 'audio'])) {
            foreach ($lesson->attachments as $attachment) {
                $this->deleteAttachment($attachment);
            }
        }

        if ($oldType === 'quiz' && $newType !== 'quiz') {
            if ($lesson->quiz) {
                $this->deleteQuiz($lesson->quiz);
            }
        }
    }

    protected function deleteLesson(Lesson $lesson): void
    {
        if (in_array($lesson->type, ['document', 'audio'])) {
            foreach ($lesson->attachments as $attachment) {
                $this->deleteAttachment($attachment);
            }
        }

        if ($lesson->type === 'quiz' && $lesson->quiz) {
            $this->deleteQuiz($lesson->quiz);
        }

        $lesson->delete();
    }

    protected function deleteAttachment(Attachment $attachment): void
    {
        if (!filter_var($attachment->url, FILTER_VALIDATE_URL)) {
            if (Storage::exists($attachment->url)) {
                Storage::delete($attachment->url);
            }
        }

        $attachment->delete();
    }

    protected function deleteQuiz(Quiz $quiz): void
    {
        foreach ($quiz->questions as $question) {
            $question->answerBanks()->delete();
        }

        $quiz->questions()->delete();
        $quiz->delete();
    }
}
