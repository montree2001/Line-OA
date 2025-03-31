// Service Worker สำหรับ PWA

// ตั้งค่าชื่อ cache และไฟล์ที่จะเก็บ cache
const CACHE_NAME = 'line-attendance-v1';
const urlsToCache = [
  '/',
  '/home.php',
  '/check-in.php',
  '/attendance_history.php',
  '/announcements.php',
  '/profile.php',
  '/assets/css/student-home.css',
  'https://fonts.googleapis.com/icon?family=Material+Icons',
  'https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
  'https://code.jquery.com/jquery-3.6.4.min.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js'
];

// เมื่อติดตั้ง Service Worker
self.addEventListener('install', event => {
  console.log('Service Worker: Installing');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching Files');
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting())
  );
});

// เมื่อมีการ activate Service Worker
self.addEventListener('activate', event => {
  console.log('Service Worker: Activated');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// จัดการ fetch requests
self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Make a copy of the response
        const responseClone = response.clone();
        
        // Open cache
        caches.open(CACHE_NAME)
          .then(cache => {
            // Add response to cache
            cache.put(event.request, responseClone);
          });
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});

// จัดการกับการแจ้งเตือน Push Notification
self.addEventListener('push', event => {
  const data = event.data.json();
  console.log('Push Received...');
  
  const options = {
    body: data.body,
    icon: data.icon,
    data: {
      url: data.url
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// จัดการการคลิกที่การแจ้งเตือน
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url || 'home.php')
  );
}); 