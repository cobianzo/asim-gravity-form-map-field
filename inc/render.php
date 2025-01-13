<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Renders a map field for a Gravity Form.
 *
 * This function outputs the HTML and JavaScript necessary to display a map field within a Gravity Form.
 * It initializes a Google Map with specified coordinates and binds event listeners for interacting with the map.
 * The map and input field are linked, allowing users to select a location on the map which updates the input field.
 *
 * @param object $instance An instance of the GF field class.
 * @param array  $form     The GF form array containing form details.
 * @param string $value    The initial value for the map field, typically a comma-separated latitude and longitude.
 *
 * @return string The rendered HTML and JavaScript for the map field.
 */
function asim_render_map_field( $instance, $form, $value ) {

	$field_id = absint( $form['id'] );

	$input_id = sprintf( 'input_%d_%d', $form['id'], $instance->id );
	$name_id  = sprintf( 'input_%d', $instance->id );
	$field_id = sprintf( 'field_%d_%d', $form['id'], $instance->id );

	$is_entry_detail = $instance->is_entry_detail();
	$is_form_editor  = $instance->is_form_editor();

	$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

	$field_type         = $is_entry_detail || $is_form_editor ? 'text' : 'hidden';
	$field_type         = 'text'; //todelete
	$class_attribute    = $is_entry_detail || $is_form_editor ? '' : "class='gform_asim_map'";
	$required_attribute = $instance->isRequired ? 'aria-required="true"' : '';
	$invalid_attribute  = $instance->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';




	// Salir si estamos en el administrador.
	$latlng = explode( ',', $value );

	[$lat, $lng] = [ -34.397, 150.644 ]; // default
	if ( 2 === count( $latlng ) ) {
		[$lat, $lng] = $latlng;
	}


	ob_start();

	if ( empty( $instance->google_maps_api_key ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>El campo de mapas requiere una clave de API de Google Maps. <a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&subview=asim-gravity-form-map-field' ) ) . '">Configurar</a></p></div>';
		}
		return ob_get_clean();
	}

	?>
	<div id="map-container-<?php echo esc_attr( $field_id ); ?>"
		style="height: 300px; margin-bottom: 1rem;"></div>

	<input type="<?php echo esc_attr( $field_type ); ?>"
		readonly
		placeholder="<?php esc_attr_e( 'Latitude, Longitude', 'asim-gravity-form-map-field' ); ?>"
		name="<?php echo esc_attr( $name_id ); ?>"
		id="<?php echo esc_attr( $input_id ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $class_attribute;
		?>
		<?php echo $required_attribute; ?>
		<?php echo $invalid_attribute; ?>
		<?php echo $disabled_text; ?>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		?> />



	<script>

		window.asimMaps = window.asimMaps || {};

		asimMaps['<?php echo esc_js( $input_id ); ?>'] = {
			map: null,
			inputElement: null,
			initMap: () => {
				const input = document.getElementById('<?php echo esc_js( $input_id ); ?>');
				asimMaps['<?php echo esc_js( $input_id ); ?>'].inputElement = input;
				const coordinatesInput = window.coordinatesFromInput(input);
				const coordinates = coordinatesInput || {
					lat: -34.397,
					lng: 150.644
				};

				// Inicializar el mapa.
				const mapContainerEl = document.getElementById('map-container-<?php echo esc_js( $field_id ); ?>');
				const map = new google.maps.Map(mapContainerEl, {
					center: coordinates,
					disableDefaultUI: true, // Desactiva la interfaz predeterminada
					zoomControl: true,      // Activa los controles de zoom
					zoom: 8,
				});
				asimMaps['<?php echo esc_js( $input_id ); ?>'].mapContainerEl = mapContainerEl
				asimMaps['<?php echo esc_js( $input_id ); ?>'].map = map;

				window.locationButton('<?php echo esc_js( $input_id ); ?>');

				// Agregar marcador inicial si las coordenadas son válidas.
				if (coordinatesInput) {
					window.addMarker(coordinatesInput, map);
					window.centerMapAtInputCoordinates(input, map);
				}


				// CLICK on the map > sets a market
				asimMaps['<?php echo esc_js( $input_id ); ?>'].map.addListener('click', function(e) {
					const clickedCoordinates = e.latLng;
					const inputElement = document.getElementById('<?php echo esc_js( $input_id ); ?>');
					inputElement.value = `${clickedCoordinates.lat()},${clickedCoordinates.lng()}`;
					window.addMarker(clickedCoordinates, map);
				});
			}
		}

		// ---------------------------------------------------------------
		// Call to GoogleMapsAPI
		window.loadGoogleMapsAPI = function() {
			const script = document.createElement('script');
			script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js( $instance->google_maps_api_key ); ?>&callback=initAllMaps';
			script.async = true;
			document.head.appendChild(script);
		}

		// After GoogleMapsAPILoads
		// Executed only once.
		document.addEventListener("DOMContentLoaded", function() {
			if ( window.googleMapsAPILoaded !== true ) {

				window.initAllMaps = function () {
					setTimeout(function() {
						Object.keys(asimMaps).forEach(function(key) {
							asimMaps[key].initMap();
						});
					}, 500);
				}

				loadGoogleMapsAPI();

			} // end of the code exectued only once in the page.
			window.googleMapsAPILoaded = true;
		});

	</script>


	<?php
	return ob_get_clean();
}
