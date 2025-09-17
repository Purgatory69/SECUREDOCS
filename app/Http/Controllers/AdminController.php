<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            ->paginate(15)
            ->withQueryString();

        return view('admin-users', compact('users'));
    }

    /**
     * Approve a user.
     */
    public function approve($id)
    {
        DB::table('users')
            ->where('id', $id)
            ->update(['is_approved' => DB::raw('true')]);

        return redirect()->route('admin.users')->with('success', 'User approved successfully.');
    }

    /**
     * Revoke a user's approval.
     */
    public function revoke($id)
    {
        DB::table('users')
            ->where('id', $id)
            ->update(['is_approved' => DB::raw('false')]);

        return redirect()->route('admin.users')->with('success', 'User approval revoked successfully.');
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

        return redirect()->route('admin.users')->with('success', 'User premium settings updated successfully.');
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
}