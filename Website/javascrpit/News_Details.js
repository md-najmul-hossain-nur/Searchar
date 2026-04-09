(function renderNewsDetails() {
  var list = Array.isArray(window.SEARCHAR_NEWS) ? window.SEARCHAR_NEWS : [];
  if (list.length === 0) return;

  var params = new URLSearchParams(window.location.search);
  var newsId = params.get('news');
  var current = list.find(function (item) {
    return item.id === newsId;
  }) || list[0];

  var imageEl = document.getElementById('newsImage');
  var dateEl = document.getElementById('newsDate');
  var titleEl = document.getElementById('newsTitle');
  var descEl = document.getElementById('newsDesc');
  var bodyEl = document.getElementById('newsBody');

  if (!imageEl || !dateEl || !titleEl || !descEl || !bodyEl) return;

  imageEl.src = current.img;
  imageEl.alt = current.title;
  dateEl.textContent = current.date;
  titleEl.textContent = current.title;
  descEl.textContent = current.desc;

  bodyEl.innerHTML = '';
  (current.details || []).forEach(function (line) {
    var p = document.createElement('p');
    p.textContent = line;
    bodyEl.appendChild(p);
  });
})();
