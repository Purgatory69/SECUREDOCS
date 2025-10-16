<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring and expired subscriptions, send notifications, and reset premium status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking subscription expirations...');
        
        try {
            // Call the database function to notify expiring subscriptions
            $this->info('📧 Sending expiration warnings for subscriptions expiring soon...');
            DB::select('SELECT notify_expiring_subscriptions()');
            
            // Call the database function to handle expired subscriptions
            $this->info('⚠️ Processing expired subscriptions...');
            DB::select('SELECT handle_expired_subscriptions()');
            
            // Get statistics
            $stats = $this->getStatistics();
            
            $this->newLine();
            $this->info('✅ Subscription check completed successfully!');
            $this->newLine();
            
            // Display statistics
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Active Subscriptions', $stats['active']],
                    ['Expiring Soon (7 days)', $stats['expiring_soon']],
                    ['Expired Subscriptions', $stats['expired']],
                    ['Premium Users', $stats['premium_users']],
                    ['Users Without Subscription', $stats['premium_no_sub']],
                ]
            );
            
            Log::info('Subscription expiration check completed', $stats);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error checking subscriptions: ' . $e->getMessage());
            Log::error('Subscription expiration check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Get subscription statistics
     */
    private function getStatistics(): array
    {
        // Use DB::raw for PostgreSQL boolean compatibility
        $premiumUsers = DB::table('users')
            ->whereRaw('is_premium = true')
            ->count();
            
        $premiumNoSub = DB::table('users')
            ->whereRaw('is_premium = true')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.user_id', 'users.id')
                    ->whereRaw('subscriptions.status = ?', ['active'])
                    ->whereRaw('subscriptions.ends_at > NOW()');
            })
            ->count();
            
        return [
            'active' => DB::table('subscriptions')
                ->whereRaw('status = ?', ['active'])
                ->whereRaw('ends_at > NOW()')
                ->count(),
            'expiring_soon' => DB::table('subscriptions')
                ->whereRaw('status = ?', ['active'])
                ->whereRaw('ends_at > NOW()')
                ->whereRaw('ends_at <= NOW() + INTERVAL \'7 days\'')
                ->count(),
            'expired' => DB::table('subscriptions')
                ->whereRaw('status = ?', ['expired'])
                ->count(),
            'premium_users' => $premiumUsers,
            'premium_no_sub' => $premiumNoSub,
        ];
    }
}
