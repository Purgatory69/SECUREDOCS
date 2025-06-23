<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard with a list of users.
     */
    public function dashboard()
    {
        $users = User::all(); // Fetch all users
        return view('admin-dashboard', compact('users')); // Pass users to the view
    }

    /**
     * Approve a user.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->is_approved = true;
        $user->save();

        return redirect()->route('admin.dashboard')->with('success', 'User approved successfully.');
    }

    /**
     * Revoke a user's approval.
     */
    public function revoke($id)
    {
        $user = User::findOrFail($id);
        $user->is_approved = false;
        $user->save();

        return redirect()->route('admin.dashboard')->with('success', 'User approval revoked successfully.');
    }

    /**
     * Update a user's premium status.
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'is_premium' => 'sometimes|boolean',
        ]);

        // Handle checkbox for is_premium (if it's not present, it means false)
        $user->is_premium = $request->has('is_premium');

        $user->save();

        return redirect()->route('admin.dashboard')->with('success', 'User premium settings updated successfully.');
    }
}