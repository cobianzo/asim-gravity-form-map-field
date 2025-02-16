window.initPolygonSetup = function (inputName) {
	// create the button that deletes any started polygon
	window.createClearPolygonButton(inputName);

	// crates the Google Maps polygon object
	window.createPolygonArea(inputName);

	// CLICK on the map >creates a new vertex for the polygon
	window.asimMaps[inputName].map.addListener('click', function (e) {
		const clickedCoordinates = `${e.latLng.lat()},${e.latLng.lng()}`;
		const inputElement = document.getElementById(inputName);
		inputElement.value += ' ' + clickedCoordinates;
		inputElement.value = inputElement.value.trim();

		window.paintPolygonFromInput(inputName);
	});

	// if there is a value in the input, we paint the polygon on page load.
	window.paintPolygonFromInput(inputName);
};

/**
 * Creates a button to clear the polygon drawn on the map and removes it from the input field.
 * The button is added to the bottom left of the map.
 *
 * @param {string} inputName - The name of the input field associated with the map, eg 'input_1_3'.
 *
 * @since 3.0.0
 */
window.createClearPolygonButton = function (inputName) {
	const mapSetup = window.asimMaps[inputName];
	const asimVars = window.asimVars || {};
	const clearPolygonButtonEl = document.createElement('button');
	clearPolygonButtonEl.innerHTML =
		'<img style="width:24px;" width="24" height="24" src="' + asimVars.asimClearPolygonIcon + '}" />';
	const id = `asim-clear-polygon-button-${inputName}`;
	clearPolygonButtonEl.id = id;
	clearPolygonButtonEl.classList.add('custom-map-control-button');
	clearPolygonButtonEl.title = 'Click to clear the area';
	clearPolygonButtonEl.style.margin = '0 0 5px';
	clearPolygonButtonEl.style.aspectRatio = '1 / 1';
	clearPolygonButtonEl.style.padding = '2px';
	clearPolygonButtonEl.style.border = '3px solid white';
	clearPolygonButtonEl.style.background = 'white';
	clearPolygonButtonEl.style.borderRadius = '50%';
	clearPolygonButtonEl.style.boxShadow = '3px 3px 10px black';

	const inputValue = document.getElementById(inputName).value;
	clearPolygonButtonEl.style.display = inputValue.trim().length ? 'block' : 'none';

	clearPolygonButtonEl.addEventListener('click', (e) => {
		e.preventDefault();
		window.clearPolygon(inputName);
	});

	window.google = window.google || null;
	mapSetup.map.controls[window.google.maps.ControlPosition.BOTTOM_LEFT].push(clearPolygonButtonEl);

	mapSetup.map.setOptions({ draggableCursor: 'crosshair' });
};

/**
 * Creates the polygon object associated to the map.
 *
 * @param {string} inputName - The name of the input field associated with the map, eg 'input_1_3'.
 *
 * @since 3.0.0
 */
window.createPolygonArea = function (inputName) {
	const mapSetup = window.asimMaps[inputName];

	mapSetup.polygonArea = new window.google.maps.Polygon({
		// paths: mapSetup.polygonCoords,
		strokeColor: '#FF0000',
		strokeOpacity: 0.8,
		strokeWeight: 2,
		fillColor: '#FF0000',
		fillOpacity: 0.35,
		map: mapSetup.map,
		editable: true,
	});

	mapSetup.map.getDiv().addEventListener('mouseup', function () {
		setTimeout(() => window.polygonCoordsToInput(inputName), 500);
	});
};

/**
 * Clears the polygon map and input value.
 *
 * @param {string} inputName
 *
 * @return {void}
 */
window.clearPolygon = function (inputName) {
	const mapSetup = window.asimMaps[inputName];
	const inputElement = document.getElementById(inputName);

	mapSetup.polygonArea.setPaths([]);
	inputElement.value = '';

	// remove also de marker
	window.removeMarker(inputName);

	mapSetup.map.setOptions({ draggableCursor: 'crosshair' });

	const clearPoligonBtn = document.getElementById(`asim-clear-polygon-button-${inputName}`);
	clearPoligonBtn.style.display = 'none';
};

window.paintPolygonFromInput = function (inputName) {
	const value = document.getElementById(inputName).value;
	if ('' === value) {
		return;
	}
	const mapSetup = window.asimMaps[inputName];
	const { polygonArea } = mapSetup;

	const coordinatesArray = value.split(' ');
	const newPolygonCoords = coordinatesArray.map((coord) => {
		const [lat, lng] = coord.split(',');
		return new window.google.maps.LatLng({ lat: parseFloat(lat), lng: parseFloat(lng) });
	});

	polygonArea.setPath(newPolygonCoords);

	const clearPoligonBtn = document.getElementById(`asim-clear-polygon-button-${inputName}`);
	if (clearPoligonBtn) clearPoligonBtn.style.display = coordinatesArray.length ? 'block' : 'none';
};

// Función para extraer las coordenadas del polígono en el formato deseado
window.polygonCoordsToInput = function (inputName) {
	const mapSetup = window.asimMaps[inputName];
	const path = mapSetup.polygonArea.getPath();
	if (!path) return;
	const coordenadas = [];
	path.forEach((latlng) => {
		coordenadas.push(latlng.lat() + ',' + latlng.lng());
	});

	const inputElement = document.getElementById(inputName);
	inputElement.value = coordenadas.join(' ').trim();
};
