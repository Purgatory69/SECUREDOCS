<?php
/**
 * Complete AI Categorization Script
 * This script updates the AI categorization status for a user to 100% completion
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'],
    'host' => $_ENV['DB_HOST'],
    'port' => $_ENV['DB_PORT'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

/**
 * Complete AI categorization for a specific user
 */
function completeAICategorization($userId) {
    $cacheKey = "securedocs_cache_ai_categorization_status_{$userId}";
    
    // Current status data
    $currentStatus = [
        'status' => 'completed',
        'progress' => 100,
        'message' => 'AI categorization completed successfully',
        'updated_at' => date('c'), // ISO 8601 format
        'details' => null
    ];
    
    // Serialize the data (PHP array serialization format used by Laravel)
    $serializedData = serialize($currentStatus);
    
    // Set expiration to 1 hour from now
    $expiration = time() + 3600;
    
    try {
        // Update or insert the cache entry
        $result = Capsule::table('cache')
            ->updateOrInsert(
                ['key' => $cacheKey],
                [
                    'value' => $serializedData,
                    'expiration' => $expiration
                ]
            );
        
        if ($result) {
            echo "✅ Successfully completed AI categorization for user {$userId}\n";
            echo "📊 Status: completed (100%)\n";
            echo "💾 Cache key: {$cacheKey}\n";
            echo "⏰ Expires at: " . date('Y-m-d H:i:s', $expiration) . "\n";
            
            // Log the completion
            logCompletion($userId);
            
            return true;
        } else {
            echo "❌ Failed to update cache for user {$userId}\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ Error completing AI categorization: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Log the completion to Laravel log
 */
function logCompletion($userId) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] local.INFO: AI categorization status updated {\"user_id\":\"{$userId}\",\"status\":\"completed\",\"progress\":100}";
    $logFile = __DIR__ . '/storage/logs/laravel.log';
    
    if (file_exists(dirname($logFile))) {
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        echo "📝 Logged completion to laravel.log\n";
    }
}

/**
 * Get current status for a user
 */
function getCurrentStatus($userId) {
    $cacheKey = "securedocs_cache_ai_categorization_status_{$userId}";
    
    try {
        $result = Capsule::table('cache')
            ->where('key', $cacheKey)
            ->first();
        
        if ($result) {
            $data = unserialize($result->value);
            echo "📊 Current status for user {$userId}:\n";
            echo "   Status: {$data['status']}\n";
            echo "   Progress: {$data['progress']}%\n";
            echo "   Message: {$data['message']}\n";
            echo "   Updated: {$data['updated_at']}\n";
        } else {
            echo "❌ No categorization status found for user {$userId}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error getting status: " . $e->getMessage() . "\n";
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage: php complete_ai_categorization.php <user_id> [status|complete]\n";
    echo "Examples:\n";
    echo "  php complete_ai_categorization.php 18 status    - View current status\n";
    echo "  php complete_ai_categorization.php 18 complete  - Complete categorization\n";
    echo "  php complete_ai_categorization.php 18           - Complete categorization (default)\n";
    exit(1);
}

$userId = $argv[1];
$action = $argv[2] ?? 'complete';

echo "🤖 AI Categorization Management Tool\n";
echo "=====================================\n";

if ($action === 'status') {
    getCurrentStatus($userId);
} else {
    echo "👤 Processing user ID: {$userId}\n";
    echo "🎯 Action: Complete AI categorization\n\n";
    
    // Show current status first
    echo "Current status:\n";
    getCurrentStatus($userId);
    
    echo "\nUpdating to completion...\n";
    $success = completeAICategorization($userId);
    
    if ($success) {
        echo "\n✅ AI categorization completed successfully!\n";
        echo "🔄 The frontend will automatically detect this change within 3 seconds.\n";
    } else {
        echo "\n❌ Failed to complete AI categorization.\n";
        exit(1);
    }
}
