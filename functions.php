<?php
/* ========================================================================================================================================== */
/*                                                         ASSETS                                                                             */
/* ========================================================================================================================================== */
//Global variable of values to check
$customValuesToCheck = ['depth', 'width', 'subSize', 'subQty', 'postSize', 'postQty'];

/*This inherits the parent style. DO NOT REMOVE.*/
function hello_elementor_child_enqueue_styles() {
    wp_enqueue_style(
        'hello-elementor-style',
        get_template_directory_uri() . '/style.css'
    );

    wp_enqueue_style(
        'hello-elementor-customizer',
        get_template_directory_uri() . '/style.css', // The customizer CSS is often loaded here
        array('hello-elementor-style'), // Make sure parent styles load first
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('hello-elementor-style'),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles' );

/*This creates the tables used in fixlist, forecast and todo.*/
function enqueue_datatables_assets() {
    wp_enqueue_script('jquery');

    // DataTables core
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], '1.13.6', true);
    wp_enqueue_style('datatables-style', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], '1.13.6');

    wp_add_inline_script('datatables', "
        jQuery(document).ready(function($) {
            $('#todoTable_0, #todoTable_1, #forecastTable_0, #forecastTable_1, #fixlistTable_0, #fixlistTable_1, #customersTable_list, #customerTable').DataTable({
                autoWidth : false, 
				ordering: true,          // Enable sorting
                order: [[1, 'asc']],    
                searching: true,         // Enable searching
                paging: true,    // Enable pagination
				pageLength: 50
            });
           
		   //Remove label for search and show search as a placeholder
		    $('.dataTables_filter label').each(function() {
				var labelText = $(this).text().trim(); 
				var input = $(this).find('input');
				input.attr('placeholder', labelText.replace(':', '')); 
				$(this).contents().filter(function() {
					return this.nodeType === 3; 
				}).remove();
			});
			

            /* Add 'dirty' class to know when an item was changed form the view */
            $('#todoTable_0, #todoTable_1, #forecastTable_0, #forecastTable_1, #fixlistTable_0, #fixlistTable_1').on('input', '[contenteditable=\"true\"]', function () {
                $(this).closest('tr').addClass('dirty');
            });
        });
    ");
}
add_action('wp_enqueue_scripts', 'enqueue_datatables_assets');

function my_custom_ajax_data_shortcode() {
    $nonce = wp_create_nonce('save_table_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data' data-nonce='{$nonce}' data-url='{$ajax_url}'></div>";
}
add_shortcode('ajax_data_block', 'my_custom_ajax_data_shortcode');


function render_google_map_shortcode() {
    return '<div id="map" style="height: 500px; width: 100%;"></div>';
}
add_shortcode('google_map', 'render_google_map_shortcode');

function enqueue_google_maps_assets() {
    // Only run on a specific page (adjust as needed)
    if (!is_page('fixlist-view')) {
        return;
    }

    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT postal_code, priority, fixer, description, due_date FROM wp_custom_form_fixlist WHERE status = 0"
    );

    $locations = [];
    foreach ($results as $row) {
        $locations[] = [
            'postal_code' => trim($row->postal_code),
            'priority'    => (string) $row->priority,
            'fixer'       => $row->fixer,
            'description' => $row->description,
			'due_date' => $row->due_date
        ];
    }

    wp_register_script('google-maps-custom', '', [], null, true);
    wp_enqueue_script('google-maps-custom');

    wp_add_inline_script('google-maps-custom', 'const locations = ' . json_encode($locations) . ';', 'before');

    wp_add_inline_script('google-maps-custom', <<<JS
	document.addEventListener('DOMContentLoaded', function () {
		const mapDiv = document.getElementById("map");
		if (!mapDiv) return;

		let map;

		function initMap() {
			map = new google.maps.Map(mapDiv, {
				zoom: 12,
				center: { lat: 45.5017, lng: -73.5673 }
			});

			const geocoder = new google.maps.Geocoder();
			const infowindow = new google.maps.InfoWindow();

			if (locations && locations.length > 0) {
				locations.forEach(location => {
					geocoder.geocode({ address: location.postal_code }, function(results, status) {
						if (status === "OK") {
							let color;
							switch (location.priority) {
								case '1': color = 'red'; break;
								case '2': color = 'yellow'; break;
								case '3': color = 'green'; break;
								default:  color = 'blue'; break;
							}

							const marker = new google.maps.Marker({
								map: map,
								position: results[0].geometry.location,
								icon: {
									url: "https://maps.google.com/mapfiles/ms/icons/" + color + "-dot.png",
									scaledSize: new google.maps.Size(40, 40)
								}
							});

							marker.addListener("click", function() {
								infowindow.setContent(
									"<strong>Fixer:</strong> " + location.fixer + "<br>" +
									"<strong>Due Date:</strong> " + location.due_date + "<br>"+ 
									"<strong>Description:</strong> " + location.description + "<br>" +
									"<strong>Postal Code:</strong> " + location.postal_code + "<br>" +
									"<strong>Priority:</strong> " + location.priority
								);
								infowindow.open(map, marker);
							});
						} else {
							console.warn("Geocoding failed for " + location.postal_code + ": " + status);
						}
					});
				});
			} else {
				console.warn("No locations available.");
			}
		}

		// Wait until Google Maps is available
		if (typeof google === 'object' && typeof google.maps === 'object') {
			initMap();
		} else {
			const interval = setInterval(() => {
				if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
					clearInterval(interval);
					initMap();
				}
			}, 100);
		}
	});
	JS, 'after');

    // Load Google Maps API
    wp_enqueue_script(
        'google-maps-api',
        'https://maps.googleapis.com/maps/api/js?key=AIzaSyAWhHJaTmGH7lrPPU8p_yHI5k5ApJUiyE4',
        ['google-maps-custom'],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_google_maps_assets');

/*Nonce and url to save documents in forecast model*/
function add_ajax_vars_inline_script() {
    ?>
    <script type="text/javascript">
      var MyAjax = {
        ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
        nonce: "<?php echo wp_create_nonce('save_docs_nonce'); ?>"
      };
    </script>
    <?php
}
add_action('wp_head', 'add_ajax_vars_inline_script');


/* =========================================================================== FORMS LOGIC =========================================================================== */
/* ========================================================================================================================================== */
/*                                                         FORECAST FORM                                                                      */
/* ========================================================================================================================================== */
add_action('wp_ajax_save_forecast_form', 'save_forecast_form');
add_action('wp_ajax_nopriv_save_forecast_form', 'save_forecast_form'); //Allows non-logged-in users

function ajax_data_block_forecast_custom() {
    $nonce = wp_create_nonce('save_forecast_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data-block-forecast_custom' data-nonce='{$nonce}' data-url='{$ajax_url}' style='display:none;'></div>";
}
add_shortcode('ajax_data_block_forecast_form', 'ajax_data_block_forecast_custom');

function save_forecast_form() {
  global $wpdb;

	$errors = '';
	$success = '';

	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_forecast_nonce')) {
		$errors .= 'Invalid nonce. ';
	}

	/* CUSTOMER PART */
	$first_name  = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
	$last_name   = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
	$email       = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
	$phone_num   = isset($_POST['phoneNum']) ? sanitize_text_field($_POST['phoneNum']) : '';

	if (empty($first_name) || empty($last_name)) {
		$errors .= 'Missing required fields. ';
	}

	/* DATE HANDLING */
	$dueDate = new DateTime(isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '');
	$formattedDueDate = $dueDate->format('Y-m-d');
	$formattedCurrentDate = (new DateTime())->format('Y-m-d');
	/* FORECAST PART */
	$model         = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
	$modelType     = isset($_POST['modelType']) ? sanitize_text_field($_POST['modelType']) : '';
	$width         = formatFeetInches(isset($_POST['widthField']) ? wp_unslash($_POST['widthField']) : '');
	$depth         = formatFeetInches(isset($_POST['depthField']) ? wp_unslash($_POST['depthField']) : '');
	$louverLength  = formatFeetInches(isset($_POST['louverLength']) ? wp_unslash($_POST['louverLength']) : '');
	$louverQty     = isset($_POST['louverQty']) ? $_POST['louverQty'] : '';
	$soldiers      = isset($_POST['soldiers']) ? $_POST['soldiers'] : '';
	$subframeQty   = isset($_POST['subframeQty']) ? wp_unslash($_POST['subframeQty']) : '';
	$subframeSize  = formatFeetInches(isset($_POST['subframeSize']) ? wp_unslash($_POST['subframeSize']) : '');
	$postSize      = formatFeetInches(isset($_POST['postSize']) ? wp_unslash($_POST['postSize']) : '');
	$postQty       = isset($_POST['postQty']) ? $_POST['postQty'] : '';
	$dueDate       = $formattedDueDate;
	$color         = isset($_POST['color']) ? $_POST['color'] : '';
	$submittedAt   = $formattedCurrentDate;
	$customFields = isset($_POST['custom']) && is_array($_POST['custom']) ? array_map('trim', $_POST['custom']) : [];
	$status        = 0;
	$orderStatus   = 'measurement';
	$accessories   = isset($_POST['extras']) && is_array($_POST['extras']) ? array_map('trim', $_POST['extras']) : [];

	/* IMAGE UPLOAD */
	$imageUrls = [];
	if (!function_exists('wp_handle_upload')) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
	}

	if (isset($_FILES['customer_image']) && !empty($_FILES['customer_image']['name'][0])) {
		$files = $_FILES['customer_image'];
		foreach ($files['name'] as $key => $value) {
			if ($files['name'][$key]) {
				$file_array = [
					'name'     => $files['name'][$key],
					'type'     => $files['type'][$key],
					'tmp_name' => $files['tmp_name'][$key],
					'error'    => $files['error'][$key],
					'size'     => $files['size'][$key]
				];

				$upload_overrides = ['test_form' => false];
				$movefile = wp_handle_upload($file_array, $upload_overrides);

				if ($movefile && !isset($movefile['error'])) {
					$attachment = [
						'post_mime_type' => $movefile['type'],
						'post_title'     => sanitize_file_name($files['name'][$key]),
						'post_content'   => '',
						'post_status'    => 'inherit'
					];

					$attach_id = wp_insert_attachment($attachment, $movefile['file']);
					$attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
					wp_update_attachment_metadata($attach_id, $attach_data);

					$imageUrls[] = wp_get_attachment_url($attach_id);
				}
			}
		}
	}

	/* DATABASE INSERT */
	$wpdb->query('START TRANSACTION');

	$inserted_customer = $wpdb->insert(
		'wp_custom_customers',
		[
			'postal_code'  => $postal_code,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'email'        => $email,
			'phone_number' => $phone_num
		],
		['%s','%s','%s','%s','%s']
	);

	if ($inserted_customer === false) {
		$wpdb->query('ROLLBACK');
		$errors .= 'Customer insert failed; customer rolled back. ';
	}

	$customer_id = $wpdb->insert_id;

	$inserted_forecast = $wpdb->insert(
		'wp_forecast_table',
		[
			'customer_id'   => $customer_id,
			'model'         => $model,
			'model_type'    => $modelType,
			'width'         => $width,
			'depth'         => $depth,
			'louver_size'   => $louverLength,
			'louver_qty'    => $louverQty,
			'soldiers'      => $soldiers,
			'subframe_size' => $subframeSize,
			'subframe_qty'  => $subframeQty,
			'post_size'     => $postSize,
			'post_qty'      => $postQty,
			'due_date'      => $dueDate,
			'color'         => $color,
			'entry_date'    => $submittedAt,
			'custom_fields' => json_encode($customFields),
			'status'        => $status,
			'order_status'  => $orderStatus,
			'image_url'     => json_encode($imageUrls),
		],
		['%d','%s','%s','%s','%s','%s','%s','%s','%s','%s',
		 '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']
	);

	if ($inserted_forecast === false) {
		$wpdb->query('ROLLBACK');
		$errors .= 'Forecast insert failed; customer rolled back. ';
	} else {
		$wpdb->query('COMMIT');
	}

	if ($errors) {
		wp_send_json_error(['message' => $errors]);
	} else {
		wp_send_json_success(['message' => 'Customer and forecast inserted successfully']);
	}

}

/* ----------------------------  Create a new DB entry from a form  ---------------------------- */
add_action( 'elementor_pro/forms/new_record', function( $record, $ajax_handler ) {
    $form_name = $record->get_form_settings('form_name');
	$raw_fields = $record->get('fields');
	$fields = [];
	$info = '';
	foreach ( $raw_fields as $id => $field ) {
		$fields[ $id ] = $field['value'];
	}

	global $wpdb;
	if ($form_name === 'Fix_list_form'){
		$customerName = ucwords(trim($fields['fixlist_name'])) . ' ' . ucwords(trim($fields['fixlist_last_name']));
		
		$insert_success = $wpdb->insert('wp_custom_form_fixlist', array( 
			'name' => $customerName, 
			'phone_number' => $fields['fixlist_phone_number'], 
			'address' => $fields['fixlist_address'],
			'postal_code' => $fields['fixlist_postal_code'],
			'time_needed' => $fields['fixlist_time_needed'], 
			'fixer' => $fields['fixlist_fixer'],
			'due_date' => $fields['fixlist_due_date'],
			'entry_date' => date('Y-m-d'),
			'priority' => $fields['fixlist_priority'],
			'description' => $fields['fixlist_description'],
			'status' => 0
		));
	}else if ($form_name === 'forecast_form'){  
		$customFields = [];
		$louverLength = $fields['forecast_form_louverLength'];
		$customerName = ucwords(trim($fields['forecast_form_name'])) . ' ' . ucwords(trim($fields['forecast_form_last_name']));
		
			
		//Handle custom inputs
		if ($fields['forecast_form_width'] === 'Custom'){
			$rawWidth = $fields['forecast_form_custom_width'] ?? '';
    		$width = formatFeetInches(trim($rawWidth));
    		$customFields[] = 'width';
		}else{
			$width = $fields['forecast_form_width'];
		}
		
		if ($fields['forecast_form_depth'] === 'Custom'){
			$rawDepth = $fields['forecast_form_custom_depth'] ?? '';
			$depth = formatFeetInches($rawDepth);
			$louverLength = formatFeetInches($louverLength);
			$customFields[] = 'depth';
		}else{
			$depth = $fields['forecast_form_depth'];
		}
		
		if ($fields['forecast_form_subframe_size'] === 'Custom'){
			$rawSubSize = $fields['forecast_form_custom_subSize'] ?? ''; 
			$subSize = formatFeetInches($rawSubSize);
			$customFields[] = 'subSize';
		}else{
			$subSize = $fields['forecast_form_subframe_size'];
		}
		
		if ($fields['forecast_form_subframe_qty'] === 'Custom'){
			$subQty = $fields['forecast_form_custom_subQty'];
			$customFields[] = 'subQty';
		}else{
			$subQty = $fields['forecast_form_subframe_qty'];
		}
		
		if ($fields['forecast_form_posts_size'] === 'Custom'){
			$rawPostSize = $fields['forecast_form_custom_postLength'] ?? '';
			$postSize = formatFeetInches($rawPostSize);
			$customFields[] = 'postSize';
		}else{
			$postSize = $fields['forecast_form_posts_size'];
		}
		
		if ($fields['forecast_form_posts_qty'] === 'Custom'){
			$postQty = $fields['forecast_form_custom_postQty'];
			$customFields[] = 'postQty';
		}else{
			$postQty = $fields['forecast_form_posts_qty'];
		}
		
	
		$dueDate = new DateTime($fields['forecast_form_dueDate']);
		$formattedDueDate= $dueDate->format('Y-m-d');
		
		$currentDate = new DateTime();
		$formattedCurrentDate = $currentDate->format('Y-m-d'); 
		
		$insert_success = $wpdb->insert('wp_custom_form_forecast', 
			array( 
				'name' => $customerName,
				'postal_code' => $fields['forecast_form_postal_code'],
				'model' => $fields['forecast_form_model'],
				'type' => $fields['forecast_form_type'],
				'width' => $width,
				'depth' => $depth,
				'subframe_size' => $subSize,
				'subframe_qty' => $subQty,
				'louver_size' => $louverLength,
				'louver_qty' => $fields['forecast_form_louversQty'],
				'post_size' => $postSize,
				'post_qty' => $postQty,
				'soldiers' => $fields['forecast_form_soldiers'],
				'status' => 0,
				'info' => $info,
				'due_date' => $formattedDueDate,
				'entry_date' => $formattedCurrentDate,
				'custom_fields' => json_encode($customFields),
			)
		);
	}else if ($form_name === 'todo_form'){
		if ($fields['todolist_email'] === 'Other'){
			$email_to = $fields['todo_custom_email'];
		}else{
			$email_to = $fields['todolist_email'];	
		}
		
		$due_date = $fields['todolist_duedate'];
		$task_description = $fields['todolist_task'];
		
		$insert_success = $wpdb->insert('wp_custom_form_todolist', array( 
			'assignee' => ucfirst($fields['todolist_assignee']), 
			'priority' => $fields['todolist_priority'],
			'date' => $due_date,
			'status' => 0,
			'task' => $task_description,
			'email' => $email_to
		));
	}else{
		$output['success'] = false;
		$output['debug'] = 'Form not found';
		$record->set_status('failed'); 
	}
	
	
	if ($insert_success === false) {
		$output['success'] = false;
		$output['debug'] = 'Error inserting item';
		$record->set_status('failed'); 
	}else{
		$output['success'] = $insert_success;
		
		if (method_exists($record, 'set_status')) {
			$record->set_status('success');
		} else {
			error_log('Record object does not have set_status method.');
		}
		
		if ($form_name === 'todo_form'){
			$subject = "New task asssigned";			
			$message  = "Hi,<br> You have a new task: \"{$task_description}\" due on <strong>{$due_date}</strong><br><br>";
			$headers = array('Content-Type: text/html; charset=UTF-8');
			
			send_email($email_to, $subject, $message);
		}
			
	}
	
   $ajax_handler->add_response_data(true, $output);
    
}, 10, 2);
/* ----------------------------  Formats custom input  ---------------------------- */
function formatFeetInches($input) {
	if ($input ===""){return $input;}
  	$input = trim($input);
    $input = preg_replace('/\s+/', ' ', $input); // Normalize whitespace
	
    // Handle inputs like: 11 2, 11,2, 11 2", 11, 2", etc.
    if (preg_match('/^(\d+)[\s,]+(\d+)"?$/', $input, $matches)) {
        return $matches[1] . "'" . $matches[2] . '"';
    }

    // Handle inputs like: 11'2 (missing final ")
    if (preg_match('/^(\d+)\'(\d+)$/', $input, $matches)) {
        return $matches[1] . "'" . $matches[2] . '"';
    }

    // Case 1: No ' or ", assume it's feet only
    if (strpos($input, "'") === false && strpos($input, '"') === false) {
        return $input . "'";
    }

    // Case 2: Ends with ', assume it's already formatted as feet
    if (substr($input, -1) === "'") {
        return $input;
    }

    // Case 3: Contains ' and more text after, add " if it's missing
    if (strpos($input, "'") !== false && substr($input, -1) !== '"') {
        return $input . '"';
    }

    // Default: return as-is
    return $input;
}


/* ========================================================================================================================================== */
/*                                                         SAVE TABLE                                                                         */
/* ========================================================================================================================================== */
function save_table_cb() {
   if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'save_table_nonce') ) {
    	wp_send_json_error('Security check failed. Nonce invalid or missing.');
	}
	
	//Wordpress db object
	global $wpdb;
	
	$action = sanitize_text_field($_POST['action']);
	if ($action === 'save_fixlist_table') {
		$table = 'wp_custom_form_fixlist';
		$columns = ['status','priority','due_date','entry_date','fixer','time_needed','phone_number','name','address','postal_code','description'];
		$format = array('%d','%d','%s', '%s','%s','%s','%s','%s','%s','%s','%s');
	}else if ($action === 'save_forecast_table'){
		$table = 'wp_custom_form_forecast';
		$columns = ['status','entry_date','due_date','model','type','width','depth','subframe_size','subframe_qty','louver_size', 'louver_qty','post_size','post_qty','soldiers','name','postal_code','info','custom_fields'];
		$format = array('%d','%s', '%s','%s','%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');
	}else if ($action === 'save_todo_table'){
		$table = 'wp_custom_form_todolist';
		$columns = ['status', 'priority', 'assignee', 'date', 'email', 'task'];
		$format = array('%d','%d','%s','%s', '%s', '%s');
	}
    
	 // Get and decode the posted data
    if (empty($_POST['data'])) {
        wp_send_json_error('No data received.');
    }

    $data = json_decode(stripslashes($_POST['data']), true);
    if (empty($data) || !is_array($data)) {
        wp_send_json_error('Invalid data format.');
    }

    $errors = [];
	
    foreach ($data as $row) {
        $id = intval($row['id']);
        $values = $row['values']; 

        $update_data = [];
        foreach ($columns as $index => $col_name) {
            if (isset($values[$index])) {
				// Map sanitized data to table columns
                $update_data[$col_name] = sanitize_text_field($values[$index]);
            }
        }
		
		//If not empty, update row in DB
        if (!empty($update_data) && $id > 0) {
			$result = $wpdb->update(
				$table,
				$update_data,
				array('id' => $id),
				$format,
				array('%d')
			);
		
			if ($result === false){
				$errors[] = "Database error for ID $id: " . $wpdb->last_error;
			} else {
				// Add debugging output
				$errors[] = "Query executed successfully. Rows affected: " . $result;
			}
        }else{
			$errors[] = "Error updating table for ID $id: Invalid data or ID.";
		}
    }

    if (!empty($error)) {
		wp_send_json_error($error);
	} else {
		wp_send_json_success('Changes saved.');
	}
}

add_action('wp_ajax_save_fixlist_table', 'save_table_cb');
add_action('wp_ajax_nopriv_save_fixlist_table', 'save_table_cb');     //Allows non logged in user to make changes
add_action('wp_ajax_save_forecast_table', 'save_table_cb');
add_action('wp_ajax_nopriv_save_forecast_table', 'save_table_cb');    //Allows non logged in user to make changes
add_action('wp_ajax_save_todo_table', 'save_table_cb');
add_action('wp_ajax_nopriv_save_todo_table', 'save_table_cb');        //Allows non logged in user to make changes


/*----------------------------  Fetch row data for forecast modal  ----------------------------*/
function get_row_data_ajax() {
    check_ajax_referer('get_table_nonce', '_wpnonce');

    // Get row ID from POST, sanitize it
    $row_id = isset($_POST['row_id']) ? intval($_POST['row_id']) : 0;
    if (!$row_id) {
        wp_send_json_error('Missing row ID');
    }

    global $wpdb;
 
    // Fetch row from database
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_custom_form_forecast WHERE id = %d", $row_id));

    if (!$row) {
         error_log("get_row_data_ajax: No row found for id = $row_id in table $table_name");
		 wp_send_json_error('Row not found');
    }

    // Return the row data (adjust fields as needed)
    wp_send_json_success(array(
        'id' => $row->id,
        'name' => $row->name,
		'documents' => isset($row->image_url) ? json_decode($row->image_url, true) : array(),

    ));
}
add_action('wp_ajax_get_row_data', 'get_row_data_ajax');
add_action('wp_ajax_nopriv_get_row_data', 'get_row_data_ajax');

function ajax_data_block_forecast_shortcode() {
    $nonce = wp_create_nonce('get_table_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data-block-forecast' data-nonce='{$nonce}' data-url='{$ajax_url}' style='display:none;'></div>";
}
add_shortcode('ajax_data_block_forecast', 'ajax_data_block_forecast_shortcode');

/* ========================================================================================================================================== */
/*                                                         SAVE DOCS                                                                          */
/* ========================================================================================================================================== */
add_action('wp_ajax_save_docs', 'handle_save_docs');
add_action('wp_ajax_nopriv_save_docs', 'handle_save_docs');

function handle_save_docs() {
	check_ajax_referer('save_docs_nonce', 'nonce');

	if (empty($_FILES['docs'])) {
		wp_send_json_error('No files uploaded');
	}

	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	global $wpdb;
	$table = 'wp_custom_form_forecast';
	$rowId = intval($_POST['id']);
	$uploaded_links = [];

	// Get current stored URLs (JSON)
	$current = $wpdb->get_var(
		$wpdb->prepare("SELECT image_url FROM $table WHERE id = %d", $rowId)
	);
	$existing_links = $current ? json_decode($current, true) : [];

	$files = $_FILES['docs'];
	$file_count = count($files['name']);

	for ($i = 0; $i < $file_count; $i++) {
		if ($files['error'][$i] !== UPLOAD_ERR_OK) {
			continue;
		}

		$file = [
			'name'     => $files['name'][$i],
			'type'     => $files['type'][$i],
			'tmp_name' => $files['tmp_name'][$i],
			'error'    => $files['error'][$i],
			'size'     => $files['size'][$i],
		];

		$upload_overrides = ['test_form' => false];
		$movefile = wp_handle_upload($file, $upload_overrides);

		if ($movefile && !isset($movefile['error'])) {
			$filename = $movefile['file'];
			$filetype = wp_check_filetype(basename($filename), null);

			// Insert in media library
			$attachment = [
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_file_name(basename($filename)),
				'post_content'   => '',
				'post_status'    => 'inherit',
			];
			$attach_id = wp_insert_attachment($attachment, $filename);
			$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
			wp_update_attachment_metadata($attach_id, $attach_data);

			// Add new link to list
			$url = esc_url($movefile['url']);
			if (!in_array($url, $existing_links)) {
				$existing_links[] = $url;
				$uploaded_links[] = $url;
			}
		}
	}

	if (!empty($uploaded_links)) {
		$wpdb->update(
			$table,
			['image_url' => json_encode($existing_links)],
			['id' => $rowId],
			['%s'],
			['%d']
		);

		wp_send_json_success(['uploaded_urls' => $uploaded_links]);
	} else {
		wp_send_json_error('No files uploaded correctly');
	}
}


/* =========================================================================== VIEWS LOGIC =========================================================================== */
/* ========================================================================================================================================== */
/*                                                  FIX LIST / SERVICES                                                                       */
/* ========================================================================================================================================== */
function display_fixlist_data($status) {
	
	//DB call based on status
	global $wpdb;
	
	$results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM wp_custom_form_fixlist WHERE status = %d", $status)
    );
	
	if (empty($results)) {
        return '<p style="font-family: Roboto, sans-serif;text-align: center;"">No data found.</p>';
    }
	
	$dynamic_id = 'fixlistTable_' . $status;
    ob_start();
	
	//Table header
    echo '<table id="' . esc_attr($dynamic_id) . '" class="table-custom display">';

	// Display the table header
	echo '<thead><tr>';
	
	if ($dynamic_id === 'fixlistTable_0') {
    	echo '<th style="width:2%">To Do</th>';
	} elseif ($dynamic_id === 'fixlistTable_1') {
    	echo '<th style="width:2%">Done</th>';
	} else {
    	echo '<th style="width:2%">Status</th>';
	}

	echo '<th style="width:2%">Priority</th>';
	echo '<th style="width:3%">Due Date</th>';
	
	if ($dynamic_id === 'fixlistTable_0') {
    	echo '<th style="width:3%">Creation Date</th>';
	} elseif ($dynamic_id === 'fixlistTable_1') {
    	echo '<th style="width:3%">Done Date</th>';
	} else {
    	echo '<th style="width:3%">Date</th>'; // fallback, optional
	}
	
	echo '<th>Fixer</th>';
	echo '<th style="width:2%">Fix Time</th>';
	echo '<th style="width:9%">Phone#</th>';
	echo '<th>Name</th>';
	echo '<th>Address</th>';
	echo '<th style="width:8%">Postal Code</th>';
	echo '<th style="width:25%">Description</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	// Loop through the data and display the rows
	foreach ($results as $row) {
		$due_date = $row->due_date;
		$formatted_due_date = date('d/m', strtotime($due_date));
		
		$entry_date = $row->entry_date;
		$formatted_entry_date = date('d/m', strtotime($entry_date));
		
		$priority = esc_html($row->priority);
		$bgColor = '';

		// Colour code priority
		if ($priority == '1') {
			$bgColor = '#ffdddd'; // Red
		} elseif ($priority == '2') {
			$bgColor = '#fff7cc'; // Yellow
		} else {
			$bgColor = '#ddffdd'; // Green
		}

		echo '<tr data-id="' . esc_attr($row->id) . '">';
        //echo '<td contenteditable="true" style="text-align: center;"><input type="checkbox" ' . (($status == 1) ? 'checked' : '') . ' /></td>';
        echo '<td style="text-align: center; position: relative;" contenteditable="true">
        <span class="editable-text"> </span> <!-- Editable space if needed -->
        <span contenteditable="false" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <input type="checkbox" ' . (($status == 1) ? 'checked' : '') . ' />
        </span>
      </td>';
		
        echo '<td contenteditable="true" style="background-color: ' . esc_attr($bgColor) . ';">' . esc_html($row->priority) . '</td>';
        echo '<td contenteditable="true">' . esc_html($formatted_due_date) . '</td>';
        echo '<td >' . esc_html($formatted_entry_date) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->fixer) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->time_needed) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->phone_number) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->name) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->address) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->postal_code) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->description) . '</td>';
        echo '</tr>';
	}

	echo '</tbody></table>';

    return ob_get_clean();
}

//Uses param 0 for incomplete and 1 for complete. Param is passed in shortcode added on page. 
function display_fixlist_data_table($atts){
	   $atts = shortcode_atts([
        'status' => 'all' 
    ], $atts);

    $status = sanitize_text_field($atts['status']);
	return display_fixlist_data($status);	
}

add_shortcode('show_fixlist_data', 'display_fixlist_data_table');


/* ----------------------------  Retrieve Forecast data and display it  ---------------------------- */
function display_forecast_data($status) {
	
	global $wpdb;
	global $customValuesToCheck;
	$customFlags = [];
	
	$results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM wp_custom_form_forecast WHERE status = %d", $status)
    );
	
	if (empty($results)) {
        return '<p style="font-family: Roboto, sans-serif;text-align: center;"">No data found.</p>';
    }
	
	$dynamic_id = 'forecastTable_' . sanitize_title($status);
		
    ob_start();

    echo '<table id="' . esc_attr($dynamic_id) . '" class="table-custom display">';
    echo '<thead><tr>';

		if ($dynamic_id === 'forecastTable_0') {
			echo '<th style="width:2%">To Do</th>';
			echo '<th style="width:3%">Submit Date</th>';
		} elseif ($dynamic_id === 'forecastTable_1') {
			echo '<th style="width:2%">Done</th>';
			echo '<th style="width:3%">Done Date</th>';
		} else {
			echo '<th style="width:2%">Status</th>';
		}
	
		echo '<th style="width:5%">Due Date</th>
			<th style="width:2%">Mdl</th>
			<th style="width:2%">Type
			<th style="width:2%">Wdt</th>
			<th style="width:2%">Dpt</th>
			<th style="width:5%">SF Lgt</th>
			<th style="width:5%">SF Qty</th>
			<th style="width:5%">Lvr Lgt</th>
			<th style="width:5%">Lvr Qty</th>
			<th style="width:6%">Posts Lgt</th>
			<th style="width:6%">Posts Qty</th>
			<th style="width:5%">Slds</th>
			<th >Name</th>
			<th style="width:7%">Postal Code</th>
			<th style="display:none" class="hidden-data"></th>
			<th>Info</th>
			<th>Doc</th>
			</tr></thead>';
    echo '<tbody>';

	$customStyle = 'color: blue;';
    foreach ($results as $row) {
		//Check if any field as a custom value
		if ($row->custom_fields){
			$customValuesArray = json_decode($row->custom_fields, true);
			
			foreach ($customValuesToCheck as $value) {
				$customFlags[$value] = in_array($value, array_map('trim', $customValuesArray));
			}
		}	
		
		$entry_date = $row->entry_date;
		$formatted_entry_date = date('d/m', strtotime($entry_date));
		
		$due_date = $row->due_date;
		$formatted_due_date = date('d/m', strtotime($due_date));
		
		echo '<tr data-id="' . esc_attr($row->id) . '">';
		echo '<td contenteditable="true" style="text-align: center;">
			<span contenteditable="false">
				<input type="checkbox" ' . (($status == 1) ? 'checked' : '') . ' />
			</span>
		</td>';
		echo '<td >' . esc_html($formatted_entry_date) . '</td>';
		echo '<td contenteditable="true">' . esc_html($formatted_due_date) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->model) . '</td>';
		echo '<td contenteditable="true">' . esc_html($row->type) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['width'] ? $customStyle : '') . '">' . esc_html($row->width) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['depth'] ? $customStyle : '') . '">'. esc_html($row->depth) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['subSize'] ? $customStyle : '') . '">'. esc_html($row->subframe_size) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['subQty'] ? $customStyle : '') . '">'. esc_html($row->subframe_qty) . '</td>';
		echo '<td contenteditable="true">' . esc_html($row->louver_size) . '</td>';
		echo '<td contenteditable="true">' . esc_html($row->louver_qty) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['postSize'] ? $customStyle : '') . '">'. esc_html($row->post_size) . '</td>';
		echo '<td contenteditable="true" style="' . ($customFlags['postQty'] ? $customStyle : '') . '">'. esc_html($row->post_qty) . '</td>';
		echo '<td contenteditable="true">' . esc_html($row->soldiers) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->name) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->postal_code) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->info) . '</td>';
		echo '<td style="display:none" class="hidden-data">' . esc_html($row->custom_fields) . '</td>';    
		echo '<td style="text-align:center;"> <span class="view-doc-button" data-rowid="' . esc_attr($row->id) . '" style="cursor:pointer; font-size:22px;" title="View Document">📁</span></td>';
		
		echo '</tr>';
    }
    echo '</tbody></table>';
	
    return ob_get_clean();
}

//Uses param 0 for incomplete and 1 for complete. Param is passed in shortcode added on page. 
function display_forecast_data_table($atts){
	   $atts = shortcode_atts([
        'status' => 'all' 
    ], $atts);

    $status = sanitize_text_field($atts['status']);
	return display_forecast_data($status);	
}

add_shortcode('show_forecast_data', 'display_forecast_data_table');

/* ========================================================================================================================================== */
/*                                                  MATERIAL CALCULATIONS                                                                     */
/* ========================================================================================================================================== */

function sortArrayByKey($array) {
    // Create a custom comparison function to sort by the numerical value of the key
    uksort($array, function($a, $b) {
        return (intval($a) - intval($b));
    });
    return $array;
}

function calculateLouvers($results){
	$louversCount = [];

    foreach ($results as $entry) {

        $louverSize = $entry->louver_size;
        $louverQty = (int)$entry->louver_qty;

        incrementCount($louversCount, $louverSize, $louverQty);
    }

	$sortedLouversCount = sortArrayByKey($louversCount);
    return (object)[
        'stdKeys' => array_keys($sortedLouversCount),
        'stdValues' => array_values($sortedLouversCount),
    ];
}

function calculatePosts($results){
    $stdPostsCounts = [];
    $modernPostsCounts = [];
    $customPostsCounts = [];
   
    $typeCounters = [
        '3S' => &$stdPostsCounts,
        '4S' => &$stdPostsCounts,
        'Modern' => &$modernPostsCounts,
    ];

    foreach ($results as $entry) {
        $customFields = json_decode($entry->custom_fields, true);
        $isCustom = is_array($customFields) && in_array('postSize', $customFields);

        $postSize = $entry->post_size;
		$postQty = $entry->post_qty;	
		
		if (!empty($postSize) && ($postQty === null || $postQty === '')) {
    		$postQty = 1;
		}
        $model = $entry->model;

        if ($isCustom) {
            incrementCount($customPostsCounts, $postSize, $postQty);
        } elseif (isset($typeCounters[$model])) {
            incrementCount($typeCounters[$model], $postSize, $postQty);
        }
    }
	
	$sortedStdPostsCounts = sortArrayByKey($stdPostsCounts);
	$sortedModernPostsCounts = sortArrayByKey($modernPostsCounts);
	$sortedCustomPostsCounts = sortArrayByKey($customPostsCounts);
	
   return (object)[
    'stdKeys' => array_keys($sortedStdPostsCounts),
    'stdValues' => array_values($sortedStdPostsCounts),
    'modernKeys' => array_keys($sortedModernPostsCounts),
    'modernValues' => array_values($sortedModernPostsCounts),
    'customKeys' => array_keys($sortedCustomPostsCounts),
    'customValues' => array_values($sortedCustomPostsCounts)
];
}

function calculateWidthAndDepth($results) {
    $s3Counts = [];
	$s4Counts = [];
    $modernCounts = [];
    $customCounts = [];

    $typeCounter = [
        '3S' => &$s3Counts,
        '4S' => &$s4Counts,
        'Modern' => &$modernCounts,
    ];

    foreach ($results as $entry) {
        $customFields = json_decode($entry->custom_fields, true);
        $isWidthCustom = is_array($customFields) && (in_array('width', $customFields)); 
        $isDepthCustom = is_array($customFields) && (in_array('depth', $customFields)); 

        $width = $entry->width;
        $depth = $entry->depth;
        $model = $entry->model;

        if ($isWidthCustom) {
            incrementCount($customCounts, $width, 2);
        }elseif (isset($typeCounter[$model])) {
            incrementCount($typeCounter[$model], $width, 2);
        }

        if($isDepthCustom){
            incrementCount($customCounts, $depth, 2);
        } elseif (isset($typeCounter[$model])) {
            incrementCount($typeCounter[$model], $depth, 2);
        }
    }

	$sorted3StdCounts = sortArrayByKey($s3Counts);
	$sorted4StdCounts = sortArrayByKey($s4Counts);
	$sortedModernCounts = sortArrayByKey($modernCounts);
	$sortedCustomCounts = sortArrayByKey($customCounts);
	
    return (object)[
        'std3Keys' => array_keys($sorted3StdCounts),
        'std3Values' => array_values($sorted3StdCounts),
		'std4Keys' => array_keys($sorted4StdCounts),
        'std4Values' => array_values($sorted4StdCounts),
        'modernKeys' => array_keys($sortedModernCounts),
        'modernValues' => array_values($sortedModernCounts),
        'customKeys' => array_keys($sortedCustomCounts),
        'customValues' => array_values($sortedCustomCounts),
    ];
}

function incrementCount(array &$arr, $key, $value) {
	if ($key && $key !== 'None'){
		if (!isset($arr[$key])) {
			$arr[$key] = 0;
		}
		$arr[$key] += $value;	
	}
}

function calculateSubframes($results){
	$stdCounts = [];
	$customCounts = [];

	foreach ($results as $entry) {
		$customFields = json_decode($entry->custom_fields, true);
        $isCustom = is_array($customFields) && (in_array('subSize', $customFields));

		$subSize = $entry->subframe_size;
		$subQty = (int)$entry->subframe_qty;

		if (!$isCustom){
			incrementCount($stdCounts, $subSize, $subQty);
		}else{
			incrementCount($customCounts, $subSize, $subQty);
		}
	}

	// Separate the keys and values for both stdCounts and modernCounts
	$stdKeys = array_keys($stdCounts);
	$stdValues = array_values($stdCounts);
	$customKeys = array_keys($customCounts);
	$customValues = array_values($customCounts);

	return (object)[
		'stdKeys' => $stdKeys,
		'stdValues' => $stdValues,
		'customKeys' => $customKeys,
		'customValues' => $customValues
	];
}

function calculate_forecast_material(){
	ob_start();
	global $wpdb;
	
	// Get data from DB
    $table_name = 'wp_custom_form_forecast'; 
	$results = $wpdb->get_results("SELECT * FROM `$table_name` WHERE status = '$status'");
	if (empty($results)) {
        return '<p style="font-family: Roboto, sans-serif;text-align: center;"">No data found.</p>';
    }
	
	$widthsAndDepths = calculateWidthAndDepth($results);
	$postsCount = calculatePosts($results);
	$subframesCount = calculateSubframes($results);
	$louversCount = calculateLouvers($results);
	
	$soldiers_total = 0;
	//Calculate values based on data
	foreach ($results as $obj) {
		$soldiers_total += $obj->soldiers; 
	};

	echo '<table id="materialTable" class="table-custom display"; style="width:100%; padding=10px;">';
	echo '<thead>';

	// Beams label
	echo '<tr>
				<th colspan="4" style="background-color:black; color:white; text-align:center; border-right:2px solid black;">Beams</th>
				<th colspan="2" style="background-color:#4c5357; color:white; text-align:center; border-right:2px solid black;">Subframes</th>
				<th colspan="1" style="background-color:black;border-right:2px solid black;">Louvers</th>
				<th colspan="1" style="background-color:#4c5357;border-right:2px solid black;">Soldiers</th>
				<th colspan="3" style="background-color:black; color:white; text-align:center;">Posts</th>
			  </tr>';

	// Second row: individual headers
	echo '<tr>
				<th style="background-color:black; color:white;">3S</th>
				<th style="background-color:black; color:white;">4S</th>
				<th style="background-color:black; color:white;">Modern</th>
				<th style="background-color:black; color:white; border-right:2px solid black;">Custom</th>
				<th style="background-color:#4c5357; color:white;">Standard</th>
				<th style="background-color:#4c5357; color:white; border-right:2px solid black;">Custom</th>
				<th style="background-color:black; color:white; border-right:2px solid black;"></th>
				<th style="background-color:#4c5357; color:white; border-right:2px solid black;"></th>
				<th style="background-color:black; color:white;">3S & 4S</th>
				<th style="background-color:black; color:white;">Modern</th>
				<th style="background-color:black; color:white;">Custom</th>
			  </tr>';

	echo '</thead>';
	echo '<tbody>';
	echo '<tr>';

	$max_rows = max(
		count($widthsAndDepths->std3Keys),
		count($widthsAndDepths->std4Keys),
		count($widthsAndDepths->modernKeys),
		count($widthsAndDepths->customKeys),
		count($subframesCount->stdKeys),
		count($subframesCount->customKeys),
		count($louversCount->stdKeys),
		count($postsCount->stdKeys),
		count($postsCount->modernKeys)
	);
	
	for ($i = 0; $i < $max_rows; $i++) {

		//3S beams	
		if ($widthsAndDepths->std3Keys[$i]){
			echo '<td>' . $widthsAndDepths->std3Keys[$i]  . ' = ' . $widthsAndDepths->std3Values[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}
		
		//4S beams
		if ($widthsAndDepths->std4Keys[$i]){
			echo '<td>' . $widthsAndDepths->std4Keys[$i]  . ' = ' . $widthsAndDepths->std4Values[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}

		//Modern Beams
		if ($widthsAndDepths->modernKeys[$i] ){
			echo '<td>' . $widthsAndDepths->modernKeys[$i] . ' = ' . $widthsAndDepths->modernValues[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}

		//Custom Beams
		if ($widthsAndDepths->customKeys[$i] ){
			echo '<td style="border-right:2px solid black;">' . $widthsAndDepths->customKeys[$i] . ' = ' . $widthsAndDepths->customValues[$i] . '</td>';	
		}else{
			echo '<td style="border-right:2px solid black;"></td>';
		}

		//Standard Subframes
		if ($subframesCount->stdKeys[$i]){
			echo '<td>' . $subframesCount->stdKeys[$i] . ' = ' . $subframesCount->stdValues[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}

		//Custom Subframes
		if ($subframesCount->customKeys[$i]){
			echo '<td style="border-right:2px solid black;">' . $subframesCount->customKeys[$i] . ' = ' . $subframesCount->customValues[$i] . '</td>';	
		}else{
			echo '<td style = "border-right:2px solid black;"></td>';
		}

		//Louvers
		if ($louversCount->stdKeys[$i]){
			echo '<td style="border-right:2px solid black;">' . $louversCount->stdKeys[$i]  . ' = ' . $louversCount->stdValues[$i] . '</td>';
		}else{
			echo '<td style="border-right:2px solid black;"></td>';
		}

		// Soldiers: only show once (first row)
		if ($i === 0) {
			echo '<td rowspan="' . $max_rows . '" style="text-align: center;border-right:2px solid black;">' . $soldiers_total . '</td>';
		}

		//Standard Post (3s & 4S)
		if ($postsCount->stdValues[$i]){
			echo '<td>' . $postsCount->stdKeys[$i] . ' = ' . $postsCount->stdValues[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}

		//Modern Post
		if ($postsCount->modernValues[$i]){
			echo '<td>' . $postsCount->modernKeys[$i] . ' = ' . $postsCount->modernValues[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}

		//Custom Post
		if ($postsCount->customValues[$i]){
			echo '<td>' . $postsCount->customKeys[$i] . ' = ' . $postsCount->customValues[$i] . '</td>';	
		}else{
			echo '<td></td>';
		}


		echo '</tr>';
	}

	echo '</tbody></table>';
	return ob_get_clean();
}

add_shortcode('show_forecast_material', 'calculate_forecast_material');

/* ========================================================================================================================================== */
/*                                                   TO DO LIST.                                                                              */
/* ========================================================================================================================================== */
function display_todo_data($status) {
	global $wpdb;
	
	$results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM wp_custom_form_todolist WHERE status = %d", $status)
    );
	
	if (empty($results)) {
        return '<p style="font-family: Roboto, sans-serif;text-align: center;"">No data found.</p>';
    }

	$dynamic_id = 'todoTable_' . $status;
    ob_start();

    echo '<table id="' . esc_attr($dynamic_id) . '" class="table-custom display">';
    echo '<thead><tr>';
	
	if ($dynamic_id === 'todoTable_0') {
		echo '<th style="width:5%"> To Do</th>';
	} elseif ($dynamic_id === 'todoTable_1') {
		echo '<th style="width:5%"> Done</th>';
	} else {
		echo '<th style="width:5%"> Status</th>';
	};
	
	echo '<th style="width:2%">Priority</th>
          <th style="width:10%"">Assignee</th>';
	 
	if ($dynamic_id === 'todoTable_0') {
		echo '<th style="width:10%"> Due Date </th>';
	} elseif ($dynamic_id === 'todoTable_1') {
		echo '<th style="width:10%"> Completed Date </th>';
	} else {
		echo '<th style="width:10%"> Date</th>';
	};
	
	echo '<th >Email</th>
         <th > Task </th>
		</tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        $priority = esc_html($row->priority);
        $bgColor = '';

        // Colour code priority
        if ($priority == '1') {
            $bgColor = '#ffdddd'; // Red
        } elseif ($priority == '2') {
            $bgColor = '#fff7cc'; // Yellow
        } else {
            $bgColor = '#ddffdd'; // Green
        }

        echo '<tr data-id="' . esc_attr($row->id) . '">';
        echo '<td contenteditable="true" style="text-align: center;">
			<span contenteditable="false">
				<input type="checkbox" ' . (($status == 1) ? 'checked' : '') . ' />
			</span>
		</td>';
        echo '<td contenteditable="true" style="background-color: ' . esc_attr($bgColor) . ';">' . esc_html($row->priority) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->assignee) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->date) . '</td>';
		echo '<td contenteditable="true">' . esc_html($row->email) . '</td>';
        echo '<td contenteditable="true">' . esc_html($row->task) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    return ob_get_clean();
}

//Uses param 0 for incomplete and 1 for complete. Param is passed in shortcode added on page. 
function display_todo_table($atts){
	   $atts = shortcode_atts([
        'status' => 'all' 
    ], $atts);

    $status = sanitize_text_field($atts['status']);
	return display_todo_data($status);	
}

add_shortcode('show_todo_data', 'display_todo_table');
			
/* ========================================================================================================================================== */
/*                                                          CUSTOMERS LIST                                                                    */
/* ========================================================================================================================================== */
/* ---------------------------- Display list of all customers ---------------------------- */
function display_customer_data() {
	global $wpdb;
	
	$query =
		  "SELECT c.*, f.* 
             FROM wp_custom_customers c
             INNER JOIN wp_forecast_table f
             ON c.id = f.customer_id";

	$results = $wpdb->get_results($query);
	
	if (empty($results)) {
        return '<p style="font-family: Roboto, sans-serif;text-align: center;"">No data found.</p>';
    }
	
    ob_start();	
	echo '<div class="mrpergola-table-container">';
	echo '<table id="customersTable_list" class="mrpergola-table display">';
	echo '<thead><tr>';
	echo '<th>Name</th>
		<th class="mrpergola-col-xsmall">Submit date</th>
		<th class="mrpergola-col-xsmall">Due date</th>
		<th class="mrpergola-col-xsmall">Mdl</th>
		<th class="mrpergola-col-xsmall">Type</th>
		<th class="mrpergola-col-xsmall">Wdt</th>
		<th class="mrpergola-col-xsmall">Dpt</th>
		<th class="mrpergola-col-xsmall">Color</th>
		<th class="mrpergola-col-large">Accessories</th>
		<th class="mrpergola-col-medium">Postal Code</th>
		<th>Info</th>
		<th class="mrpergola-col-large">Status</th>';
	echo '</tr></thead>';
	echo '<tbody>';
		
	foreach ($results as $row) {
		$formatted_entry_date = date('d/m', strtotime($row->entry_date));
		$formatted_due_date = date('d/m', strtotime($row->due_date));
		$profile_url = 'customer-profile.php?id=' . urlencode($row->id);
		$profile_url = site_url('/customer-profile/?id=' . urlencode($row->customer_id));
		$full_name = $row->first_name . ' ' . $row->last_name;
		$accessories = json_decode($row->accessories, true);
		echo '<tr data-id="' . esc_attr($row->id) . '">';
		echo '<td><a href="' . esc_url($profile_url) .  '" target="_blank" style="color:black;">' . esc_html($full_name) . '</a></td>';
		echo '<td> ' . esc_attr($formatted_entry_date) . '</td>';
		echo '<td>' . esc_attr($formatted_due_date) . '</td>';
		echo '<td> ' . esc_attr($row->model) . '</td>';
		echo '<td> ' . esc_attr($row->model_type) . '</td>';
		echo '<td>' . esc_attr($row->width) . '</td>';
		echo '<td>' . esc_attr($row->depth) . '</td>';
		echo '<td>' . esc_attr($row->color) . '</td>';
		echo '<td>' . esc_html(implode(', ', (array) $accessories)) . '</td>';
		echo '<td>' . esc_attr($row->postal_code) . '</td>';
		echo '<td>' . esc_html($row->info) . '</td>';
		echo '<td>' . esc_attr($row->order_status) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '</div>';

    return ob_get_clean();
}

add_shortcode('show_customer_data', 'display_customer_data');

/* ========================================================================================================================================== */
/*                                                      CUSTOMER PROFILE DATA                                                                 */
/* ========================================================================================================================================== */
/* ---------------------------- Retrieve Customer Profile Data ---------------------------- */
// REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('myplugin/v1', '/customer/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'myplugin_get_customer',
        'permission_callback' => '__return_true',
    ]);
});

function myplugin_get_customer(WP_REST_Request $request) {
    global $wpdb;

    $id = (int) $request['id'];
    if (!$id) {
        return new WP_Error('no_id', 'No ID provided', ['status' => 400]);
    }

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                c.id AS customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.phone_number,
                c.postal_code,
                f.id AS forecast_id,
                f.model,
                f.model_type,
                f.width,
                f.depth,
                f.louver_size,
                f.louver_qty,
                f.soldiers,
                f.subframe_size,
                f.subframe_qty,
                f.post_size,
                f.post_qty,
                f.due_date,
                f.entry_date,
                f.color,
                f.custom_fields,
                f.status AS forecast_status,
                f.order_status,
                f.accessories,
                f.image_url,
                f.team_assigned,
                f.order_status_notification,
				f.info
            FROM wp_custom_customers c
            LEFT JOIN wp_forecast_table f ON c.id = f.customer_id
            WHERE c.id = %d",
            $id
        ),
        ARRAY_A
    );

    if (empty($results)) {
        return new WP_Error('not_found', 'Customer not found', ['status' => 404]);
    }

    $customer = [
        'id'          => $results[0]['customer_id'],
        'first_name'  => $results[0]['first_name'],
        'last_name'   => $results[0]['last_name'],
        'email'       => $results[0]['email'],
        'phone_number'=> $results[0]['phone_number'],
        'postal_code' => $results[0]['postal_code'],
        'forecasts'   => [],
    ];

    foreach ($results as $row) {
        if ($row['forecast_id']) {
            $customer['forecasts'][] = [
                'id'                        => $row['forecast_id'],
                'image_url'                 => is_array(json_decode($row['image_url'], true)) ? json_decode($row['image_url'], true) : [],
                'team_assigned'             => $row['team_assigned'],
                'order_status'              => $row['order_status'],
                'order_status_notification' => $row['order_status_notification'],
                'model'                     => $row['model'],
                'model_type'                => $row['model_type'],
                'width'                     => $row['width'],
                'depth'                     => $row['depth'],
                'louver_size'               => $row['louver_size'],
                'louver_qty'                => $row['louver_qty'],
                'soldiers'                  => $row['soldiers'],
                'subframe_size'             => $row['subframe_size'],
                'subframe_qty'              => $row['subframe_qty'],
                'post_size'                 => $row['post_size'],
                'post_qty'                  => $row['post_qty'],
                'due_date'                  => $row['due_date'],
                'entry_date'                => $row['entry_date'],
                'color'                     => $row['color'],
                'custom_fields'             => $row['custom_fields'],
                'status'                    => $row['forecast_status'],
				'info'                      => $row['info'],
                'accessories'               => is_array(json_decode($row['accessories'], true)) ? json_decode($row['accessories'], true) : []
            ];
        }
    }

    return rest_ensure_response($customer);
}

/* ---------------------------- Save Customer Profile Data ---------------------------- */
function ajax_data_block_client_profile() {
    $nonce = wp_create_nonce('client_profile_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data-block-client-profile' data-nonce='{$nonce}' data-url='{$ajax_url}' style='display:none;'></div>";
}
add_shortcode('ajax_data_block_client_profile', 'ajax_data_block_client_profile');

function save_client_profile() {
 
	global $wpdb;

    $messages = [];

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'client_profile_nonce')) {
        wp_send_json_error('Wrong token.');
    } 

    $payload = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];
    if (!$payload) {
        wp_send_json_error('Payload is empty or invalid JSON.');
    } 


    $id          = isset($payload['id']) ? intval($payload['id']) : 0;
    $first_name  = isset($payload['first_name']) ? sanitize_text_field($payload['first_name']) : '';
    $last_name   = isset($payload['last_name']) ? sanitize_text_field($payload['last_name']) : '';
    $email       = isset($payload['email']) ? sanitize_email($payload['email']) : '';
    $postal_code = isset($payload['postal_code']) ? sanitize_text_field($payload['postal_code']) : '';
    $phone_num   = isset($payload['phone_number']) ? sanitize_text_field($payload['phone_number']) : '';

    if (empty($id)) {
        wp_send_json_error('Client profile save: Missing or invalid customer ID.');
    } 

    $result = $wpdb->update(
        'wp_custom_customers',
        [
            'postal_code'  => $postal_code,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'email'        => $email,
            'phone_number' => $phone_num
        ],
        ['id' => $id],
        ['%s','%s','%s','%s','%s'],
        ['%d']
    );

    if ($result === false) {
        $messages[] = 'Database update FAILED: ' . $wpdb->last_error;
        wp_send_json_error($messages);
    } elseif ($result === 0) {
        $messages[] = 'Update executed, but no rows affected (data may be identical to current DB).';
        wp_send_json_success($messages);
    } else {
        $messages[] = "Update successful. Rows affected: $result";
        wp_send_json_success($messages);
    }
}
add_action('wp_ajax_save_client_profile', 'save_client_profile');
add_action('wp_ajax_nopriv_save_client_profile', 'save_client_profile');

/* ---------------------------- Save Customer Order (forecast) Data ---------------------------- */
function ajax_data_block_client_order() {
    $nonce = wp_create_nonce('client_order_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data-block-client-order' data-nonce='{$nonce}' data-url='{$ajax_url}' style='display:none;'></div>";
}
add_shortcode('ajax_data_block_client_order', 'ajax_data_block_client_order');

function process_images($new_list, $old_list) {
    $final_urls = [];
    $messages   = [];

    // Make sure WP upload helpers are loaded
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    // Process new list
    foreach ($new_list as $item) {
        if (filter_var($item, FILTER_VALIDATE_URL)) {
            // Already a URL → keep it
            $final_urls[] = $item;
        } else {
            // It's a filename → look for the actual uploaded file
            $file_found = false;
            if (!empty($_FILES)) {
                foreach ($_FILES as $file_field) {
                    foreach ($file_field['name'] as $key => $filename) {
                        if ($filename === $item) {
                            $file_array = [
                                'name'     => $file_field['name'][$key],
                                'type'     => $file_field['type'][$key],
                                'tmp_name' => $file_field['tmp_name'][$key],
                                'error'    => $file_field['error'][$key],
                                'size'     => $file_field['size'][$key],
                            ];

                            $upload_overrides = ['test_form' => false];
                            $movefile = wp_handle_upload($file_array, $upload_overrides);

                            if ($movefile && !isset($movefile['error'])) {
                                $attachment = [
                                    'post_mime_type' => $movefile['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit',
                                ];

                                $attach_id   = wp_insert_attachment($attachment, $movefile['file']);
                                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                $final_urls[] = wp_get_attachment_url($attach_id);
                                $messages[] = "Uploaded $filename successfully";
                            } else {
                                $messages[] = "Failed to upload $filename";
                            }
                            $file_found = true;
                        }
                    }
                }
            }

            if (!$file_found) {
                $messages[] = "File $item not found in uploaded files";
            }
        }
    }

    // Remove old items not in the new list
    foreach ($old_list as $old_url) {
        if (!in_array($old_url, $final_urls, true)) {
            $attach_id = attachment_url_to_postid($old_url);
            if ($attach_id) {
                wp_delete_attachment($attach_id, true);
                $messages[] = "Deleted old file: $old_url";
            }
        }
    }

    return [$final_urls, $messages];
}


function save_client_order() { 
    global $wpdb;

    // Vérif du nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'client_order_nonce')) {
        wp_send_json_error('Wrong token.');
    }

    $decoded = [];
    if (!empty($_POST["data"])) {
        $decoded = json_decode(stripslashes($_POST["data"]), true);
    }

    $results = [];
    $messages = [];

    if (empty($decoded)) {
        wp_send_json_error('No payload decoded.');
    }

    foreach ($decoded as $index => $item) {
		$id          = isset($item["id"])           ? $item["id"]           : null;
		$model       = isset($item["model"])        ? $item["model"]        : null;
		$modelType   = isset($item["model_type"])   ? $item["model_type"]   : null;
		$width       = isset($item["width"])        ? formatFeetInches($item["width"]) : null;
		$depth       = isset($item["depth"])        ? formatFeetInches($item["depth"]) : null;
		$louverSize  = isset($item["louver_size"])  ? $item["louver_size"]  : null;
		$louverQty   = isset($item["louver_qty"])   ? formatFeetInches($item["louver_qty"]) : null;
		$subframeSize= isset($item["subframe_size"])? $item["subframe_size"] : null;
		$subframeQty = isset($item["subframe_qty"]) ? formatFeetInches($item["subframe_qty"]) : null;
		$postSize    = isset($item["post_size"])    ? $item["post_size"]    : null;
		$postQty     = isset($item["post_qty"])     ? formatFeetInches($item["post_qty"]) : null;
		$soldiers    = isset($item["soldiers"])     ? $item["soldiers"]     : null;
		$color       = isset($item["color"])        ? $item["color"]        : null;
		$extras      = isset($item["extras"])       ? $item["extras"]       : null;
		$info        = isset($item["info"])         ? $item["info"]         : null;
		$orderStatus = isset($item["order_status"]) ? $item["order_status"] : null;
		$teamSelected= isset($item["team_selected"])? $item["team_selected"]: null;
		$accessories = isset($item["extras"])? $item["extras"]: null;
		
        $fields = [
            'model'        => $model,
            'model_type'   => $modelType,
            'width'        => $width,
            'depth'        => $depth,
            'louver_size'  => $louverSize,
            'louver_qty'   => $louverQty,
            'subframe_size'=> $subframeSize,
            'subframe_qty' => $subframeQty,
            'post_size'    => $postSize,
            'post_qty'     => $postQty,
            'soldiers'     => $soldiers,
            'color'        => $color,
            'accessories'  => $accessories,
            'info'         => $info,
			'order_status' => $orderStatus,
			'team_assigned' => $teamSelected
        ];

    $fields = array_filter($fields, function($value) {
        return $value !== null;
    });

		if (empty($fields)) {
			continue;
		}

		$formats = array_fill(0, count($fields), '%s');

		$result = $wpdb->update(
			'wp_forecast_table',
			$fields,
			['id' => $id],
			$formats,
			['%d']
		);

		$messages[] = [
			"row"             => $index,
			"fields"          => $fields,
			"wpdb_last_query" => $wpdb->last_query,
			"wpdb_last_error" => $wpdb->last_error,
			"update_result"   => $result
		];
    }

    wp_send_json_success([
        "payload_received" => $decoded,
        "update_results"   => $results,
        "troubleshooting"  => $messages
    ]);
}
	
	
      /*  $table_name = 'wp_forecast_table';
        $formats = array_fill(0, count($fields), '%s');

        $result = $wpdb->update($table_name, $fields, ['id' => $forecast_id], $formats, ['%d']);

        if ($result === false) {
            $messages[] = 'Database update FAILED: ' . $wpdb->last_error;
        } elseif ($result === 0) {
            $messages[] = "Forecast ID $forecast_id: no rows changed";
        } else {
            $messages[] = "Forecast ID $forecast_id updated. Rows affected: $result";
        }
    }

    wp_send_json_success($messages);
}*/

// Hook it up
add_action('wp_ajax_save_client_order', 'save_client_order');
add_action('wp_ajax_nopriv_save_client_order', 'save_client_order');


/* ---------------------------- Show Customer Notes ---------------------------- */
add_shortcode('show_customer_notes', function() {
    global $wpdb;

    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    if (!$id) {
        return '<p>No customer ID provided in the URL.</p>';
    }

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT c.*, n.* 
             FROM wp_custom_customers c
             INNER JOIN wp_customer_notes n ON c.id = n.customer_id
             WHERE c.id = %d",
            $id
        )
    );

    if (!$results) {
        return '<p style="font-family: Roboto, sans-serif;">No notes found for this customer.</p>';
    }

    $html_table = '
    <div class="notes-table-wrapper">
        <table id="notes_table" class="notesTable">
            <thead>
                <tr>
                    <th style="width:20%">Timestamp</th>
                    <th style="width:5%">Type</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($results as $row) {
        $style = '';
        if ($row->note_type === 'service') {
            $style = 'color: red;';
        }

        $html_table .= '<tr>';
        $html_table .= '<td style="' . $style . '">' . esc_html($row->timestamp) . '</td>';
        $html_table .= '<td style="' . $style . '">[' . esc_html(strtoupper($row->note_type)) . ']</td>';
        $html_table .= '<td style="' . $style . '">' . esc_html($row->note_content) . '</td>';
        $html_table .= '</tr>';
    }

    $html_table .= '
            </tbody>
        </table>
    </div>';

    return $html_table;
});

/* ---------------------------- Save Customer Note ----------------------------------- */
function ajax_data_block_client_note() {
    $nonce = wp_create_nonce('client_note_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    return "<div id='ajax-data-block-client-note' data-nonce='{$nonce}' data-url='{$ajax_url}' style='display:none;'></div>";
}
add_shortcode('ajax_data_block_client_note', 'ajax_data_block_client_note');

function save_client_note() {
	global $wpdb;
    $messages = [];

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'client_note_nonce')) {
        wp_send_json_error('Wrong token.');
    } 

	$payload = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];

    if (!$payload) {
        wp_send_json_error('Payload is empty or invalid JSON.');
    } 

    $id          = isset($payload['id']) ? intval($payload['id']) : 0;
    $noteType    = isset($payload['noteType']) ? sanitize_text_field($payload['noteType']) : '';
    $noteContent = isset($payload['noteContent']) ? sanitize_text_field($payload['noteContent']) : '';
  
    if (empty($id)) {
        wp_send_json_error('Client profile save: Missing or invalid customer ID.');
    } 

   	$inserted_note = $wpdb->insert(
		'wp_customer_notes',
		[
			'customer_id'  	=> $id,
			'note_type'     => $noteType,
			'note_content'  => $noteContent,
			'timestamp'     => (new DateTime())->format('Y-m-d H:i:s')
		],
		['%d','%s','%s','%s']
	);

	if ($inserted_note === false) {
		wp_send_json_error(['message' => 'Insert failed']);
	} else {
		wp_send_json_success(['message' => 'Customer note inserted successfully']);
	}
}
add_action('wp_ajax_save_client_note', 'save_client_note');
add_action('wp_ajax_nopriv_save_client_note', 'save_client_note');


/* ========================================================================================================================================== */
/*                                                       EMAIL TASK                                                                           */
/* ========================================================================================================================================== */

// Query DB for tasks due and send email
function send_email($to, $subject, $message, $addToCalendar){
	$start = urlencode(date('Ymd\THis\Z', strtotime($task->due_date . ' 09:00')));
	$end = urlencode(date('Ymd\THis\Z', strtotime($task->due_date . ' 10:00')));
	$details = urlencode("Reminder: Task due on {$task->date}");
	$title = urlencode("Task: {$task->task}");
	
	if ($addToCalendar){
		$calendar_link = "https://www.google.com/calendar/render?action=TEMPLATE&text=$title&dates=$start/$end&details=$details";
		$message .= "Add it to your <a href=\"$calendar_link\" target='_blank'>Google Calendar</a><br>";	
	}
		
	$headers = array('Content-Type: text/html; charset=UTF-8');
	wp_mail($to, $subject, $message, $headers);
}

function send_task_reminder_emails() {
    global $wpdb;

    date_default_timezone_set('America/New_York'); // or your UTC-4 zone

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM wp_custom_form_todolist WHERE status = %d", 0)
    );

    if (empty($results)) {
        return;
    }

    foreach ($results as $task) {
        $message = "";
        $subject = "";
        $due_date = date('Y-m-d', strtotime($task->date));
        $to = $task->email. ',info@mrpergola.com';

        if ($due_date === $tomorrow) {
            $subject = 'Reminder: Task Due Tomorrow';
            $message = "Hi {$task->assignee},<br> This is a reminder that your task \"{$task->task}\" is due tomorrow.<br><br>";
        } elseif ($due_date < $today) {
            $subject = 'Task Overdue';
            $message = "Hi {$task->assignee},<br> This is a reminder that your task \"{$task->task}\" was due on: <span style='color: red;'>{$due_date}</span><br><br>";
        } else {
            continue; // Skip tasks not due tomorrow or overdue
        }

        send_email($to, $subject, $message, true);
    }
}
add_action('send_daily_task_reminder', 'send_task_reminder_emails');

// Schedule for cron event task. This uses the WP cron plugin accessible through the left panel > Settings > Cron Schedules
function schedule_daily_task_reminder() {
    if (!wp_next_scheduled('send_daily_task_reminder')) {
        wp_schedule_event(time(), 'daily', 'send_daily_task_reminder');
    }
}
add_action('wp', 'schedule_daily_task_reminder');
// hide update notifications
function remove_core_updates(){
global $wp_version;return(object) array('last_checked'=> time(),'version_checked'=> $wp_version,);
}
add_filter('pre_site_transient_update_core','remove_core_updates'); 
add_filter('pre_site_transient_update_plugins','remove_core_updates');
add_filter('pre_site_transient_update_themes','remove_core_updates');

/* ----------------------------  LOADING LOGO - GLOBAL FUNCTION  ---------------------------- */
function add_spinner_loading() {
     ?>
    <style>
    #loading-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.4); 
        backdrop-filter: blur(3px); 
        z-index: 999999 !important;
    }

    /* Spinner au-dessus de l'overlay */
    #loading-spinner {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000000 !important;
    }

    .spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #020508;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg);}
        100% { transform: rotate(360deg);}
    }
    </style>

    <div id="loading-overlay"></div>
    <div id="loading-spinner">
        <div class="spinner"></div>
    </div>
    <?php
}
add_action('wp_footer', 'add_spinner_loading');

function add_js_spinner() {
   ?>
    <script>
    function showLoading() {
        document.getElementById('loading-overlay').style.display = 'block';
        document.getElementById('loading-spinner').style.display = 'block';
    }

    function hideLoading() {
        document.getElementById('loading-overlay').style.display = 'none';
        document.getElementById('loading-spinner').style.display = 'none';
    }
    </script>
    <?php
}
add_action('wp_footer', 'add_js_spinner');
?>
