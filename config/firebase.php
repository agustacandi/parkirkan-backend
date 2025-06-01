<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging
    |
    */
    
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Topic for Broadcasts
    |--------------------------------------------------------------------------
    |
    | Default topic name for broadcast notifications
    |
    */
    'default_topic' => 'broadcast',
    
    /*
    |--------------------------------------------------------------------------
    | FCM API URL
    |--------------------------------------------------------------------------
    |
    | Firebase Cloud Messaging API URL
    |
    */
    'fcm_url' => 'https://fcm.googleapis.com/v1/projects/',
]; 