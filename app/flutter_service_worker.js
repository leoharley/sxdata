'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';

const RESOURCES = {"assets/AssetManifest.bin": "57b5ce96996a64da1755b37968bd7641",
"assets/AssetManifest.bin.json": "ab027b7699d33e7af97f197d89325c8c",
"assets/AssetManifest.json": "175e9c7597d1dc50c68b467b90d061ef",
"assets/assets/fonts/Roboto-Bold.ttf": "ee7b96fa85d8fdb8c126409326ac2d2b",
"assets/assets/fonts/Roboto-Medium.ttf": "d08840599e05db7345652d3d417574a9",
"assets/assets/fonts/Roboto-Regular.ttf": "3e1af3ef546b9e6ecef9f3ba197bf7d2",
"assets/assets/images/icone_socialx.png": "748e0a88c694478b08f2c7219c32994b",
"assets/assets/images/icone_socialx_foreground.png": "79c41c572c6bb16767d45e71ab8fd6c0",
"assets/assets/images/logo_socialx.png": "300a7b0e17b13eb1855a477d81fd8ed3",
"assets/assets/images/Logo_verde2.png": "fc67a0e17ee76b49f20a160566a2f70e",
"assets/assets/images/X.png": "bdfb217e27e4a16014124f1308d2cf42",
"assets/assets/images/X_negativo.png": "9ac85fb3d582ca82ca94eb4d96c87476",
"assets/FontManifest.json": "4f98c153db1e7acf8e51da5cda1effc0",
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
"flutter_bootstrap.js": "530c54541457de3582a6a438a6901586",
"icons/Icon-192.png": "a62f59dada275159e16f9ac2330eaab6",
"icons/Icon-512.png": "91a7df9dec88023bfbd3c6ad36d70591",
"icons/Icon-maskable-192.png": "a62f59dada275159e16f9ac2330eaab6",
"icons/Icon-maskable-512.png": "91a7df9dec88023bfbd3c6ad36d70591",
"index.html": "9c50c8df6e0b225867894b59228fca5e",
"/": "9c50c8df6e0b225867894b59228fca5e",
"main.dart.js": "e0eb436a0ea61a86b1ac1f915b92644c",
"manifest.json": "1dda81c846eba856cf5c60e3e70bc0bb",
"version.json": "b7be5dc8eaf800c875287f1713f9d3a6"};
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
        var key = sxChave(request.url);
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
// Fazer o app abrir OFFLINE quando publicado em um SUBDIRETÓRIO (/app/).
// Explicação completa em tool/patch_service_worker.dart, no sxdata-app.
// -----------------------------------------------------------------------

// Chave de um recurso relativa ao ESCOPO do worker ("main.dart.js"), não ao
// origin ("app/main.dart.js"). Declaração de função: é içada, então vale
// também no activate e no downloadOffline do Flutter, onde o patch a usa.
function sxChave(url) {
  var escopo = new URL(self.registration.scope).pathname;
  var u = new URL(url);
  if (u.origin !== self.location.origin || u.pathname.indexOf(escopo) !== 0) {
    return u.href;   // fora do app: nunca casa com RESOURCES
  }
  var chave = u.pathname.substring(escopo.length);
  return chave === '' ? '/' : chave;
}

(function () {
  // Rede com prazo: sinal fraco não pode segurar a abertura do app
  const PRAZO_REDE_MS = 4000;

  function comPrazo(promessa, ms) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('prazo')), ms);
      promessa.then(
        (valor) => { clearTimeout(timer); resolve(valor); },
        (erro) => { clearTimeout(timer); reject(erro); }
      );
    });
  }

  // Rede primeiro, com prazo; se falhar, demorar ou responder com erro
  // (servidor em manutenção, 5xx), a cópia guardada em "chaveCache". Uma
  // resposta boa da rede atualiza a cópia, mesmo se chegar depois do prazo.
  function redeComPrazo(request, chaveCache, semCache) {
    const rede = fetch(request).then((response) => {
      if (response && response.ok) {
        const copia = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(chaveCache, copia));
      }
      return response;
    });
    rede.catch(() => {});   // falha depois do prazo: já respondemos do cache

    const guardado = () => caches.open(CACHE_NAME)
      .then((cache) => cache.match(chaveCache, { ignoreVary: true }));

    return comPrazo(rede, PRAZO_REDE_MS).then(
      // A rede respondeu: com erro, prefere a cópia; sem cópia, o próprio erro
      (response) => (response && response.ok)
        ? response
        : guardado().then((copia) => copia || response),
      // Sem rede ou estourou o prazo
      () => guardado().then((copia) => {
        if (copia) return copia;
        if (semCache) return semCache();
        return rede;   // nada guardado: espera a rede, sem prazo
      })
    );
  }

  self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    // --- Navegação: sempre o index.html ---------------------------------
    if (event.request.mode === 'navigate') {
      event.respondWith(
        redeComPrazo(event.request, 'index.html', () => {
          return new Response(
            '<h1>App indisponível offline</h1>' +
            '<p>Abra o aplicativo uma vez com internet para que ele ' +
            'fique disponível sem conexão.</p>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
          );
        })
      );
      return;
    }

    let key = sxChave(event.request.url);
    if (!RESOURCES[key]) return;              // não é recurso do app

    // --- version.json (vem com ?cachebuster=): rede com prazo -----------
    if (key === 'version.json') {
      event.respondWith(redeComPrazo(event.request, 'version.json'));
      return;
    }

    // --- Demais recursos: cache primeiro --------------------------------
    // Casa pela própria Request e, se não achar, pela chave sem a query
    // string (é assim que a pré-carga abaixo guarda).
    event.respondWith(
      caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request, { ignoreVary: true })
          .then((cached) => cached || cache.match(key, { ignoreVary: true }))
          .then((cached) => {
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

  // --- Pré-carga do app inteiro ------------------------------------------
  // Os mapas de depuração (.symbols), o motor skwasm (só usado em build
  // --wasm) e as licenças não são necessários para abrir o app.
  const DISPENSAVEIS = /(\.symbols$|^canvaskit\/skwasm|^assets\/NOTICES$)/;

  async function sxPrecache() {
    const cache = await caches.open(CACHE_NAME);
    const faltando = [];
    for (const chave of Object.keys(RESOURCES)) {
      if (chave === '/' || DISPENSAVEIS.test(chave)) continue;
      if (await cache.match(chave, { ignoreVary: true })) continue;
      faltando.push(chave);
    }

    // Três downloads por vez; o que falhar fica para a próxima abertura
    let i = 0;
    async function proximo() {
      while (i < faltando.length) {
        const chave = faltando[i++];
        try {
          const response = await fetch(new Request(chave, { cache: 'reload' }));
          if (response && response.ok) await cache.put(chave, response);
        } catch (e) { /* sem rede agora */ }
      }
    }
    await Promise.all([proximo(), proximo(), proximo()]);
  }

  self.addEventListener('message', (event) => {
    if (event.data === 'sxdataPrecache') {
      event.waitUntil(sxPrecache());
    }
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
    var key = sxChave(request.url);
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
