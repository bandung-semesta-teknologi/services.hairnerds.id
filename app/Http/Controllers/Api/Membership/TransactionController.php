<?php

namespace App\Http\Controllers\Api\Membership;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Transaction Membership API')]
class TransactionController extends Controller
{
    /**
     * Transaction Membership Datalist
     *
     * Display a datalist of the transaction membership.
     */
    public function datalist()
    {
        //
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
     *  Transaction Membership Store
     *
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
