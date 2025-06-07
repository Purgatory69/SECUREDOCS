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
     * Update a user's premium status and n8n webhook URL.
     */
    public function updateUserPremiumSettings(Request $request, User $user) // Using route model binding
    {
        $request->validate([
            'n8n_webhook_url' => 'nullable|url|max:2048',
            'is_premium' => 'sometimes|boolean',
        ]);

        $user->n8n_webhook_url = $request->input('n8n_webhook_url', $user->n8n_webhook_url);
        
        // Handle checkbox for is_premium (if it's not present, it means false)
        $user->is_premium = $request->has('is_premium');

        $user->save();

        return redirect()->route('admin.dashboard')->with('success', 'User premium settings updated successfully.');
    }
}