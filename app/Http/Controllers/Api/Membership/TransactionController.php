<?php

namespace App\Http\Controllers\Api\Membership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\TransactionStoreRequest;
use App\Http\Resources\Membership\TransactionResource;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Group('Transaction Membership API')]
class TransactionController extends Controller
{
    /**
     * Transaction Membership Datalist
     *
     * Display a datalist of the transaction membership.
     */
    public function datalist(Request $request)
    {
        $perPage = (int) ($request->input('per_page', 5));
        $search = $request->input('search');
        $merchantId = $request->input('merchant_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay(); // 00:00:00
        }

        if ($dateTo) {
            $dateTo = Carbon::parse($dateTo)->endOfDay(); // 23:59:59
        }

        DB::enableQueryLog();

        $payments = Payment::query()
            ->where('payable_type', MembershipTransaction::class)
            ->with(['payable'])
            ->when($merchantId, function ($query, $merchantId) {
                $query->whereHas('payable', function ($q) use ($merchantId) {
                    $q->where('merchant_id', $merchantId);
                });
            })
            ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('paid_at', [$dateFrom, $dateTo]);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_code', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhereHas('payable', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%")
                                ->orWhere('card_number', 'like', "%{$search}%")
                                ->orWhere('merchant_name', 'like', "%{$search}%")
                                ->orWhere('merchant_email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        // dd(DB::getQueryLog());

        return TransactionResource::collection($payments);
    }

    public function datalistMember(Request $request, string $member_id)
    {
        $perPage = (int) ($request->input('per_page', 5));
        $search = $request->input('search');
        $merchantId = $request->input('merchant_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay(); // 00:00:00
        }

        if ($dateTo) {
            $dateTo = Carbon::parse($dateTo)->endOfDay(); // 23:59:59
        }

        $payments = Payment::query()
            ->where('payable_type', MembershipTransaction::class)
            ->with(['payable'])
            ->whereRelation('payable', function ($query) use ($member_id) {
                $query->where('user_uuid_supabase', $member_id);
            })
            ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('paid_at', [$dateFrom, $dateTo]);
            })
            ->when($merchantId, function ($query, $merchantId) {
                $query->whereHas('payable', function ($q) use ($merchantId) {
                    $q->where('merchant_id', $merchantId);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_code', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhereHas('payable', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%")
                                ->orWhere('card_number', 'like', "%{$search}%")
                                ->orWhere('merchant_name', 'like', "%{$search}%")
                                ->orWhere('merchant_email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        return TransactionResource::collection($payments);
    }

    /**
     * Transaction Membership Index
     *
     * Display a listing of the transaction membership.
     */
    public function index()
    {
        //
    }

    /**
     * Transaction Membership Index
     *
     * Display a listing of the transaction membership.
     */
    public function latestTransaction(Request $request)
    {
        $limit = $request->input('limit', 3);
        $merchantId = $request->input('merchant_id', null);

        $payments = Payment::query()
            ->where('payable_type', MembershipTransaction::class)
            ->when($merchantId, function ($query, $merchantId) {
                $query->whereHas('payable', function ($q) use ($merchantId) {
                    $q->where('merchant_id', $merchantId);
                });
            })
            ->with(['payable'])
            ->limit($limit)
            ->latest()
            ->get();

        return [
            'status' => 'success',
            'data' => TransactionResource::collection($payments),
            'message' => 'Latest transaction retrieved successfully.',
        ];
    }

    /**
     * Transaction Membership Store
     *
     * Store a newly created resource in storage.
     */
    public function store(TransactionStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Get User by Card UUID
            // $user = User::where('card_uuid', $request->card_number)->firstOrFail();
            $user = User::whereHas('userProfile', function ($query) use ($request) {
                $query->where('card_number', $request->card_number);
            })->firstOrFail();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found with the provided card number.',
                ], 404);
            }

            // Store Membership Transaction
            $membershipTransaction = MembershipTransaction::create([
                'merchant_id' => $data['merchant_id'],
                'merchant_user_id' => $data['merchant_user_id'],
                'merchant_name' => $data['merchant_name'],
                'merchant_email' => $data['merchant_email'],
                'user_id' => $user->id,
                'user_uuid_supabase' => $user->userProfile->user_uuid_supabase,
                'serial_number' => $user->userProfile->serial_number,
                'card_number' => $user->userProfile->card_number,
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->userProfile->address,
                'phone_number' => $user->phoneNumberCredential->identifier,
                'total_amount' => $data['total'],
            ]);

            // Store Payment
            $payment = Payment::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'payable_type' => MembershipTransaction::class,
                'payable_id' => $membershipTransaction->id,
                'payment_method' => 'manual',
                'amount' => $data['amount'],
                'discount' => $data['discount'],
                'discount_type' => $data['discount_type'],
                'total' => $data['total'],
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully.',
                'data' => [
                    'user_id' => $user->userProfile->user_uuid_supabase,
                    'user_name' => $user->name,
                    'payment_code' => $payment->payment_code,
                    'amount' => $payment->amount,
                    'discount' => $payment->discount,
                    'discount_type' => $payment->discount_type,
                    'total' => $payment->total,
                    'merchant_user_id' => $payment->payable->merchant_user_id,
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating transaction: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transaction.' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transaction Membership Show
     *
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Transaction Membership Update
     *
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Transaction Membership Destroy
     *
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
