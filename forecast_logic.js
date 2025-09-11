<script>	
document.addEventListener('DOMContentLoaded', function () {
	var widthField = document.querySelector('#frame_width_unit');
	var depthField = document.querySelector('#frame_depth_unit');
	var louverSizeField = document.querySelector('#louver_length');
	var louverQtyField = document.querySelector('#louver_qty');
	var soldiersField = document.querySelector('#forecast_soldier');
	var subframeQtyField = document.querySelector('#subframe_qty_unit');
	var subframeSizeField = document.querySelector('#subframe_length_unit');
	var postSizeField = document.querySelector('#post_length_unit');	
	var postQtyField = document.querySelector('#post_qty_unit');
	var modelType = document.querySelector('#forecast_model_type');		
	var customWidth = document.querySelector('#custom_frame_width');
	var customDepth = document.querySelector('#custom_frame_depth');
	var customSubQty = document.querySelector('#custom_subframe_qty');
	var customSubSize = document.querySelector('#custom_subframe_length');		
	var customPostQty = document.querySelector('#custom_post_qty');
	var customPostSize = document.querySelector('#custom_post_length');			
	var modelField = document.querySelector('#forecast_model');	
	var isModern = false;
	var fieldToDisableList = [widthField, depthField, louverSizeField, louverQtyField, soldiersField, subframeSizeField, subframeQtyField, postSizeField, postQtyField];
	var trigger = document.getElementById('file-trigger');
	var input = document.getElementById('customer_images');
	var fileList = document.getElementById('file-list');
	var saveBtn = document.getElementById('forecast_save_btn');

// ===================== FILE PICKER LOGIC ===================== 
	// Open filepicker
	trigger.addEventListener('click', function() {
			if (input) {
					input.click();
			}
	});

	input.addEventListener('change', function() {
			
			fileList.innerHTML = '';

			if (input.files.length === 0) {
					fileList.textContent = 'No file selected';
					return;
			}

			var files = Array.prototype.slice.call(input.files);
			for (var i = 0; i < files.length; i++) {
					var div = document.createElement('div');
					div.textContent = files[i].name;
					fileList.appendChild(div);
			}

			// send data
			/*
			var formData = new FormData();
			for (var j = 0; j < files.length; j++) {
					formData.append('customer_image[]', files[j]);
			}

			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/upload-endpoint', true);
			xhr.onload = function () {
					if (xhr.status === 200) {
							console.log('Upload Succeeded', xhr.responseText);
					} else {
							console.error('Error', xhr.statusText);
					}
			};
			xhr.send(formData);
			*/
	});

	// ===================== FORM LOGIC ===================== 
	//Disable all fields until Model is selected 	
	function disableFields(disable, fieldList){
		if (disable){
			fieldList.forEach(function(field){
				if (field){
					field.disabled = true;
					field.value = null;
				}
			});
		}else{
			fieldList.forEach(function(field){
				if (field){
					field.disabled = false;
					field.selectedIndex = 0;
				}
			});
		}
	}	

	function cleanInput(input){
		if (input){
			var i = input.split('\'');
			return parseInt(i[0],0);
		}
	}	


	/* Qty louvers * subframe Qty */	
	function calculateSoldiers(){
		if (isModern && louverQtyField && louverQtyField.value){
			soldiersField.value = louverQtyField.value * 3;
		}else{
			var louver = 0;
			var subframeQty = 0;
			if (louverQtyField.value){
				louver = cleanInput(louverQtyField.value);
			}

			if (subframeQtyField.value && subframeQtyField.value !== '- Select -' && subframeQtyField.value !== 'custom'){
				subframeQty = cleanInput(subframeQtyField.value);
			}else if(subframeQtyField.value && subframeQtyField.value === 'custom'){
				subframeQty = cleanInput(customSubQty.value);
			}else{
				soldiersField.value = null;
			}

			if(louver > 0 && subframeQty > 0){
				soldiersField.value = louver * subframeQty;
			}
		}
	}	


	function calculateLouverQty(){
		if (isModern && widthField && widthField.value){
			var width = cleanInput(widthField.value);
			louverQtyField.value = width * 2;
		}else{
			/*subframeSize in inches / 6*/	
			if (subframeSizeField.value && subframeSizeField.value !== 'custom'){
				get_louver_value(cleanInput(subframeSizeField.value));
			}else if(customSubSize.value){
				if (!isModern){
					get_louver_value(cleanInput(customSubSize.value));
				}else{
					if (widthField.value && widthField.value !== 'custom' && widthField.value !=='None'){
						louverQtyField.value = widthField.value * 2;
					}
				}
			}else{
				louverQtyField.value = null;
			}
		}
	}	

	function get_louver_value(subsize){
		if (subsize && subsize > 0){
			louverQtyField.value = (subsize*12)/6;
			calculateSoldiers();
		}
	}	

	function calculateNoWidthNoDepth(){
		if(widthField.value === 'None' && depthField.value === 'None'){
			var subframeQty = cleanInput(subframeQtyField.value);
			if (subframeQty > 0){
				soldiersField.value = (subframeQty * 2) * subframeQty;
			}
		}
	}	

	/* ===== EVENT LISTENERS FOR FORM CALCULATIONS ===== */
	widthField.addEventListener('change', function() {
		if (widthField.value === 'custom'){
			customWidth.classList.remove('hidden');
		}else if (widthField.value === 'None'){
			customWidth.classList.add('hidden');
			customWidth.value = null;
			calculateNoWidthNoDepth();
		}else{
			customWidth.classList.add('hidden');
			customWidth.value = null;
			if (modelField && modelField.value === 'Modern'){
				calculateLouverQty();
				calculateSoldiers();
			}
		}
	});

	depthField.addEventListener('change', function() {
		if (depthField.value === 'custom'){
			customDepth.classList.remove('hidden');
			louverSizeField.value = null;
		}else if (depthField.value === 'none'){
			customDepth.classList.add('hidden');
			customDepth.value = null;
			calculateNoWidthNoDepth();
		}else{
			customDepth.classList.add('hidden');
			customDepth.value = null;
			louverSizeField.value = depthField.value;
		}
	});

	customDepth.addEventListener('input', function(){
		louverSizeField.value = customDepth.value;
	});

	subframeSizeField.addEventListener('input', function() {
		if (subframeSizeField.value === 'custom'){
			customSubSize.classList.remove('hidden');
		}else{
			customSubSize.classList.add('hidden');
			customSubSize.value = null;
		}
		calculateLouverQty();
	});

	customSubSize.addEventListener('input', function(){
		calculateLouverQty();
	});

	subframeQtyField.addEventListener('change', function(){
		if (subframeQtyField.value === 'custom'){
			customSubQty.classList.remove('hidden');
			soldiersField.value = null;
		}else{
			customSubQty.classList.add('hidden');
			customSubQty.value = null;
		}
		calculateSoldiers();
	});

	customSubQty.addEventListener('input', function(){
		calculateSoldiers();
	});

	louverQtyField.addEventListener('input', function() {
		calculateSoldiers();
	});	

	postSizeField.addEventListener('change', function(){
		if (postSizeField.value === 'custom'){
			customPostSize.classList.remove('hidden');	
		}else{
			customPostSize.classList.add('hidden');
			customPostSize.value = null;
		}
	}) ;

	postQtyField.addEventListener('change', function(){
		if (postQtyField.value && postQtyField.value === 'custom'){
			customPostQty.classList.remove('hidden');		
		}else{
			customPostQty.classList.add('hidden');
			customPostQty.value = null;
		}
	}) ;

	modelField.addEventListener('change', function(){
		if (modelField.value){
			if(modelField.value === 'Modern'){
				isModern = true;
				disableFields(false, [widthField, depthField, louverSizeField, louverQtyField, soldiersField, postSizeField, postQtyField]);
				disableFields(true, [subframeSizeField, subframeQtyField]);
				subframeSizeField.value = null;
				subframeQtyField.value = null;
				setDefaultModel();
			}else if (modelField.value === 'SF'){
				isModern = false;
				disableFields(false, fieldToDisableList);
				disableFields(true, [widthField, depthField, postSizeField, postQtyField]);
				modelType.value = 'None';
				widthField.value = null;
				depthField.value = null;
				postSizeField.value = null;
				postQtyField.value = null;
			}else{
				isModern = false;
				disableFields(false, fieldToDisableList);
				setDefaultModel();
			}         
		}
	});


	modelType.addEventListener('change', function(){
		if (modelType.value && modelType.value === 'Free'){
			postQtyField.value = '4';
		}else if (modelType.value && modelType.value === 'Wall'){
			postQtyField.value = '2';
		}else if(modelType.value && modelType.value === 'None'){
			postQtyField.value = 'None';
		}
	});

	function setDefaultModel(){
		if (modelType.value === '- Select -' || modelType.value === 'None' || modelType.value === ''){
			modelType.value = 'Free';
		}
		if (postQtyField.value){
			if (postQtyField.value === '- Select -' || postQtyField.value === 'None' || postQtyField.value === ''){
				postQtyField.value = '4';
			} 

			if (postSizeField.value === '- Select -' || postSizeField.value === 'None' || postSizeField.value === ''){
				postSizeField.value = '7\'5"';
			}
		}				
	}

	disableFields(true, fieldToDisableList);	
	
	/*.  SAVE BTN LOGIC.  */
	saveBtn.addEventListener('click', function(e){
		 e.preventDefault();
 	
		//customer
		var date = document.getElementById('due_date');
		var color = document.getElementById('forecast_color');
		var first_name = document.getElementById('first_name');
		var last_name = document.getElementById('last_name');
		var email = document.getElementById('forecast_email');
		var postal_code = document.getElementById('forecast_postal_code');
		
		var formData = new FormData();
		
	 	formData.append('first_name', first_name.value || '');
    formData.append('last_name', last_name.value || '');
    formData.append('email', email.value || '');
    formData.append('postal_code', postal_code.value || '');
    formData.append('model', modelField.value || '');
    formData.append('modelType', modelType.value || '');
    formData.append('widthField', customWidth.value || widthField.value || '');
    formData.append('depthField', customDepth.value || depthField.value || '');
    formData.append('louverLength', louverSizeField.value || '');
    formData.append('louverQty', louverQtyField.value || '');
    formData.append('soldiers', soldiersField.value || '');
    formData.append('subframeQty', customSubQty.value || subframeQtyField.value || '');
    formData.append('subframeSize', customSubSize.value || subframeSizeField.value || '');
    formData.append('postSize', customPostSize.value || postSizeField.value || '');
    formData.append('postQty', customPostQty.value || postQtyField.value || '');
    formData.append('date', date.value || '');
    formData.append('color', color.value || '');

    // Add checked extras values
    var extras = document.querySelectorAll('.checkboxes input[type="checkbox"]:checked');
    for (var i = 0; i < extras.length; i++) {
        formData.append('extras[]', extras[i].value);
    }

    // Add documents
    if (input.files.length > 0) {
        for (var j = 0; j < input.files.length; j++) {
            formData.append('customer_image[]', input.files[j]);
        }
		}
		saveForm(formData);
	});
	

	
	function saveForm(formData){
	
		formData.append('action', 'save_forecast_form');
		formData.append('_wpnonce', ajaxNonce);
	
		// send via fetch
		fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
				body: formData
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			if (data && data.success) {
				console.log('Saved successfully:', data);
			} else {
				console.error('Save failed:', data);
			}
		});
	}
	
	
});
</script>
