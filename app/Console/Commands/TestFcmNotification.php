<?php

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Models\NotificationTopic;
use App\Notifications\BroadcastNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFcmNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test {broadcast_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FCM notification delivery for broadcasts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $broadcastId = $this->argument('broadcast_id');
        
        if ($broadcastId) {
            $broadcast = Broadcast::find($broadcastId);
            if (!$broadcast) {
                $this->error("Broadcast with ID {$broadcastId} not found");
                return 1;
            }
        } else {
            $broadcast = Broadcast::latest()->first();
            if (!$broadcast) {
                $this->error("No broadcasts found in database");
                return 1;
            }
        }

        $this->info("Testing FCM notification for broadcast: {$broadcast->title}");
        
        // Check Firebase configuration
        $this->info("Checking Firebase configuration...");
        $projectId = config('firebase.project_id');
        $credentialsPath = config('firebase.credentials');
        
        if (!$projectId) {
            $this->error("FIREBASE_PROJECT_ID not set in environment");
            return 1;
        }
        
        if (!$credentialsPath) {
            $this->error("FIREBASE_CREDENTIALS not set in environment");
            return 1;
        }
        
        if (!file_exists($credentialsPath)) {
            $this->error("Firebase credentials file not found at: {$credentialsPath}");
            return 1;
        }
        
        $this->info("✓ Firebase Project ID: {$projectId}");
        $this->info("✓ Firebase Credentials: {$credentialsPath}");
        
        // Test sending notification
        try {
            $this->info("Sending test notification...");
            Log::info('Test FCM notification started', ['broadcast_id' => $broadcast->id]);
            
            // Send notification directly to FCM topic without using model
            $this->sendNotificationToTopic(new BroadcastNotification($broadcast));
            
            $this->info("✓ Notification sent successfully!");
            $this->info("Check your mobile app and Laravel logs for delivery confirmation.");
            
        } catch (\Exception $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
            Log::error('Test FCM notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    /**
     * Send notification directly to FCM topic
     */
    private function sendNotificationToTopic(BroadcastNotification $notification)
    {
        try {
            // Use the FCM channel directly
            $fcmChannel = app(\NotificationChannels\Fcm\FcmChannel::class);
            
            // Create a dummy notifiable that returns null for FCM routing (topic messaging)
            $dummyNotifiable = new class {
                public function routeNotificationFor($driver, $notification = null) {
                    return null; // Topic will be set in the notification itself
                }
                
                public function routeNotificationForFcm() {
                    return null; // Topic will be set in the notification itself
                }
            };
            
            // Send the notification
            $fcmChannel->send($dummyNotifiable, $notification);
            
            Log::info('FCM topic notification sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send FCM topic notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
