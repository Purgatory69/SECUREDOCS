<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard with a list of users (with search & pagination).
     */
    public function dashboard(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = User::query();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $query->where(function ($qq) use ($needle) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]) 
                   ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"]);
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // KPI counts
        $totalUsers = User::count();
        $premiumUsers = User::whereRaw('is_premium IS TRUE')->count();
        $standardUsers = $totalUsers - $premiumUsers;

        // Recent signups
        $recentUsers = User::orderBy('created_at', 'desc')->take(10)->get(['id','name','email','created_at','is_premium']);

        // Line chart data: daily new users over last 30 days (total, premium, standard)
        $days = 30;
        $from = Carbon::today()->subDays($days - 1)->startOfDay();

        $totals = User::where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

        $premiums = User::where('created_at', '>=', $from)
            ->whereRaw('is_premium IS TRUE')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

        $labels = [];
        $seriesTotal = [];
        $seriesPremium = [];
        $seriesStandard = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $key;
            $t = (int)($totals[$key] ?? 0);
            $p = (int)($premiums[$key] ?? 0);
            $seriesTotal[] = $t;
            $seriesPremium[] = $p;
            $seriesStandard[] = max(0, $t - $p);
        }

        return view('admin-dashboard', compact(
            'users',
            'totalUsers', 'premiumUsers', 'standardUsers',
            'recentUsers', 'labels', 'seriesTotal', 'seriesPremium', 'seriesStandard'
        ));
    }

    /**
     * Admin Users page - alias for usersList to match route naming
     */
    public function users(Request $request)
    {
        return $this->usersList($request);
    }

    /**
     * Admin Users list page (separate from dashboard) with search & pagination.
     */
    public function usersList(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = User::query();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $query->where(function ($qq) use ($needle) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]) 
                   ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"]);
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(6) //For arrow testing
            ->withQueryString();

        return view('admin-users', compact('users'));
    }

    /**
     * Approve a user.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        
        DB::table('users')
            ->where('id', $id)
            ->update(['is_approved' => DB::raw('true')]);

        return redirect()->route('admin.users')->with('success', [
            'key' => 'auth.success_user_approved',
            'params' => ['name' => Str::limit($user->name, 28)]
        ]);
    }

    /**
     * Revoke a user's approval.
     */
    public function revoke($id)
    {
        $user = User::findOrFail($id);
        
        DB::table('users')
            ->where('id', $id)
            ->update(['is_approved' => DB::raw('false')]);

        return redirect()->route('admin.users')->with('success', [
            'key' => 'auth.success_user_revoked',
            'params' => ['name' => Str::limit($user->name, 28)]
        ]);
    }

    /**
     * Update a user's premium status.
     */
    public function updateUser(Request $request, User $user)
    {
        // Handle checkbox for is_premium (if it's not present, it means false)
        $isPremium = $request->has('is_premium') && $request->input('is_premium') == '1';
        
        DB::table('users')
            ->where('id', $user->id)
            ->update(['is_premium' => DB::raw($isPremium ? 'true' : 'false')]);

        return redirect()->route('admin.users')->with('success', [
            'key' => 'auth.success_premium_updated',
            'params' => ['name' => Str::limit($user->name, 28)]
        ]);
    }

    /**
     * Alias to match route name for updating user's premium settings.
     */
    public function updatePremiumSettings(Request $request, User $user)
    {
        return $this->updateUser($request, $user);
    }

    /**
     * JSON: Metrics for users over a time range and grouping (day/month).
     */
    public function metricsUsers(Request $request)
    {
        $range = strtolower((string) $request->query('range', '30d')); // 7d,30d,90d,1y,12m
        $group = strtolower((string) $request->query('group', 'day')); // day | month

        // Determine time window
        $now = Carbon::today();
        switch ($range) {
            case '7d': $days = 7; break;
            case '90d': $days = 90; break;
            case '1y': $days = 365; break;
            case '12m': $days = 365; $group = 'month'; break;
            case '30d': default: $days = 30; break;
        }

        $from = $now->copy()->subDays($days - 1)->startOfDay();

        if ($group === 'month') {
            // Build label buckets per month
            $months = [];
            $cursor = $from->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            while ($cursor->lte($end)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            $totals = User::where('created_at', '>=', $from)
                ->select(DB::raw("to_char(date_trunc('month', created_at), 'YYYY-MM') as d"), DB::raw('COUNT(*) as c'))
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

            $premiums = User::where('created_at', '>=', $from)
                ->whereRaw('is_premium IS TRUE')
                ->select(DB::raw("to_char(date_trunc('month', created_at), 'YYYY-MM') as d"), DB::raw('COUNT(*) as c'))
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

            $labels = $months;
            $seriesTotal = [];
            $seriesPremium = [];
            $seriesStandard = [];
            foreach ($labels as $key) {
                $t = (int)($totals[$key] ?? 0);
                $p = (int)($premiums[$key] ?? 0);
                $seriesTotal[] = $t;
                $seriesPremium[] = $p;
                $seriesStandard[] = max(0, $t - $p);
            }
        } else {
            // Daily grouping
            $totals = User::where('created_at', '>=', $from)
                ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

            $premiums = User::where('created_at', '>=', $from)
                ->whereRaw('is_premium IS TRUE')
                ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
                ->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();

            $labels = [];
            $seriesTotal = [];
            $seriesPremium = [];
            $seriesStandard = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $from->copy()->addDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $key;
                $t = (int)($totals[$key] ?? 0);
                $p = (int)($premiums[$key] ?? 0);
                $seriesTotal[] = $t;
                $seriesPremium[] = $p;
                $seriesStandard[] = max(0, $t - $p);
            }
        }

        $totalUsers = User::count();
        $premiumUsers = User::whereRaw('is_premium IS TRUE')->count();
        $standardUsers = $totalUsers - $premiumUsers;

        return response()->json([
            'labels' => $labels,
            'total' => $seriesTotal,
            'premium' => $seriesPremium,
            'standard' => $seriesStandard,
            'totals' => [
                'total_users' => $totalUsers,
                'premium_users' => $premiumUsers,
                'standard_users' => $standardUsers,
            ]
        ]);
    }

    /**
     * JSON: Predictive users list for live search in admin.
     */
    public function usersJson(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(50, $perPage));

        $query = User::query();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $query->where(function ($qq) use ($needle) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]) 
                   ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"]);
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = [];
        foreach ($paginator->items() as $u) {
            $data[] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'is_approved' => (bool) $u->is_approved,
                'is_premium' => (bool) $u->is_premium,
                'created_at' => optional($u->created_at)?->format('Y-m-d H:i:s'),
                'urls' => [
                    'approve' => route('admin.approve', $u->id),
                    'revoke' => route('admin.revoke', $u->id),
                    'premium' => route('admin.users.premium-settings', $u),
                ],
            ];
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        ]);
    }


    /**
     * Toggle user premium status
     */
    public function togglePremium(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            if ($user->is_premium) {
                // Remove premium status
                DB::statement('UPDATE users SET is_premium = false WHERE id = ?', [$user->id]);
                
                // Cancel active subscriptions
                $user->subscriptions()
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                        'auto_renew' => false
                    ]);
                
                $message = [
                    'key' => 'auth.success_premium_removed',
                    'params' => ['name' => Str::limit($user->name, 28)]
                ];
            } else {
                // Grant premium status
                DB::statement('UPDATE users SET is_premium = true WHERE id = ?', [$user->id]);
                
                // Create admin-granted subscription
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_name' => 'premium',
                    'status' => 'active',
                    'amount' => 0.00, // Admin granted - no charge
                    'currency' => 'PHP',
                    'billing_cycle' => 'monthly',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(), // Give 1 year for admin grants
                    'auto_renew' => false
                ]);
                
                $message = [
                    'key' => 'auth.success_premium_granted',
                    'params' => ['name' => Str::limit($user->name, 28)]
                ];
            }
            
            // Refresh user model to get updated is_premium value
            $user->refresh();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __($message['key'], $message['params']),
                    'is_premium' => $user->is_premium
                ]);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Toggle premium failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle premium status: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to toggle premium status: ' . $e->getMessage());
        }
    }

    /**
     * Reset user premium completely (remove all subscriptions and payments)
     */
    public function resetPremium(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        // Remove premium status
        $user->is_premium = false;
        $user->save();
        
        // Delete all subscriptions and payments for this user
        $user->payments()->delete();
        $user->subscriptions()->delete();
        
        $message = [
            'key' => 'auth.success_premium_reset',
            'params' => ['name' => Str::limit($user->name, 28)]
        ];
            
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __($message['key'], $message['params'])
            ]);
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Get user premium details
     */
    public function getUserPremiumDetails($userId)
    {
        $user = User::with(['subscriptions', 'payments'])->findOrFail($userId);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_premium' => $user->is_premium,
            ],
            'subscriptions' => $user->subscriptions->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'plan_name' => $sub->plan_name,
                    'status' => $sub->status,
                    'amount' => $sub->formatted_amount,
                    'starts_at' => $sub->starts_at->format('M d, Y'),
                    'ends_at' => $sub->ends_at->format('M d, Y'),
                    'auto_renew' => $sub->auto_renew
                ];
            }),
            'payments' => $user->payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->formatted_amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method_display,
                    'created_at' => $payment->created_at->format('M d, Y')
                ];
            })
        ]);
    }
}