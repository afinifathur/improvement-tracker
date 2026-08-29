<?php

namespace App\Http\Controllers;

use App\Enums\Position;
use App\Http\Requests\DeactivateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'active');
        $departments = Department::active()->orderBy('name')->get();
        $roles = ['admin', 'director', 'manager', 'kabag', 'spv'];

        $query = User::query()
            ->with([
                'department',
                'areaAssignments' => function ($q) {
                    $q->with('area')->orderByDesc('started_at');
                },
            ])
            ->withCount([
                'ownedWorkItems as unfinished_work_items_count' => function ($q) {
                    $q->whereIn('status', ['not_started', 'in_progress', 'blocked']);
                },
            ]);

        // Status Filter
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Department Filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // Role Filter
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('name')->get();

        $summary = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'departments', 'roles', 'status', 'summary'));
    }

    public function create(): View
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $positions = Position::cases();

        return view('users.create', compact('departments', 'areas', 'positions'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
                'department_id' => $request->department_id,
                'is_active' => true,
            ]);

            if ($request->filled('area_id')) {
                AreaAssignment::create([
                    'user_id' => $user->id,
                    'area_id' => $request->area_id,
                    'role' => $request->position ?? ($request->role === 'admin' || $request->role === 'director' ? 'manager' : $request->role),
                    'started_at' => now()->toDateString(),
                    'ended_at' => null,
                ]);
            }
        });

        return redirect()->route('users.index')
            ->with('status', 'Personel berhasil didaftarkan.');
    }

    public function edit(User $user): View
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $positions = Position::cases();

        $activeAssignment = $user->areaAssignments()
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString()))
            ->latest('started_at')
            ->first();

        return view('users.edit', compact('user', 'departments', 'areas', 'positions', 'activeAssignment'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'department_id' => $request->department_id,
            ];

            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $user->update($data);

            if ($request->filled('area_id')) {
                $todayStr = now()->toDateString();
                $currentActive = $user->areaAssignments()
                    ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $todayStr))
                    ->first();

                $targetRole = $request->position ?? ($request->role === 'admin' || $request->role === 'director' ? 'manager' : $request->role);

                if ($currentActive) {
                    if ($currentActive->area_id != $request->area_id || $currentActive->role->value != $targetRole) {
                        // Close current assignment
                        $currentActive->update(['ended_at' => $todayStr]);

                        // Create new assignment
                        AreaAssignment::create([
                            'user_id' => $user->id,
                            'area_id' => $request->area_id,
                            'role' => $targetRole,
                            'started_at' => $todayStr,
                            'ended_at' => null,
                        ]);
                    }
                } else {
                    AreaAssignment::create([
                        'user_id' => $user->id,
                        'area_id' => $request->area_id,
                        'role' => $targetRole,
                        'started_at' => $todayStr,
                        'ended_at' => null,
                    ]);
                }
            }
        });

        return redirect()->route('users.index')
            ->with('status', 'Data personel berhasil diperbarui.');
    }

    public function deactivate(DeactivateUserRequest $request, User $user): RedirectResponse
    {
        $effectiveDate = Carbon::parse($request->effective_date);

        $user->deactivate($effectiveDate, $request->reason, $request->note);

        return redirect()->back()
            ->with('status', "Personel {$user->name} berhasil dinonaktifkan per tanggal {$effectiveDate->format('d M Y')}.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $user->reactivate();

        return redirect()->back()
            ->with('status', "Personel {$user->name} berhasil diaktifkan kembali.");
    }
}
