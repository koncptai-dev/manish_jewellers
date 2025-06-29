importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-auth.js');

firebase.initializeApp({
    apiKey: "AIzaSyA85FzkpPaOMBTVju5PMIVRc4kqUy1iGqU",
    authDomain: "manishjewellers-cf71a.firebaseapp.com",
    projectId: "manishjewellers-cf71a",
    storageBucket: "manishjewellers-cf71a.appspot.com",
    messagingSenderId: "948376471552",
    appId: "1:448385715117:android:a7b41100df9e24ab736f24",
    measurementId: "448385715117"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function(payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body || '',
        icon: payload.data.icon || ''
    });
});