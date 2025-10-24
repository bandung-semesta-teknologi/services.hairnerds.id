<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Academy\InstructorResource;
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
