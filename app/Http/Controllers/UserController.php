<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::withCount('templates')
            ->orderBy('created_at', 'desc')
            ->where('role', 'admin')
            ->paginate(500);
        
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'subscription_start_date' => 'required|date',
            'subscription_end_date' => 'required|date|after:subscription_start_date',
            'role' => 'required|in:super_admin,admin,user',
            'template_limit' => 'required|integer|in:-1,5,10,25',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'subscription_start_date' => $request->subscription_start_date,
            'subscription_end_date' => $request->subscription_end_date,
            'role' => $request->role,
            'template_limit' => $request->template_limit,
            'is_active' => true, // Default to active, will be updated based on subscription
        ]);

        // Update account status based on subscription dates
        if (!$user->isSuperAdmin()) {
            $user->setSubscriptionDates($request->subscription_start_date, $request->subscription_end_date);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'mobile' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'subscription_start_date' => 'required|date',
            'subscription_end_date' => 'required|date|after:subscription_start_date',
            'role' => 'required|in:super_admin,admin,user',
            'template_limit' => 'required|integer|in:-1,5,10,25',
        ]);

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'subscription_start_date' => $request->subscription_start_date,
            'subscription_end_date' => $request->subscription_end_date,
            'role' => $request->role,
            'template_limit' => $request->template_limit,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update account status based on new subscription dates
        if (!$user->isSuperAdmin()) {
            $user->setSubscriptionDates($request->subscription_start_date, $request->subscription_end_date);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Prevent deleting super admin users
        if ($user->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'Cannot delete super admin user!');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    public function show(User $user): Response
    {
        $user->loadCount('templates');
        
        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }

    public function toggleStatus(User $user)
    {
        // Prevent toggling super admin status
        if ($user->isSuperAdmin()) {
            return response()->json(['error' => 'Cannot change super admin account status'], 403);
        }

        if ($user->is_active) {
            $user->deactivateAccount();
            $message = 'User account deactivated successfully!';
        } else {
            $user->activateAccount();
            $message = 'User account activated successfully!';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_active' => $user->is_active
        ]);
    }

    public function updateSubscription(User $user, Request $request)
    {
        $request->validate([
            'subscription_start_date' => 'required|date',
            'subscription_end_date' => 'required|date|after:subscription_start_date',
        ]);

        // Prevent updating super admin subscription
        if ($user->isSuperAdmin()) {
            return response()->json(['error' => 'Cannot update super admin subscription'], 403);
        }

        $user->setSubscriptionDates(
            $request->subscription_start_date,
            $request->subscription_end_date
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully!',
            'is_active' => $user->is_active,
            'subscription_start_date' => $user->subscription_start_date,
            'subscription_end_date' => $user->subscription_end_date,
        ]);
    }
}

