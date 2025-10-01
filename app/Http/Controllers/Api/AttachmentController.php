<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachmentStoreRequest;
use App\Http\Requests\AttachmentUpdateRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attachment::class);

        $user = $request->user();

        $attachments = Attachment::query()
            ->with(['lesson.course'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->whereHas('lesson.course', function($q) {
                    $q->where('status', 'published');
                })->whereHas('lesson.course.enrollments', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('lesson.course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->lesson_id, fn($q) => $q->where('lesson_id', $request->lesson_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return AttachmentResource::collection($attachments);
    }

    public function store(AttachmentStoreRequest $request)
    {
        $this->authorize('create', Attachment::class);

        try {
            $data = $request->validated();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('lessons/attachments', 'public');
                $data['url'] = $path;
            }

            unset($data['file']);

            $attachment = Attachment::create($data);
            $attachment->load(['lesson.course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Attachment created successfully',
                'data' => new AttachmentResource($attachment)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating attachment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create attachment'
            ], 500);
        }
    }

    public function show(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        $attachment->load(['lesson.course']);

        return new AttachmentResource($attachment);
    }

    public function update(AttachmentUpdateRequest $request, Attachment $attachment)
    {
        $this->authorize('update', $attachment);

        try {
            $data = array_filter([
                'lesson_id' => $request->input('lesson_id'),
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'url' => $request->input('url'),
            ], function($value) {
                return $value !== null;
            });

            if ($request->hasFile('file')) {
                if (!filter_var($attachment->url, FILTER_VALIDATE_URL)) {
                    if (Storage::exists($attachment->url)) {
                        Storage::delete($attachment->url);
                    }
                }

                $file = $request->file('file');
                $path = $file->store('lessons/attachments', 'public');
                $data['url'] = $path;
            }

            if (!empty($data)) {
                $attachment->update($data);
            }

            $attachment->refresh();
            $attachment->load(['lesson.course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Attachment updated successfully',
                'data' => new AttachmentResource($attachment)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating attachment: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update attachment',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        try {
            $attachment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Attachment deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete attachment'
            ], 500);
        }
    }
}
