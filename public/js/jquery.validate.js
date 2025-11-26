
// // Check if firebaseConfig is already declared to avoid duplicate declaration
// if (typeof firebaseConfig === 'undefined') {
//     var firebaseConfig = {
//         apiKey: $.decrypt($.cookie('XSRF-TOKEN-AK')),
//         authDomain: $.decrypt($.cookie('XSRF-TOKEN-AD')),
//         databaseURL: $.decrypt($.cookie('XSRF-TOKEN-DU')),
//         projectId: $.decrypt($.cookie('XSRF-TOKEN-PI')),
//         storageBucket: $.decrypt($.cookie('XSRF-TOKEN-SB')),
//         messagingSenderId: $.decrypt($.cookie('XSRF-TOKEN-MS')),
//         appId: $.decrypt($.cookie('XSRF-TOKEN-AI')),
//         measurementId: $.decrypt($.cookie('XSRF-TOKEN-MI'))
//     }
// }
//
// // Initialize Firebase only if not already initialized
// if (!firebase.apps.length) {
//     firebase.initializeApp(firebaseConfig);
//     console.log('✅ Firebase initialized in jquery.validate.js');
// } else {
//     console.log('✅ Firebase already initialized, skipping duplicate initialization');
// }
