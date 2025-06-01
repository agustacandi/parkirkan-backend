# Firebase Cloud Messaging (FCM) Setup

Dokumentasi ini menjelaskan cara setup FCM untuk mengirim notifikasi broadcast ke mobile app.

## Prerequisites

1. Firebase project sudah dibuat
2. Firebase Admin SDK sudah dikonfigurasi
3. Laravel project sudah terinstall package `laravel-notification-channels/fcm`

## Setup Environment

Tambahkan konfigurasi berikut ke file `.env`:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CREDENTIALS=path/to/your/firebase-service-account.json
```

## Firebase Service Account Setup

1. Buka [Firebase Console](https://console.firebase.google.com)
2. Pilih project Anda
3. Klik ⚙️ (Settings) → Project settings
4. Pilih tab **Service accounts**
5. Klik **Generate new private key**
6. Download file JSON dan simpan di project Laravel Anda
7. Set path file tersebut di environment variable `FIREBASE_CREDENTIALS`

## Fitur yang Sudah Diimplementasikan

### Broadcast Notification

Ketika security berhasil menambahkan broadcast baru, sistem akan:

1. ✅ Membuat broadcast baru di database
2. ✅ Mengirim notifikasi FCM ke topic `"broadcast"` secara langsung
3. ✅ Menyertakan data broadcast (title, description, image, dll)

### Topic Messaging

Sistem menggunakan FCM Topic Messaging dengan topic `"broadcast"` yang memungkinkan:
- Mengirim notifikasi ke semua device yang subscribe ke topic
- Tidak perlu manage individual device tokens
- Optimal untuk broadcast ke banyak user

### Notification Class

`App\Notifications\BroadcastNotification` menangani:
- Format notifikasi FCM
- Topic messaging setup
- Payload data untuk mobile app

## Mobile App Integration

### Android

App Android perlu subscribe ke topic "broadcast":

```kotlin
FirebaseMessaging.getInstance().subscribeToTopic("broadcast")
```

### iOS

App iOS perlu subscribe ke topic "broadcast":

```swift
Messaging.messaging().subscribe(toTopic: "broadcast")
```

## Testing

Untuk test notifikasi broadcast:

1. Pastikan Firebase credentials sudah dikonfigurasi
2. Jalankan command: `php artisan fcm:test`
3. Atau panggil API POST `/api/broadcasts` dengan data broadcast
4. Cek log Laravel untuk memastikan notifikasi terkirim
5. Device yang subscribe topic "broadcast" akan menerima notifikasi

### Test dengan Firebase Console

Untuk test langsung dari Firebase Console:

1. Buka [Firebase Console](https://console.firebase.google.com)
2. Pilih project Anda
3. Masuk ke **Cloud Messaging**
4. Klik **Send your first message**
5. Isi form:
   - **Notification title**: `Test Broadcast`
   - **Notification text**: `Testing broadcast notification`
6. Klik **Send test message**
7. Pilih **Topic** dan masukkan: `broadcast`
8. Klik **Test**

Jika notifikasi dari Firebase Console berhasil diterima tapi dari Laravel tidak, berarti ada masalah di payload Laravel.

## Troubleshooting

### Error: "Failed to send broadcast notification"

1. Cek Firebase credentials path
2. Pastikan service account memiliki permission Firebase Cloud Messaging
3. Cek log Laravel: `tail -f storage/logs/laravel.log`

### Notifikasi tidak diterima di mobile app

1. **Pastikan device sudah subscribe ke topic "broadcast"**
   ```kotlin
   FirebaseMessaging.getInstance().subscribeToTopic("broadcast")
   ```

2. **Cek permission notifikasi di Android (API 33+)**
   ```kotlin
   if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
       if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) 
           != PackageManager.PERMISSION_GRANTED) {
           // Request permission
           ActivityCompat.requestPermissions(this, 
               arrayOf(Manifest.permission.POST_NOTIFICATIONS), 
               REQUEST_CODE)
       }
   }
   ```

3. **Pastikan FCM service terdaftar di AndroidManifest.xml**
   ```xml
   <service
       android:name=".fcm.ParkirkanMessagingService"
       android:directBootAware="true"
       android:exported="false">
       <intent-filter>
           <action android:name="com.google.firebase.MESSAGING_EVENT" />
       </intent-filter>
   </service>
   ```

4. **Cek Firebase Console > Cloud Messaging untuk delivery report**

5. **Pastikan app dalam foreground atau background (bukan terminated)**

6. **Test dengan Firebase Console**
   - Buka Firebase Console > Cloud Messaging
   - Kirim test message ke topic "broadcast"
   - Cek apakah diterima di mobile app

### Queue Jobs Gagal

Jika menggunakan queue dan job gagal:

1. Cek failed jobs: `php artisan queue:failed`
2. Clear failed jobs: `php artisan queue:flush`
3. Restart queue worker: `php artisan queue:restart`

## Payload Structure

Notification yang dikirim memiliki struktur:

```json
{
  "notification": {
    "title": "📢 Broadcast Baru!",
    "body": "Judul broadcast",
    "image": "URL gambar (optional)"
  },
  "data": {
    "notification_type": "broadcast",
    "broadcast_id": "123",
    "click_action": "OPEN_BROADCAST",
    "title": "Judul broadcast",
    "description": "Deskripsi broadcast",
    "created_at": "2024-01-01T00:00:00.000Z",
    "target_route": "broadcast_detail/123"
  },
  "topic": "broadcast"
}
```

## Implementation Notes

- Backend menggunakan direct FCM topic messaging tanpa model database
- Notification dikirim langsung tanpa queue untuk menghindari serialization issues
- Mobile app harus handle notification type "broadcast" di FCM service 

## 📱 Broadcast Notification with Image

Untuk mengirim notifikasi broadcast yang menampilkan gambar, gunakan format data berikut:

### Format Data FCM untuk Broadcast dengan Gambar

```json
{
  "to": "USER_FCM_TOKEN_HERE",
  "data": {
    "notification_type": "broadcast",
    "notification_title": "Judul Broadcast",
    "notification_body": "Isi pesan broadcast yang akan ditampilkan",
    "image_url": "https://example.com/path/to/image.jpg",
    "broadcast_id": "12345",
    "id": "12345"
  },
  "notification": {
    "title": "Judul Broadcast", 
    "body": "Isi pesan broadcast",
    "image": "https://example.com/path/to/image.jpg"
  }
}
```

### Alternatif Field untuk Image URL

Aplikasi akan mencari URL gambar dari berbagai field berikut (berdasarkan prioritas):

1. **notification.image** - Firebase notification payload
2. **data.image_url** - Field custom untuk URL gambar
3. **data.image** - Field alternatif untuk gambar
4. **data.picture** - Field alternatif lainnya
5. **data.photo_url** - Field untuk URL foto
6. **data.thumbnail** - Field untuk thumbnail
7. **data.attachment_url** - Field untuk attachment
8. **data.media_url** - Field untuk media URL

### Contoh Implementasi di Laravel/PHP

**❌ OLD APPROACH (Menyebabkan sound tidak keluar):**
```php
// JANGAN GUNAKAN INI - notification payload akan override sound
$notification = Notification::create($title, $message)->withImageUrl($imageUrl);
$message = CloudMessage::withTarget('token', $fcmToken)
    ->withNotification($notification) // ← INI MASALAHNYA!
    ->withData($data);
```

**✅ NEW APPROACH (v2.0) - DATA ONLY:**
```php
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

public function sendBroadcastNotificationWithImage($fcmToken, $title, $message, $imageUrl, $broadcastId)
{
    $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
    $messaging = $factory->createMessaging();
    
    // DATA-ONLY message untuk memastikan sound local dimainkan
    $data = [
        'notification_type' => 'broadcast',
        'notification_title' => $title,
        'notification_body' => $message,
        'image_url' => $imageUrl,        // Primary image field
        'image' => $imageUrl,            // Backup image field
        'broadcast_id' => (string)$broadcastId,
        'id' => (string)$broadcastId,
        'timestamp' => now()->toISOString(),
    ];
    
    // HANYA DATA, TANPA NOTIFICATION PAYLOAD
    $message = CloudMessage::withTarget('token', $fcmToken)
        ->withData($data); // ← HANYA DATA SAJA!
    
    try {
        $result = $messaging->send($message);
        Log::info("Broadcast notification (data-only) sent successfully", [
            'token' => $fcmToken,
            'title' => $title,
            'image_url' => $imageUrl,
            'broadcast_id' => $broadcastId,
            'data_only' => true, // ← FLAG untuk tracking
            'result' => $result
        ]);
        
        return $result;
    } catch (\Exception $e) {
        Log::error("Failed to send broadcast notification", [
            'error' => $e->getMessage(),
            'token' => $fcmToken,
            'image_url' => $imageUrl
        ]);
        throw $e;
    }
}

/**
 * Alternative: Topic-based broadcast (untuk kirim ke semua user)
 */
public function sendBroadcastToAllUsers($title, $message, $imageUrl, $broadcastId)
{
    $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
    $messaging = $factory->createMessaging();
    
    $data = [
        'notification_type' => 'broadcast',
        'notification_title' => $title,
        'notification_body' => $message,
        'image_url' => $imageUrl,
        'broadcast_id' => (string)$broadcastId,
        'id' => (string)$broadcastId,
        'timestamp' => now()->toISOString(),
    ];
    
    // Send to topic "broadcast" - DATA ONLY
    $message = CloudMessage::withTarget('topic', 'broadcast')
        ->withData($data);
    
    try {
        $result = $messaging->send($message);
        Log::info("Broadcast notification sent to topic", [
            'topic' => 'broadcast',
            'title' => $title,
            'image_url' => $imageUrl,
            'broadcast_id' => $broadcastId,
            'data_only' => true,
            'result' => $result
        ]);
        
        return $result;
    } catch (\Exception $e) {
        Log::error("Failed to send topic broadcast notification", [
            'error' => $e->getMessage(),
            'topic' => 'broadcast',
            'image_url' => $imageUrl
        ]);
        throw $e;
    }
}
```

### Persyaratan untuk Image URL

1. **Protocol**: Harus menggunakan HTTPS (untuk keamanan)
2. **Format**: Mendukung JPG, JPEG, PNG, GIF, WebP, BMP
3. **Ukuran**: Maksimal 1024x512 pixels (akan otomatis diresize)
4. **Timeout**: Server harus merespons dalam 15 detik
5. **Accessibility**: URL harus dapat diakses publik (tidak memerlukan autentikasi)

### Contoh URL Image yang Valid

```
✅ https://example.com/images/broadcast.jpg
✅ https://cdn.example.com/media/photo.png
✅ https://firebasestorage.googleapis.com/v0/b/project/image.webp
✅ https://imgur.com/abc123.gif
✅ https://res.cloudinary.com/demo/image/upload/sample.jpg

❌ http://example.com/image.jpg (tidak HTTPS)
❌ https://private.com/auth-required-image.jpg (memerlukan auth)
❌ https://slow-server.com/image.jpg (timeout > 15 detik)
```

### Tips untuk Optimasi

1. **Gunakan CDN**: Gunakan service seperti Cloudinary, Firebase Storage, atau AWS S3
2. **Compress Images**: Pastikan ukuran file tidak terlalu besar
3. **Cache Headers**: Set proper cache headers untuk performa
4. **WebP Format**: Gunakan format WebP untuk ukuran file yang lebih kecil
5. **Aspect Ratio**: Gunakan aspect ratio 2:1 (landscape) untuk hasil terbaik

### Testing

Untuk testing, Anda dapat menggunakan image sample berikut:
```
https://picsum.photos/800/400?random=1
https://via.placeholder.com/800x400/FF5722/FFFFFF?text=Broadcast+Image
```

### Error Handling

Jika gambar gagal dimuat, aplikasi akan:
1. Menampilkan notifikasi dengan `BigTextStyle` (text-only)
2. Log error untuk debugging
3. Tetap menampilkan title dan message broadcast

### Testing Broadcast Notification dengan Image

Untuk test implementasi, Anda dapat menggunakan tool seperti **Postman** atau **cURL** untuk mengirim FCM message langsung:

#### Contoh Test dengan cURL

```bash
curl -X POST "https://fcm.googleapis.com/fcm/send" \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "USER_FCM_TOKEN",
    "data": {
      "notification_type": "broadcast",
      "notification_title": "Test Broadcast dengan Gambar",
      "notification_body": "Ini adalah test notification broadcast yang menampilkan gambar dari internet",
      "image_url": "https://picsum.photos/800/400?random=1",
      "broadcast_id": "test_123",
      "id": "test_123"
    },
    "notification": {
      "title": "Test Broadcast dengan Gambar",
      "body": "Ini adalah test notification broadcast",
      "image": "https://picsum.photos/800/400?random=1"
    }
  }'
```

#### Contoh Test dengan Laravel/PHP

```php
// Test Controller untuk broadcast dengan image
class TestBroadcastController extends Controller
{
    public function testBroadcastWithImage(Request $request)
    {
        $fcmToken = $request->input('fcm_token');
        $imageUrl = $request->input('image_url', 'https://picsum.photos/800/400?random=' . rand(1, 100));
        
        try {
            $result = $this->sendBroadcastNotificationWithImage(
                $fcmToken,
                'Test Broadcast Image',
                'Ini adalah test broadcast notification dengan gambar dari Laravel backend',
                $imageUrl,
                'test_' . time()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Broadcast notification sent successfully',
                'image_url' => $imageUrl,
                'fcm_result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

#### Sample Images untuk Testing

```php
// Dalam controller atau seeder
$sampleImages = [
    'https://picsum.photos/800/400?random=1',
    'https://picsum.photos/800/400?random=2', 
    'https://via.placeholder.com/800x400/FF5722/FFFFFF?text=Test+Broadcast',
    'https://via.placeholder.com/800x400/4CAF50/FFFFFF?text=Success+Test',
    'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&h=400&fit=crop',
];

// Random image untuk testing
$randomImage = $sampleImages[array_rand($sampleImages)];
```

#### Test Route (routes/web.php atau routes/api.php)

```php
// Test route untuk broadcast dengan image
Route::post('/test/broadcast-image', [TestBroadcastController::class, 'testBroadcastWithImage']);
```

#### Expected Result

Setelah mengirim notification:

1. **Android Device** akan menerima notification dengan:
   - 🖼️ **BigPictureStyle**: Image ditampilkan dalam notification yang expanded
   - 📱 **Thumbnail**: Image kecil saat notification collapsed
   - 🔊 **Custom Sound**: Sound khusus untuk broadcast (`kobo_broadcast.mp3`)
   - 📳 **Vibration**: Pattern vibration untuk broadcast

2. **Logs di Android** akan menampilkan:
   ```
   D/ParkirkanFCM: ✅ Image loaded successfully, creating BigPictureStyle notification
   D/ParkirkanFCM: ✅ Original image loaded: 800x400
   D/ParkirkanFCM: ✅ Broadcast notification shown successfully with ID: 1234567890
   ```

3. **Jika Image Gagal**, akan menampilkan:
   ```
   W/ParkirkanFCM: ❌ Timeout loading image from URL: https://example.com/slow-image.jpg
   D/ParkirkanFCM: No valid image URL provided, creating BigTextStyle notification
   ```

#### 🧪 Testing v2.0 Solution

**Test 1: Data-Only Message (CORRECT)**
```bash
curl -X POST "https://fcm.googleapis.com/fcm/send" \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "USER_FCM_TOKEN",
    "data": {
      "notification_type": "broadcast",
      "notification_title": "Test Broadcast v2.0",
      "notification_body": "Testing data-only message with sound",
      "image_url": "https://picsum.photos/800/400?random=1"
    }
  }'
```

**Expected Result v2.0:**
```
D/ParkirkanFCM: 🔊 STEP 1: Playing broadcast sound IMMEDIATELY
D/ParkirkanFCM: ✅ IMMEDIATE: MediaPlayer prepared, starting playback
D/ParkirkanFCM: ✅ IMMEDIATE: RingtoneManager played as backup
D/ParkirkanFCM: ✅ Broadcast notification shown successfully
```

**Test 2: Wrong Approach (untuk comparison)**
```bash
curl -X POST "https://fcm.googleapis.com/fcm/send" \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "USER_FCM_TOKEN",
    "notification": {
      "title": "Test Broadcast WRONG",
      "body": "This will override sound"
    },
    "data": {
      "notification_type": "broadcast"
    }
  }'
```

**Expected Warning:**
```
W/ParkirkanFCM: ⚠️ FCM notification payload detected! This will override local sound settings
W/ParkirkanFCM: ⚠️ System notification sound disabled due to FCM payload interference
```

**Test 3: Monitor Logs Real-time**
```bash
# Terminal 1: Monitor logs
adb logcat -s ParkirkanFCM:D | grep -E "(IMMEDIATE|STEP|broadcast)"

# Terminal 2: Send test notification
curl -X POST "https://fcm.googleapis.com/fcm/send" ...
```

### Troubleshooting

#### Notification Tidak Muncul
- ✅ Check FCM token masih valid
- ✅ Check app permissions untuk notification
- ✅ Check notification channel settings

#### Image Tidak Tampil
- ✅ Check URL image bisa diakses di browser
- ✅ Check format file (JPG, PNG, WebP, dll)
- ✅ Check ukuran file tidak terlalu besar
- ✅ Check logs Android untuk error detail

#### Sound Tidak Keluar  
- ✅ Check volume device tidak dalam mode silent
- ✅ Check notification channel settings
- ✅ Check file `kobo_broadcast.mp3` ada di res/raw/

### 🔊 Sound Issues Troubleshooting (PENTING!)

Jika broadcast notification tidak mengeluarkan suara, ikuti langkah-langkah berikut:

#### 1. **Foreground vs Background Behavior**

- **Foreground (App Terbuka)**: FCM tidak otomatis play sound, kita menggunakan manual sound playing
- **Background (App Minimized)**: System yang handle notification, tapi FCM payload bisa override local settings

#### 2. **Check Android Logs**

Gunakan `adb logcat` untuk melihat logs:

```bash
adb logcat -s ParkirkanFCM:D
```

**Expected logs untuk broadcast notification:**
```
D/ParkirkanFCM: Playing broadcast sound manually
D/ParkirkanFCM: Notification volume: 7/15
D/ParkirkanFCM: ✅ Broadcast sound prepared, starting playback
D/ParkirkanFCM: ✅ Broadcast sound playback completed
D/ParkirkanFCM: ✅ Broadcast notification shown successfully
```

**Logs yang menunjukkan masalah:**
```
W/ParkirkanFCM: Device is in Do Not Disturb mode, sound may not play
W/ParkirkanFCM: Notification volume is 0, sound will not be audible
E/ParkirkanFCM: ❌ Error playing broadcast sound: what=1, extra=-19
```

#### 3. **Check Device Settings**

**Notification Volume:**
- Settings → Sound → Volume → Notifications (harus > 0)
- Atau gunakan volume rocker saat di home screen

**Do Not Disturb:**
- Settings → Sound → Do Not Disturb (harus OFF atau Allow notifications)

**App Notification Settings:**
- Settings → Apps → ParkirkanApp → Notifications
- Pastikan "Allow notifications" ON
- Check "Broadcast Notifications" channel settings

#### 4. **FCM Payload Debugging**

**⚠️ CRITICAL WARNING: FCM Notification Payload akan Override Local Sound Settings!**

Masalah utama sound broadcast tidak keluar adalah ketika server mengirim `notification` payload bersamaan dengan `data` payload. FCM akan menggunakan sistem notification dan mengabaikan local sound configuration.

**❌ JANGAN kirim sound di notification payload:**
```json
{
  "notification": {
    "title": "Broadcast",
    "body": "Message",
    "sound": "default"  ← HAPUS INI untuk foreground
  }
}
```

**✅ GUNAKAN ini (DATA-ONLY MESSAGE):**
```json
{
  "to": "DEVICE_TOKEN",
  "data": {
    "notification_type": "broadcast",
    "notification_title": "Judul Broadcast", 
    "notification_body": "Pesan broadcast",
    "image_url": "https://example.com/image.jpg"
  }
  // NO "notification" field sama sekali!
}
```

**🛠️ UPDATE: Solusi Terbaru (v2.0)**

Aplikasi Android sekarang sudah dilengkapi dengan:

1. **Multi-layer Sound Playing**: 
   - MediaPlayer (primary)
   - RingtoneManager (backup)
   - SoundPool (immediate fallback)

2. **Audio Focus Management**: Request audio focus untuk memastikan sound terdengar

3. **FCM Payload Detection**: Aplikasi akan detect dan warning jika server mengirim notification payload

4. **Immediate Sound Playing**: Sound dimainkan SEGERA sebelum notification dibuild

**Expected Logs (v2.0):**
```
D/ParkirkanFCM: 🔊 STEP 1: Playing broadcast sound IMMEDIATELY
D/ParkirkanFCM: 📳 STEP 2: Triggering vibration IMMEDIATELY  
D/ParkirkanFCM: 📱 STEP 3: Building notification
D/ParkirkanFCM: ✅ IMMEDIATE: MediaPlayer prepared, starting playback
D/ParkirkanFCM: ✅ IMMEDIATE: RingtoneManager played as backup
D/ParkirkanFCM: ✅ IMMEDIATE: SoundPool played as tertiary backup
```

**Jika FCM payload terdetect:**
```
W/ParkirkanFCM: ⚠️ FCM notification payload detected! This will override local sound settings
W/ParkirkanFCM: Server should send data-only message for broadcast notifications
W/ParkirkanFCM: ⚠️ System notification sound disabled due to FCM payload interference
```

#### 5. **Manual Testing Steps**

1. **Test Alert Notification** (harus ada suara):
   ```json
   {
     "data": {
       "notification_type": "alert",
       "title": "Test Alert",
       "message": "Should have alarm sound"
     }
   }
   ```

2. **Test Broadcast dengan logs**:
   ```bash
   # Terminal 1: Monitor logs
   adb logcat -s ParkirkanFCM:D
   
   # Terminal 2: Send notification
   curl -X POST "https://fcm.googleapis.com/fcm/send" \
     -H "Authorization: key=YOUR_KEY" \
     -H "Content-Type: application/json" \
     -d '{"to":"TOKEN","data":{"notification_type":"broadcast","notification_title":"Test","notification_body":"Test broadcast"}}'
   ```

3. **Check Audio Files**:
   ```bash
   # Verify audio files exist in APK
   aapt list app/build/outputs/apk/debug/app-debug.apk | grep "res/raw"
   ```

#### 6. **Common Solutions**

**Problem**: Sound hanya muncul di background, tidak di foreground
**Solution**: Manual sound playing sudah diimplementasikan ✅

**Problem**: Sound tidak muncul sama sekali  
**Solution**: 
- Check notification volume
- Check DND mode
- Check notification channel tidak di-disable user

**Problem**: Sound berbeda dengan yang diexpected
**Solution**: Ensure `kobo_broadcast.mp3` file benar di `res/raw/`

**Problem**: Notification muncul tapi tanpa sound
**Solution**: FCM notification payload mungkin override local settings - check server payload

#### 7. **Force Recreate Notification Channels**

Jika masih bermasalah, clear app data untuk recreate notification channels:

```bash
adb shell pm clear dev.agustacandi.parkirkanapp
```

Atau dalam kode (untuk testing):
```kotlin
// Force delete and recreate channels
val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
notificationManager.deleteNotificationChannel(MainApp.BROADCAST_CHANNEL_ID)
(application as MainApp).createNotificationChannels()
```

#### 8. **Test dengan Firebase Console**

1. Buka Firebase Console → Cloud Messaging
2. Send test message:
   - **Target**: Topic → `broadcast`
   - **Title**: `Test Broadcast Sound`
   - **Body**: `Testing broadcast notification sound`
   - **Advanced Options** → **Sound**: KOSONGKAN (jangan isi)
3. Check apakah ada suara

**Expected Result**: Notification muncul dengan custom sound `kobo_broadcast.mp3` 