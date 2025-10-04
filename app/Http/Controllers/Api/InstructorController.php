<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Models\User;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $instructors = User::query()
            ->where('role', 'instructor')
            ->select('id', 'name', 'email')
            ->orderBy('name', 'asc')
            ->get();

        return InstructorResource::collection($instructors);
    }
}
