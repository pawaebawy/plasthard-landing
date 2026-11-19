(function () {
  // Идентификатор виджета из кода установки Jivo:
  // <script src="https://code.jivo.ru/widget/ЭТОТ_ИДЕНТИФИКАТОР" async></script>
  var widgetId = '';

  if (!widgetId) { return; }

  var script = document.createElement('script');
  script.src = 'https://code.jivo.ru/widget/' + widgetId;
  script.async = true;
  document.head.appendChild(script);
})();
