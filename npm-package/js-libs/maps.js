Application.Maps = {};

Application.Maps.MarkerIconTemmplate = `
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32px" height="44px" viewBox="0 0 32 43" version="1.1">
  <g id="surface1">
    <path style=" stroke:none;fill-rule:nonzero;fill:<%- colorA %>;fill-opacity:0.988235;" d="M 15.992188 42.542969 C 15.894531 42.3125 13.894531 40.382812 12.460938 38.625 C 3.96875 27.5 -5.5 17.160156 5.554688 4.597656 C 10.453125 -0.0703125 16.488281 -0.335938 22.695312 1.839844 C 40.167969 11.816406 27.515625 28.09375 19.210938 38.792969 Z M 21.25 25.488281 C 32.902344 17.261719 23.894531 1.425781 11.085938 6.691406 C 7.347656 8.65625 5.492188 12.082031 5.429688 16.117188 C 5.25 20.046875 7.363281 23.332031 10.714844 25.34375 C 13.113281 26.632812 14.171875 26.867188 16.890625 26.707031 C 18.652344 26.601562 19.667969 26.320312 21.25 25.488281 Z M 21.25 25.488281 "/>
    <path style=" stroke:none;fill-rule:nonzero;fill:<%- colorA %>;fill-opacity:0.988235;" d="M 12.652344 29.414062 C 4.417969 27.722656 -0.855469 19.945312 0.847656 12 C 2.550781 4.054688 10.589844 -1.074219 18.847656 0.515625 C 27.105469 2.105469 32.480469 9.820312 30.882812 17.785156 C 29.285156 25.75 21.308594 30.976562 13.035156 29.488281 "/>
    <path style=" stroke:none;fill-rule:nonzero;fill:<%- colorB %>;fill-opacity:1;" d="M 14.304688 23.324219 C 10.257812 22.453125 7.664062 18.453125 8.503906 14.363281 C 9.339844 10.277344 13.292969 7.640625 17.347656 8.460938 C 21.40625 9.277344 24.046875 13.246094 23.261719 17.339844 C 22.476562 21.4375 18.558594 24.125 14.492188 23.359375 "/>
</g>
</svg>
`

Application.Maps.LocationTTL = (5 * 60 * 1000)

Application.Maps.GetLocation = function (onLocation, defaultLat, defaultLng) {
  var defaultCoords = {
    lat: defaultLat,
    lng: defaultLng
  };

  if (navigator && navigator.geolocation) {
    var
      cacheKeyName = 'MyLastLocation',
      cachedCoords = $.jStorage.get(cacheKeyName, null);

    if (cachedCoords) {
      console.log("Application.Maps.GetLocation: from cache");
      onLocation(cachedCoords);
    } else {
      navigator.geolocation.getCurrentPosition(function (location) {
        var realCoords = {
          lat: location.coords.latitude,
          lng: location.coords.longitude
        };

        $.jStorage.set(cacheKeyName, realCoords);
        $.jStorage.setTTL(cacheKeyName, Application.Maps.LocationTTL);

        console.log("Application.Maps.GetLocation: from browser");
        onLocation(realCoords)
      }, function (err) {
        console.log(`Application.Maps.GetLocation: ex: ${err.message}`);
        onLocation(defaultCoords);
      });
    }
  } else {
    onLocation(defaultCoords);
  }
}


Application.Maps.MapWithPointer = function (mapDiv, lat, lng, onPoint) {
  if (mapDiv.length > 0 && lat && lng) {
    var map = L.map(mapDiv.attr('id'));
    map.setView([lat, lng], 18)

    var divIcon = L.divIcon({
      className: 'map-svg-icon',
      html: _.template(Application.Maps.MarkerIconTemmplate)({colorA: '#6bb7f5', colorB: '#6c5354'}),
    });

    var marker = L.marker([lat, lng], {icon: divIcon, draggable: 'true'});
    marker.on('dragend', function(event){
      var marker = event.target;
      var position = marker.getLatLng();

      marker.setLatLng(new L.LatLng(position.lat, position.lng),{draggable:'true'});
      map.panTo(new L.LatLng(position.lat, position.lng))

      if (onPoint){
        onPoint(position.lat, position.lng)
      }
    });
    map.addLayer(marker);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 24,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
  }
}

Application.Maps.Map = function (mapDiv) {
  if (mapDiv.length > 0) {
    var
      lat = mapDiv.attr('data-lat'),
      lng = mapDiv.attr('data-lng'),
      zoom = mapDiv.attr('data-zoom');

    if (lat && lng) {
      var map = L.map(mapDiv.attr('id')), markers = new L.FeatureGroup();

      map.setView([lat, lng], zoom || 13)
      map.addLayer(markers);
      markers.clearLayers();

      var divIcon = L.divIcon({
        className: 'map-svg-icon',
        html: _.template(Application.Maps.MarkerIconTemmplate)({colorA: '#6bb7f5', colorB: '#6c5354'})
      });
      L.marker([lat, lng], {icon: divIcon}).addTo(markers);

      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 24,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      }).addTo(map);
    }
  }
};