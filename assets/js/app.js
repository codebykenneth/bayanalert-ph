// BayanAlert PH - shared front-end behavior

document.addEventListener('DOMContentLoaded', function () {
  // Auto-dismiss flash toasts after 6s
  document.querySelectorAll('.flash-toast').forEach(function (el) {
    setTimeout(function () {
      var alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, 6000);
  });
});

/**
 * Populate latitude/longitude inputs using the browser geolocation API.
 * Used on SOS and Report Incident forms.
 */
function bayanGetLocation(latFieldId, lngFieldId, statusElId, onSuccess) {
  var statusEl = statusElId ? document.getElementById(statusElId) : null;
  if (!navigator.geolocation) {
    if (statusEl) statusEl.textContent = 'Geolocation is not supported by your browser. Please enter location manually.';
    return;
  }
  if (statusEl) statusEl.textContent = 'Getting your current location...';
  navigator.geolocation.getCurrentPosition(function (pos) {
    document.getElementById(latFieldId).value = pos.coords.latitude.toFixed(7);
    document.getElementById(lngFieldId).value = pos.coords.longitude.toFixed(7);
    if (statusEl) statusEl.textContent = 'Location captured: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
    if (typeof onSuccess === 'function') onSuccess(pos.coords.latitude, pos.coords.longitude);
  }, function (err) {
    if (statusEl) statusEl.textContent = 'Could not get location automatically (' + err.message + '). Please enter it manually or tap the map.';
  }, { enableHighAccuracy: true, timeout: 10000 });
}
