// BayanAlert PH - Leaflet map logic
// Renders markers for incidents, evacuation centers, and emergency facilities.

var bayanMap, bayanMarkers = [], bayanLayer;

var ICONS = {
  incident: { color: '#ce1126', icon: 'triangle-exclamation' },
  Hospital: { color: '#0077b6', icon: 'hospital' },
  'Police Station': { color: '#1b3a8f', icon: 'building-shield' },
  'Fire Station': { color: '#f4681c', icon: 'fire' },
  'Ambulance Station': { color: '#2b9348', icon: 'truck-medical' },
  'Disaster Risk Reduction Office': { color: '#6c757d', icon: 'building-columns' },
  'Emergency Shelter': { color: '#7048e8', icon: 'house-chimney' },
  evacuation: { color: '#2b9348', icon: 'house-chimney' }
};

function bayanDivIcon(type) {
  var cfg = ICONS[type] || ICONS.incident;
  return L.divIcon({
    className: '',
    html: '<div style="background:' + cfg.color + ';width:30px;height:30px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.35);border:2px solid #fff;">' +
          '<i class="fa-solid fa-' + cfg.icon + '" style="transform:rotate(45deg);color:#fff;font-size:13px;"></i></div>',
    iconSize: [30, 30],
    iconAnchor: [15, 30],
    popupAnchor: [0, -28]
  });
}

function initBayanMap(opts) {
  bayanMap = L.map('map').setView(opts.center, opts.zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(bayanMap);

  bayanLayer = L.layerGroup().addTo(bayanMap);

  fetch(opts.dataUrl)
    .then(function (res) { return res.json(); })
    .then(function (data) {
      bayanMarkers = data.locations || [];
      renderMarkers('all');
    })
    .catch(function () {
      console.error('Failed to load map data.');
    });

  var filterButtons = document.querySelectorAll('#mapFilters button');
  filterButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterButtons.forEach(function (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline-primary'); });
      btn.classList.remove('btn-outline-primary'); btn.classList.add('btn-primary');
      renderMarkers(btn.dataset.filter);
    });
  });
}

function renderMarkers(filter) {
  bayanLayer.clearLayers();
  bayanMarkers.forEach(function (loc) {
    var matches = filter === 'all' ||
      (filter === 'incident' && loc.category === 'incident') ||
      (filter === 'evacuation' && loc.category === 'evacuation') ||
      (loc.type === filter);
    if (!matches) return;

    var iconKey = loc.category === 'incident' ? 'incident' : (loc.category === 'evacuation' ? 'evacuation' : loc.type);
    var marker = L.marker([loc.lat, loc.lng], { icon: bayanDivIcon(iconKey) });
    var popup = '<div style="min-width:200px;">' +
      '<strong>' + escapeHtml(loc.name) + '</strong><br>' +
      '<span class="text-muted small">' + escapeHtml(loc.type || '') + '</span><br>';
    if (loc.address) popup += '<div class="small mt-1"><i class="fa-solid fa-location-dot"></i> ' + escapeHtml(loc.address) + '</div>';
    if (loc.status) popup += '<div class="small">Status: <strong>' + escapeHtml(loc.status) + '</strong></div>';
    if (loc.description) popup += '<div class="small mt-1">' + escapeHtml(loc.description) + '</div>';
    if (loc.contact) popup += '<div class="small mt-1"><i class="fa-solid fa-phone"></i> ' + escapeHtml(loc.contact) + '</div>';
    popup += '</div>';
    marker.bindPopup(popup);
    bayanLayer.addLayer(marker);
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, function (m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
  });
}
