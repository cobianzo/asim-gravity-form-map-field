// The loading of the maps happens after this script.
// Here we add the helpers functions used and binded to the map
// created in render.php

/**
 * Extracts coordinates from an input element.
 *
 * @param {HTMLInputElement} input
 * @return {Object|null} {lat, lng} or null if the input is empty or if the value is not a valid lat,lng pair.
 */
window.coordinatesFromInput = (input) => {
	const value = input.value;
	if (!value) return null;
	const [lat, lng] = value.split(',').map(parseFloat);
	if (!isNaN(lat) && !isNaN(lng)) {
		return { lat, lng };
	}
	return null;
};

/**
 * Centers the map at the coordinates input on the element.
 *
 * @param {HTMLInputElement} input The input element containing the coordinates
 * @param {Object}           map   The map element (google.maps.Map)
 */
window.centerMapAtInputCoordinates = (input, map) => {
	const coordinatesInput = window.coordinatesFromInput(input);
	if (coordinatesInput) {
		map.setCenter(coordinatesInput);
	} else {
		// eslint-disable-next-line
		alert('Please use format "lat,lng".');
	}
};

/**
 * Adds a marker to the map. If the map already has a marker, it is removed.
 *
 * @param {Object} position The position of the marker. (google.maps.LatLng)
 * @param {Object} map      The map to add the marker to. (google.maps.Map)
 */
window.addMarker = (position, map) => {
	if (map.marker) {
		map.marker.setMap(null); // Eliminar el marcador anterior.
	}

	map.marker = new window.google.maps.Marker({
		position,
		map,
	});
};

/**
 * Adds a custom "Location" button to a Google Map that centers the map
 * on the user's current geolocation when clicked. The button is styled
 * and positioned at the top left of the map. If geolocation is supported
 * and permitted by the user, the map is centered on their location, and
 * a marker is added. The input field associated with the map is updated
 * with the latitude and longitude of the user's location. If geolocation
 * is not supported or an error occurs, an alert message is displayed.
 *
 * @param {string} inputName - The name used to reference the specific map and input element.
 */

window.locationButton = function (inputName) {
	const mapSetup = window.asimMaps[inputName];

	// Crear un control para centrar el mapa en la ubicación del usuario
	const centerControlDiv = document.createElement('div');
	const centerControlButton = document.createElement('button');
	centerControlButton.textContent = 'Location';
	centerControlButton.classList.add('custom-map-control-button');
	centerControlDiv.appendChild(centerControlButton);

	// Estilo opcional para el botón
	centerControlButton.style.margin = '10px';
	centerControlButton.style.cursor = 'pointer';

	window.google = window.google || null;
	mapSetup.map.controls[window.google.maps.ControlPosition.TOP_LEFT].push(centerControlDiv);

	// Agregar evento al botón para centrar el mapa
	centerControlButton.addEventListener('click', (e) => {
		e.preventDefault();
		const btn = e.currentTarget;
		const currentText = btn.textContent;
		window.navigator = window.navigator || null;
		if (window.navigator.geolocation) {
			btn.textContent = 'Loading...';
			window.navigator.geolocation.getCurrentPosition(
				(position) => {
					const userLocation = {
						lat: position.coords.latitude,
						lng: position.coords.longitude,
					};
					mapSetup.map.setCenter(userLocation);
					window.addMarker(userLocation, mapSetup.map);
					mapSetup.inputElement.value = `${userLocation.lat},${userLocation.lng}`;
					btn.textContent = currentText;
				},
				() => {
					// eslint-disable-next-line
					alert('Could not get the user location.');
					btn.textContent = currentText;
				}
			);
		} else {
			// eslint-disable-next-line
			alert('The browser does not support geolocation.');
		}
	});
};
