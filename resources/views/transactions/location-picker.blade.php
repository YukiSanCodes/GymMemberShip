<section class="field field-wide" style="margin-top:24px">
    <h2>Member location and weather</h2>
    <label for="address">Address (optional)</label>
    <input id="address" name="address" maxlength="255" value="{{ old('address') }}">
    <p>Select a location on the map or enter coordinates and click Go to location. Location sharing is optional.</p>
    <div class="form-grid">
        <div class="field"><label for="latitude">Latitude</label><input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}"></div>
        <div class="field"><label for="longitude">Longitude</label><input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}"></div>
    </div>
    <label class="location-consent"><input type="checkbox" name="location_consent" value="1" @checked(old('location_consent'))> The member consents to saving this location with their application.</label>
    <button type="button" id="go-to-location" class="button button-outline">Go to location</button>
    <div id="membership-map" style="height:320px;border-radius:16px;background:#eef2ef;margin:16px 0" aria-label="Choose member location"></div>
    <p id="map-status" role="status">{{ config('services.google_maps.key') ? 'Loading map…' : 'Map is unavailable. You can enter coordinates above.' }}</p>
    <button type="button" id="load-weather" class="button button-outline">Check weather</button>
    <p id="location-weather" role="status">Choose a location to check current weather.</p>
</section>
<script>
(() => {
 const lat = document.getElementById('latitude'), lon = document.getElementById('longitude');
 const status = document.getElementById('location-weather');
 let request, map, marker;
 function goToLocation() {
  const message = document.getElementById('map-status');
  if (!lat.value || !lon.value || !lat.checkValidity() || !lon.checkValidity()) {
   message.textContent = 'Enter latitude between -90 and 90 and longitude between -180 and 180.';
   return;
  }
  if (!map || !marker) { message.textContent = 'The map is not ready yet. Please wait for it to load.'; return; }
  const position = {lat:Number(lat.value),lng:Number(lon.value)};
  map.setCenter(position);
  map.setZoom(15);
  marker.position = position;
  marker.map = map;
  message.textContent = `Selected location: ${position.lat.toFixed(5)}, ${position.lng.toFixed(5)}.`;
  weather();
 }
 document.getElementById('go-to-location').addEventListener('click',goToLocation);
 [lat,lon].forEach(input => input.addEventListener('keydown',event => {
  if (event.key === 'Enter') { event.preventDefault(); goToLocation(); }
 }));
 async function weather() {
  if (!lat.value || !lon.value || !lat.checkValidity() || !lon.checkValidity()) { status.textContent = 'Enter valid latitude and longitude first.'; return; }
  request?.abort(); request = new AbortController(); status.textContent = 'Loading weather…';
  try {
   const response = await fetch(@json(route('weather.show', [], false)) + '?' + new URLSearchParams({lat:lat.value,lon:lon.value}), {headers:{Accept:'application/json'},signal:request.signal});
   if (!response.ok) throw new Error();
   const w = await response.json();
   status.textContent = `${w.place}: ${w.temperature ?? '—'}°C, ${w.description ?? 'Unavailable'}, humidity ${w.humidity ?? '—'}%.`;
  } catch(error) { if (error.name !== 'AbortError') status.textContent = 'Weather is currently unavailable. You can still save the transaction.'; }
 }
 document.getElementById('load-weather').addEventListener('click',weather);
 window.gm_authFailure = () => { document.getElementById('map-status').textContent = 'Map unavailable. Enter coordinates above.'; };
 window.initMembershipMap = async () => {
  const {Map} = await google.maps.importLibrary('maps');
  const {AdvancedMarkerElement} = await google.maps.importLibrary('marker');
  const selected = lat.value !== '' && lon.value !== '';
  const center = selected ? {lat:Number(lat.value),lng:Number(lon.value)} : {lat:14.5995,lng:120.9842};
  map = new Map(document.getElementById('membership-map'),{center,zoom:13,mapId:@json(config('services.google_maps.map_id'))});
  marker = new AdvancedMarkerElement({map:selected?map:null,position:center});
  document.getElementById('map-status').textContent = 'Click the map to select a location.';
  map.addListener('click',event => {
   lat.value=event.latLng.lat().toFixed(7);lon.value=event.latLng.lng().toFixed(7);
   marker.position=event.latLng;marker.map=map; weather();
  });
 };
})();
</script>
@if(config('services.google_maps.key'))
<script async src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.key')) }}&amp;loading=async&amp;callback=initMembershipMap" onerror="document.getElementById('map-status').textContent='Map unavailable. Enter coordinates above.'"></script>
@endif
