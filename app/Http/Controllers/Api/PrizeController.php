<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrizeStoreRequest;
use App\Http\Requests\PrizeUpdateRequest;
use App\Http\Resources\PrizeResource;
use App\Models\Prize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrizeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Prize::class);

        $prizes = Prize::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->available !== null, function($q) use ($request) {
                return $request->boolean('available')
                    ? $q->where('available_stock', '>', 0)
                    : $q->where('available_stock', '=', 0);
            })
            ->when($request->point_cost_min, fn($q) => $q->where('point_cost', '>=', $request->point_cost_min))
            ->when($request->point_cost_max, fn($q) => $q->where('point_cost', '<=', $request->point_cost_max))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return PrizeResource::collection($prizes);
    }

    public function store(PrizeStoreRequest $request)
    {
        $this->authorize('create', Prize::class);

        try {
            $data = $request->validated();

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = $request->file('banner_image')->store('prizes/banners', 'public');
            }

            if (!isset($data['available_stock'])) {
                $data['available_stock'] = $data['total_stock'];
            }

            $prize = Prize::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Prize created successfully',
                'data' => new PrizeResource($prize)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating prize: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create prize'
            ], 500);
        }
    }

    public function show(Prize $prize)
    {
        $this->authorize('view', $prize);

        return new PrizeResource($prize);
    }

    public function update(PrizeUpdateRequest $request, Prize $prize)
    {
        $this->authorize('update', $prize);

        try {
            $data = $request->validated();

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = $request->file('banner_image')->store('prizes/banners', 'public');
            }

            $prize->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Prize updated successfully',
                'data' => new PrizeResource($prize)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating prize: ' . $e->getMessage(), [
                'prize_id' => $prize->id,
                'user_id' => $request->user()?->id,
                'data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update prize',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(Prize $prize)
    {
        $this->authorize('delete', $prize);

        try {
            $prize->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Prize deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting prize: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete prize'
            ], 500);
        }
    }
}
