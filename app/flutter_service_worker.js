'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';

const RESOURCES = {"assets/AssetManifest.bin": "a186e382a201077edce2752ce2012bbb",
"assets/AssetManifest.bin.json": "aeef1a37359ac77e971d1c8e040b5e26",
"assets/AssetManifest.json": "f3b0cfcbac9a11e4a218c2b0e5079976",
"assets/assets/images/icone_socialx.png": "748e0a88c694478b08f2c7219c32994b",
"assets/assets/images/icone_socialx_foreground.png": "79c41c572c6bb16767d45e71ab8fd6c0",
"assets/assets/images/logo_socialx.png": "300a7b0e17b13eb1855a477d81fd8ed3",
"assets/assets/images/Logo_verde2.png": "fc67a0e17ee76b49f20a160566a2f70e",
"assets/assets/images/X.png": "bdfb217e27e4a16014124f1308d2cf42",
"assets/assets/images/X_negativo.png": "9ac85fb3d582ca82ca94eb4d96c87476",
"assets/FontManifest.json": "dc3d03800ccca4601324923c0b1d6d57",
"assets/fonts/MaterialIcons-Regular.otf": "1391e89269578aeb625d123ab39c23f7",
"assets/NOTICES": "ed3b26068e4afecb2768d29326c11b4f",
"assets/packages/cupertino_icons/assets/CupertinoIcons.ttf": "33b7d9392238c04c131b6ce224e13711",
"assets/packages/flutter_map/lib/assets/flutter_map_logo.png": "208d63cc917af9713fc9572bd5c09362",
"assets/packages/record_web/assets/js/record.fixwebmduration.js": "1f0108ea80c8951ba702ced40cf8cdce",
"assets/packages/record_web/assets/js/record.worklet.js": "6d247986689d283b7e45ccdf7214c2ff",
"assets/packages/wakelock_plus/assets/no_sleep.js": "7748a45cd593f33280669b29c2c8919a",
"assets/shaders/ink_sparkle.frag": "ecc85a2e95f5e9f53123dcaf8cb9b6ce",
"canvaskit/canvaskit.js": "728b2d477d9b8c14593d4f9b82b484f3",
"canvaskit/canvaskit.js.symbols": "bdcd3835edf8586b6d6edfce8749fb77",
"canvaskit/canvaskit.wasm": "7a3f4ae7d65fc1de6a6e7ddd3224bc93",
"canvaskit/chromium/canvaskit.js": "8191e843020c832c9cf8852a4b909d4c",
"canvaskit/chromium/canvaskit.js.symbols": "b61b5f4673c9698029fa0a746a9ad581",
"canvaskit/chromium/canvaskit.wasm": "f504de372e31c8031018a9ec0a9ef5f0",
"canvaskit/skwasm.js": "ea559890a088fe28b4ddf70e17e60052",
"canvaskit/skwasm.js.symbols": "e72c79950c8a8483d826a7f0560573a1",
"canvaskit/skwasm.wasm": "39dd80367a4e71582d234948adc521c0",
"favicon.png": "e0924fa2568064586ccb3467028d42a2",
"flutter.js": "83d881c1dbb6d6bcd6b42e274605b69c",
"flutter_bootstrap.js": "656d9dc3f8a63f5bb6300647b523b6b5",
"icons/Icon-192.png": "a62f59dada275159e16f9ac2330eaab6",
"icons/Icon-512.png": "91a7df9dec88023bfbd3c6ad36d70591",
"icons/Icon-maskable-192.png": "a62f59dada275159e16f9ac2330eaab6",
"icons/Icon-maskable-512.png": "91a7df9dec88023bfbd3c6ad36d70591",
"index.html": "08b782bc22630a0be689c4f20fc63ce7",
"/": "08b782bc22630a0be689c4f20fc63ce7",
"main.dart.js": "c757ad9b95eeabe59a22e587c27ad7fa",
"manifest.json": "1dda81c846eba856cf5c60e3e70bc0bb",
"version.json": "09417c1877393c896ae23555efacec09"};
// The application shell files that are downloaded before a service worker can
// start.
const CORE = ["main.dart.js",
"index.html",
"flutter_bootstrap.js",
"assets/AssetManifest.bin.json",
"assets/FontManifest.json"];

// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  self.skipWaiting();
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      return cache.addAll(
        CORE.map((value) => new Request(value, {'cache': 'reload'})));
    })
  );
});
// During activate, the cache is populated with the temp files downloaded in
// install. If this service worker is upgrading from one with a saved
// MANIFEST, then use this to retain unchanged resource files.
self.addEventListener("activate", function(event) {
  return event.waitUntil(async function() {
    try {
      var contentCache = await caches.open(CACHE_NAME);
      var tempCache = await caches.open(TEMP);
      var manifestCache = await caches.open(MANIFEST);
      var manifest = await manifestCache.match('manifest');
      // When there is no prior manifest, clear the entire cache.
      if (!manifest) {
        await caches.delete(CACHE_NAME);
        contentCache = await caches.open(CACHE_NAME);
        for (var request of await tempCache.keys()) {
          var response = await tempCache.match(request);
          await contentCache.put(request, response);
        }
        await caches.delete(TEMP);
        // Save the manifest to make future upgrades efficient.
        await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
        // Claim client to enable caching on first launch
        self.clients.claim();
        return;
      }
      var oldManifest = await manifest.json();
      var origin = self.location.origin;
      for (var request of await contentCache.keys()) {
        var key = request.url.substring(origin.length + 1);
        if (key == "") {
          key = "/";
        }
        // If a resource from the old manifest is not in the new cache, or if
        // the MD5 sum has changed, delete it. Otherwise the resource is left
        // in the cache and can be reused by the new service worker.
        if (!RESOURCES[key] || RESOURCES[key] != oldManifest[key]) {
          await contentCache.delete(request);
        }
      }
      // Populate the cache with the app shell TEMP files, potentially overwriting
      // cache files preserved above.
      for (var request of await tempCache.keys()) {
        var response = await tempCache.match(request);
        await contentCache.put(request, response);
      }
      await caches.delete(TEMP);
      // Save the manifest to make future upgrades efficient.
      await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
      // Claim client to enable caching on first launch
      self.clients.claim();
      return;
    } catch (err) {
      // On an unhandled exception the state of the cache cannot be guaranteed.
      console.error('Failed to upgrade service worker: ' + err);
      await caches.delete(CACHE_NAME);
      await caches.delete(TEMP);
      await caches.delete(MANIFEST);
    }
  }());
});
// The fetch handler redirects requests for RESOURCE files to the service
// worker cache.
// SXDATA_OFFLINE_SUBDIR_FIX
// -----------------------------------------------------------------------
// Fazer o app funcionar offline quando publicado em um SUBDIRETÓRIO.
//
// O handler do Flutter calcula a chave do cache a partir do ORIGIN:
//
//     key = url.substring(origin.length + 1)
//
// Em /painel/app/ isso produz "painel/app/main.dart.js", enquanto
// RESOURCES guarda "main.dart.js" (caminhos relativos ao escopo). Nada
// bate, o worker devolve tudo ao navegador e, sem internet:
//   - a navegação dava a tela "Você está off-line" do navegador;
//   - com a navegação corrigida, os assets continuavam falhando e a
//     página ficava EM BRANCO.
//
// Este handler recalcula a chave relativa ao ESCOPO do worker e serve
// tudo do cache. Vem antes do handler do Flutter: o primeiro a chamar
// respondWith vence.
// -----------------------------------------------------------------------
(function () {
  // Escopo do worker: ".../painel/app/". A chave de cada recurso é a URL
  // menos esse prefixo.
  const SCOPE = new URL(self.registration ? self.registration.scope : './', self.location).pathname;

  function chaveDoEscopo(url) {
    const u = new URL(url);
    if (u.origin !== self.location.origin) return null;
    if (!u.pathname.startsWith(SCOPE)) return null;
    return u.pathname.substring(SCOPE.length);
  }

  self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    // --- Navegação: sempre o index.html ---------------------------------
    if (event.request.mode === 'navigate') {
      event.respondWith(
        fetch(event.request)
          .then((response) => {
            if (response && response.ok) {
              const copia = response.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put('index.html', copia);
              });
            }
            return response;
          })
          .catch(() => {
            return caches.open(CACHE_NAME).then((cache) => {
              return cache.match('index.html').then((cached) => {
                if (cached) return cached;
                return cache.match('/').then((raiz) => {
                  if (raiz) return raiz;
                  return new Response(
                    '<h1>App indisponível offline</h1>' +
                    '<p>Abra o aplicativo uma vez com internet para que ele ' +
                    'fique disponível sem conexão.</p>',
                    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                  );
                });
              });
            });
          })
      );
      return;
    }

    // --- Demais recursos: cache primeiro, chave relativa ao escopo ------
    let key = chaveDoEscopo(event.request.url);
    if (key === null) return;                 // outro domínio: não é nosso
    if (key.indexOf('?v=') !== -1) key = key.split('?v=')[0];
    if (key === '') key = '/';
    if (!RESOURCES[key]) return;              // não é recurso do app

    // O Flutter cacheia com contentCache.addAll(caminhos relativos), que o
    // navegador resolve contra o escopo — o cache guarda a URL completa.
    // Então casar pela própria Request funciona; o que faltava era não
    // desistir antes disso por causa da chave calculada pelo origin.
    event.respondWith(
      caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((cached) => {
          if (cached) return cached;
          return fetch(event.request).then((response) => {
            if (response && response.ok) {
              cache.put(event.request, response.clone());
            }
            return response;
          });
        });
      })
    );
  });
})();

self.addEventListener("fetch", (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  // Redirect URLs to the index.html
  if (key.indexOf('?v=') != -1) {
    key = key.split('?v=')[0];
  }
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#') || key == '') {
    key = '/';
  }
  // If the URL is not the RESOURCE list then return to signal that the
  // browser should take over.
  if (!RESOURCES[key]) {
    return;
  }
  // If the URL is the index.html, perform an online-first request.
  if (key == '/') {
    return onlineFirst(event);
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache only if the resource was successfully fetched.
        return response || fetch(event.request).then((response) => {
          if (response && Boolean(response.ok)) {
            cache.put(event.request, response.clone());
          }
          return response;
        });
      })
    })
  );
});
self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
    return;
  }
  if (event.data === 'downloadOffline') {
    downloadOffline();
    return;
  }
});
// Download offline will check the RESOURCES for all files not in the cache
// and populate them.
async function downloadOffline() {
  var resources = [];
  var contentCache = await caches.open(CACHE_NAME);
  var currentContent = {};
  for (var request of await contentCache.keys()) {
    var key = request.url.substring(origin.length + 1);
    if (key == "") {
      key = "/";
    }
    currentContent[key] = true;
  }
  for (var resourceKey of Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}
// Attempt to download the resource online before falling back to
// the offline cache.
function onlineFirst(event) {
  return event.respondWith(
    fetch(event.request).then((response) => {
      return caches.open(CACHE_NAME).then((cache) => {
        cache.put(event.request, response.clone());
        return response;
      });
    }).catch((error) => {
      return caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((response) => {
          if (response != null) {
            return response;
          }
          throw error;
        });
      });
    })
  );
}
