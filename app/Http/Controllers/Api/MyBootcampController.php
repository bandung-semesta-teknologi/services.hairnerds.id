<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MyBootcampResource;
use App\Http\Resources\MyBootcampTicketResource;
use App\Models\Bootcamp;
use App\Models\Payment;
use Illuminate\Http\Request;

class MyBootcampController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $payments = Payment::query()
            ->with(['payable.instructors', 'payable.categories', 'payable.faqs'])
            ->where('user_id', $user->id)
            ->where('payable_type', Bootcamp::class)
            ->where('status', 'paid')
            ->when($request->status, function($q) use ($request) {
                $status = $request->status;
                $now = now();

                return $q->whereHas('payable', function($query) use ($status, $now) {
                    if ($status === 'upcoming') {
                        $query->where('start_at', '>', $now);
                    } elseif ($status === 'ongoing') {
                        $query->where('start_at', '<=', $now)
                            ->where('end_at', '>=', $now);
                    } elseif ($status === 'completed') {
                        $query->where('end_at', '<', $now);
                    }
                });
            })
            ->when($request->search, function($q) use ($request) {
                return $q->whereHas('payable', function($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                });
            })
            ->latest('paid_at')
            ->paginate($request->per_page ?? 15);

        return MyBootcampResource::collection($payments);
    }

    public function show(Request $request, Bootcamp $bootcamp)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $payment = Payment::query()
            ->with(['payable.instructors', 'payable.categories', 'payable.faqs'])
            ->where('user_id', $user->id)
            ->where('payable_type', Bootcamp::class)
            ->where('payable_id', $bootcamp->id)
            ->where('status', 'paid')
            ->first();

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bootcamp not found or not enrolled'
            ], 404);
        }

        return new MyBootcampResource($payment);
    }

    public function ticket(Request $request, Bootcamp $bootcamp)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $payment = Payment::query()
            ->with(['payable'])
            ->where('user_id', $user->id)
            ->where('payable_type', Bootcamp::class)
            ->where('payable_id', $bootcamp->id)
            ->where('status', 'paid')
            ->first();

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        return new MyBootcampTicketResource($payment);
    }
}
