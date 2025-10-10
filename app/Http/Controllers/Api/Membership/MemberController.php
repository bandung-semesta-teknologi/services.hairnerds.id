<?php

namespace App\Http\Controllers\Api\Membership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\MemberStoreRequest;
use App\Http\Requests\Membership\MemberUpdateRequest;
use App\Http\Resources\Membership\MemberResource;
use App\Http\Resources\Membership\MembershipUserResource;
use App\Models\MembershipSerial;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

#[Group('Member Membership API')]
class MemberController extends Controller
{
    /**
     * Member Membership Datalist
     *
     * Display a datalist of the member membership.
     */
    public function datalist()
    {
        //
    }

    /**
     * Member Membership Index
     *
     * Display a listing of the member membership.
     */
    public function index()
    {
        //
    }

    /**
     * Member Membership Store
     *
     * Store a newly created resource in storage.
     */
    public function store(MemberStoreRequest $request)
    {
        try {
            $data = $request->validated();

            DB::beginTransaction();
            /**
             * In the future, implement the logic to update is_member column
             * and add validated data from in users table using the validated
             * data from $request
             */

            /* Claim Membership Serial Number (Change is_used column to true) */
            $membershipSerial = MembershipSerial::update([]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Member created successfully.',
                'data' => new MemberResource(null), // Replace null with the created member instance
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating member: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create member.',
            ], 500);
        }
    }

    /**
     * Member Membership Show
     *
     * Display the specified resource.
     */
    public function show(MembershipSerial $member)
    {
        return new MemberResource($member);
    }

    /**
     * Member Membership Show User
     *
     * Show membership user information.
     */
    public function showUser(string $member)
    {
        $user = User::whereHas('userProfile', function ($query) use ($member) {
            $query->where('user_uuid_supabase', $member);
        })->with(['membershipSerial'])->firstOrFail();

        return new MembershipUserResource($user);
    }

    /**
     * Member Membership Update
     *
     * Update the specified resource in storage.
     */
    public function update(MemberUpdateRequest $request, MembershipSerial $member)
    {
        try {
            $data = $request->validated();
            $data['is_used'] = true;
            $data['used_at'] = now();

            DB::beginTransaction();

            /**
             * In the future, implement the logic to update is_member column
             * and add validated data from in users table using the validated
             * data from $request
             */

            /* Check if email or phone already exists */

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make("password"),
                'role' => 'student',
            ]);

            $user->userCredentials()->create([
                'type' => 'email',
                'identifier' => $user->email,
            ]);

            $user->userCredentials()->create([
                'type' => 'phone',
                'identifier' => $data['phone_number'],
            ]);

            $user->userProfile()->create([
                'address' => $data['address'] ?? null,
                'user_uuid_supabase' => $data['used_by'],
                'serial_number' => $data['serial_number'],
                'card_number' => $data['card_number'],
            ]);

            event(new Registered($user));

            /* Check if user is already a member */
            if ($member->is_used) {
                throw new \Exception('Membership serial number is already used.');
            }

            /* Claim Membership Serial Number (Change is_used column to true) */
            $member->update($data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Member updated successfully.',
                'data' => new MemberResource($member),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating member: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' =>  $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Member Membership Destroy
     *
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
