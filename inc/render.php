<?php // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

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
function asim_render_map_field( object $instance, array $form, string $value ): string {

	$field_id = absint( $form['id'] );

	$input_id = sprintf( 'input_%d_%d', $form['id'], $instance->id );
	$name_id  = sprintf( 'input_%d', $instance->id );
	$field_id = sprintf( 'field_%d_%d', $form['id'], $instance->id );

	$is_entry_detail = $instance->is_entry_detail();
	$is_form_editor  = $instance->is_form_editor();
	$is_admin        = $is_form_editor || $is_entry_detail;

	$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

	$field_type         = 'text';
	$class_attribute    = $is_entry_detail || $is_form_editor ? '' : "class='gform_asim_map'";
	$required_attribute = $instance->isRequired ? 'aria-required="true"' : '';
	$invalid_attribute  = $instance->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

	// options of the field
	$map_type           = $instance->mapType ?? 'terrain'; // Default to Terrain (change to  satellite if you want)
	$autocomplete_types = $instance->autocompleteTypes ?? '';
	$interaction_type   = $instance->interactionType ?? 'marker';

	// there are two types of values, dingle coordinates or set of coordinates to defina a polygon
	if ( 'marker' === $interaction_type ) {
		$value = $instance->validate_coordinates( $value ) ? $value : '';
	} else {
		$value = $instance->validate_polygon( $value ) ? $value : '';
	}

	ob_start();

	if ( empty( $instance->google_maps_api_key ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class=""><p>The map field requires a Google Maps API key.
				<a style="text-decoration: underline;" href="'
				. esc_url( admin_url( 'admin.php?page=gf_settings&subview=asim-gravity-forms-map-addon' ) )
				. '">Configure</a></p></div>';
		}
		return ob_get_clean();
	}

	?>
	<div id="map-container-<?php echo esc_attr( $field_id ); ?>" class="gform-field-asim-map"
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
		/>



	<script>
		window.asimMaps = window.asimMaps || {};
		window.asimLocationIcon = <?php echo wp_json_encode( dirname( plugin_dir_url( __FILE__ ) ) . '/assets/location.svg' ); ?>;
		window.asimClearPolygonIcon = <?php echo wp_json_encode( dirname( plugin_dir_url( __FILE__ ) ) . '/assets/clear-polygon.webp' ); ?>;

		asimMaps['<?php echo esc_js( $input_id ); ?>'] = {
			map: null,
			inputElement: null,
			polygonCoords: [],
			polygon: null,
			marker: null,
			initMap: () => {
				const input = document.getElementById('<?php echo esc_js( $input_id ); ?>');
				asimMaps['<?php echo esc_js( $input_id ); ?>'].inputElement = input;
				const coordinatesInput = window.coordinatesFromInput(input); // {lat, lng} or null
				const coordinatesInitMap = coordinatesInput || {
					lat: -34.397,
					lng: 150.644
				};

				// Init the map calling google maps methods
				const mapContainerEl = document.getElementById('map-container-<?php echo esc_js( $field_id ); ?>');
				const map = new window.google.maps.Map(mapContainerEl, {
					center: coordinatesInitMap,
					disableDefaultUI: true, // Desactiva la interfaz predeterminada
					zoomControl: true,      // Activa los controles de zoom
					mapTypeControl: true,
					mapTypeIds: ['roadmap', 'terrain'],
					mapTypeId: '<?php echo esc_attr( $map_type ); ?>',
					mapTypeControlOptions: {
						style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
						position: google.maps.ControlPosition.TOP_RIGHT,
					},
					zoom: 8,
				});
				asimMaps['<?php echo esc_js( $input_id ); ?>'].mapContainerEl = mapContainerEl;
				asimMaps['<?php echo esc_js( $input_id ); ?>'].map = map;
				window.gotoLocationButton('<?php echo esc_js( $input_id ); ?>');

				<?php
				if ( 'marker' === $interaction_type ) : ?>
					// Add initial marker if the coordinates are valid.
					if (coordinatesInput) {
						window.addMarker('<?php echo esc_js( $input_id ); ?>', coordinatesInput);
						window.centerMapAtInputCoordinates(input, map);
					}
					// CLICK on the map > sets a market
					asimMaps['<?php echo esc_js( $input_id ); ?>'].map.addListener('click', function(e) {
						const clickedCoordinates = e.latLng;
						const inputElement = document.getElementById('<?php echo esc_js( $input_id ); ?>');
						inputElement.value = `${clickedCoordinates.lat()},${clickedCoordinates.lng()}`;
						window.addMarker('<?php echo esc_js( $input_id ); ?>', clickedCoordinates);
					});
				<?php endif; ?>

				<?php if ( 'polygon' === $interaction_type ) :
					// @TODO: validate input value to polygon or remove the input value
					// add default polygon if input value is valid polygon
					// add interacion of creaging a polygon
					?>
					window.initPolygonSetup('<?php echo esc_js( $input_id ); ?>');
				<?php endif; ?>

				// Add the search input for the map
				<?php
				if ( ! empty( $autocomplete_types ) ) :
					?>
					const autocompleteTypes = [ '<?php echo esc_js( $autocomplete_types ); ?>' ];
					window.initPlacesAutocomplete(map, '<?php esc_attr_e( 'Search location', 'asim-gravity-form-map-field' ); ?>', autocompleteTypes);
				<?php endif; ?>
			}
		}

		// ---------------------------------------------------------------
		// Call to GoogleMapsAPI
		window.loadGoogleMapsAPI = function() {
			const script = document.createElement('script');
			script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php
				echo esc_js( $instance->google_maps_api_key );
				echo ( ! empty( $autocomplete_types ) ) ? '&libraries=places' : '';
			?>&loading=async&callback=initAllMaps';
			script.async = true;
			script.loading = 'async';
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

