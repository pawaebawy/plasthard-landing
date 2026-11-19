(function () {
  // Номер счётчика из кода установки Яндекс Метрики. Пока пусто — тег не грузится.
  var metrikaId = '';
  if (!/^\d+$/.test(metrikaId)) { return; }

  var tagSrc = 'https://mc.yandex.ru/metrika/tag.js?id=' + metrikaId;

  (function (m, e, t, r, i, k, a) {
    m[i] = m[i] || function () {
      (m[i].a = m[i].a || []).push(arguments);
    };
    m[i].l = 1 * new Date();
    for (var j = 0; j < document.scripts.length; j++) {
      if (document.scripts[j].src === r) { return; }
    }
    k = e.createElement(t);
    a = e.getElementsByTagName(t)[0];
    k.async = 1;
    k.src = r;
    a.parentNode.insertBefore(k, a);
  })(window, document, 'script', tagSrc, 'ym');

  ym(Number(metrikaId), 'init', {
    ssr: true,
    clickmap: true,
    referrer: document.referrer,
    url: location.href,
    accurateTrackBounce: true,
    trackLinks: true
  });
})();
