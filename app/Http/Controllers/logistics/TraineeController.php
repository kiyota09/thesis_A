<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TraineeGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TraineeController extends Controller
{
    public function index()
    {
        $trainees = User::where('position', 'trainee')
            ->where('role', 'LOG')
            ->with('traineeGrade')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'join_date' => $t->join_date,
                'grade_percentage' => $t->traineeGrade?->total_percentage ?? 0,
            ]);

        // Any user who can access the Logistics module has full permission to manage trainees
        $permissions = ['trainee' => 'edit'];

        return Inertia::render('Dashboard/Logistics/Trainee', [
            'trainees' => $trainees,
            'permissions' => $permissions,
        ]);
    }

    public function grade(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour' => 'required|integer|min:1|max:5',
            'technicals' => 'required|integer|min:1|max:5',
            'safety_awareness' => 'required|integer|min:1|max:5',
            'productivity' => 'required|integer|min:1|max:5',
        ]);

        $total = array_sum($validated);
        $percentage = ($total / 25) * 100;

        TraineeGrade::updateOrCreate(
            ['user_id' => $id],
            array_merge($validated, ['total_percentage' => $percentage])
        );

        return back()->with('message', 'Grade saved.');
    }

    public function pass($id)
    {
        $trainee = User::findOrFail($id);
        $grade = $trainee->traineeGrade;
        if (!$grade || $grade->total_percentage < 80) {
            return back()->with('error', 'Grade must be at least 80% to pass.');
        }
        $trainee->update(['position' => 'staff']);
        // No default page permissions needed for Logistics staff – they use dedicated UI portals
        return back()->with('message', 'Trainee promoted to staff.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $trainee = User::findOrFail($id);
        $trainee->update(['is_active' => false, 'position' => 'staff']);
        return back()->with('message', 'Trainee failed and archived.');
    }
}