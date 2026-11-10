(function () {
  var bar = document.getElementById('cookieBar');
  if (!bar) { return; }

  var accepted = document.cookie.split(';').some(function (part) {
    return part.trim().indexOf('plasthard_cookie_ok=') === 0;
  });

  if (!accepted) { bar.hidden = false; }

  var button = document.getElementById('cookieAccept');
  if (!button) { return; }

  button.addEventListener('click', function () {
    document.cookie = 'plasthard_cookie_ok=1; path=/; max-age=31536000; SameSite=Lax';
    bar.hidden = true;
  });
})();
