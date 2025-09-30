<script>	
document.addEventListener('DOMContentLoaded', function () {
	var ajaxDataDiv = document.getElementById('ajax-data-block-forecast_custom');
	var ajaxNonce = ajaxDataDiv.getAttribute('data-nonce');
	
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
	var color = document.querySelector('#forecast_color');	
	var customColor = document.querySelector('#custom_color');	
	var options = document.querySelector('#forecast_options_other');	
	var customOptions = document.querySelector('#custom_extra');	
	var isModern = false;
	var fieldToDisableList = [widthField, depthField, louverSizeField, louverQtyField, soldiersField, subframeSizeField, subframeQtyField, postSizeField, postQtyField];
	var docTrigger = document.getElementById('doc-trigger');
	var imageTrigger = document.getElementById('image-trigger');
	var docInput = document.getElementById('customer_docs');
	var imageInput = document.getElementById('customer_images');
	var fileList = document.getElementById('file-list');
	var imageList = document.getElementById('image-list');
	var saveBtn = document.getElementById('forecast_save_btn');
	var successContainer = document.getElementById('success_message');
	var errorContainer = document.getElementById('error_message');
			//customer
	var date = document.getElementById('due_date');
	var color = document.getElementById('forecast_color');
	var firstName = document.getElementById('first_name');
	var lastName = document.getElementById('last_name');
	var email = document.getElementById('forecast_email');
	var postalCode = document.getElementById('forecast_postal_code');
	var phoneNum = document.getElementById('forecast_phone_num');
	var language = document.getElementById('forecast_language');

function addFilesToUi(files, list) {
    files = Array.from(files);
    files.forEach((file) => {
        // Container for each file
        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'space-between';
        div.style.padding = '6px 10px';
        div.style.marginBottom = '6px';
        div.style.border = '1px solid #ddd';
        div.style.borderRadius = '6px';
        div.style.backgroundColor = '#fafafa';
        div.style.fontSize = '14px';

        // Thumbnail (if image)
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '60px';
                img.style.height = '60px';
                img.style.objectFit = 'cover';
                img.style.marginRight = '8px';
                div.insertBefore(img, div.firstChild);
            };
            reader.readAsDataURL(file);
        }

        // Editable file name
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.value = file.name;
        nameInput.style.flex = '1';
        nameInput.style.marginRight = '8px';
        nameInput.style.fontSize = '14px';
        nameInput.style.border = '1px solid #ccc';
        nameInput.style.borderRadius = '4px';
        nameInput.style.padding = '2px 6px';

        // Update file object with new name
        file.newName = nameInput.value;
        nameInput.addEventListener('input', () => {
            file.newName = nameInput.value;
        });

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '✕';
        removeBtn.style.color = '#fff';
        removeBtn.style.background = '#e74c3c';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '4px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.padding = '2px 6px';
        removeBtn.style.fontSize = '12px';
        removeBtn.addEventListener('click', () => {
            div.remove();
            if (list.children.length === 0) {
                list.textContent = 'No file selected';
            }
        });

        // Append elements
        div.appendChild(nameInput);
        div.appendChild(removeBtn);
        list.appendChild(div);
    });
}


	
// ===================== FILE PICKER LOGIC ===================== 
	// Open document filepicker
	docTrigger.addEventListener('click', function() {
			if (docInput) {
					docInput.click();
			}
	});

	docInput.addEventListener('change', function() {
    fileList.innerHTML = '';

    if (docInput.files.length === 0) {
        fileList.textContent = 'No file selected';
        return;
    }
		addFilesToUi(docInput.files, fileList);
	});
	
	imageTrigger.addEventListener('click', function() {
			if (imageInput) {
					imageInput.click();
			}
	});

	imageInput.addEventListener('change', function() {
    imageList.innerHTML = '';

    if (imageInput.files.length === 0) {
        imageList.textContent = 'No file selected';
        return;
    }
		addFilesToUi(imageInput.files, imageList);
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
	
	firstName.addEventListener('change', function(){
		if (firstName && firstName.value){
			if (email && email.value){
				saveBtn.disabled=false;
			}
		}
	});
	
	email.addEventListener('change', function(){
		if (firstName && firstName.value){
			if (email && email.value){
				saveBtn.disabled=false;
			}
		}
	});

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

	color.addEventListener('change', function(){
		if(color.value && color.value === 'other'){
			 customColor.classList.remove('hidden');
		}else{
			 customColor.classList.add('hidden');
		}
	});
	
	options.addEventListener('change', function () {
    if (options.checked) {
        customOptions.classList.remove('hidden');
    } else {
        customOptions.classList.add('hidden');
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

	if (saveBtn){
		saveBtn.disabled = true;
	}
	disableFields(true, fieldToDisableList);	
	
	/*.  SAVE BTN LOGIC.  */
	saveBtn.addEventListener('click', function(e){
		 e.preventDefault();
 	
		var formData = new FormData();
	 	formData.append('first_name', firstName.value || '');
    formData.append('last_name', lastName.value || '');
    formData.append('email', email.value || '');
    formData.append('postal_code', postalCode.value || '');
		formData.append('phoneNum', phoneNum.value || '');
		formData.append('language', language.value || '');
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
		
		if (color.value && color.value === 'other'){
			formData.append('color', customColor.value || '');
		}else{
			formData.append('color', color.value || '');
		}
		
		var customValues = [];
		[customWidth, customDepth, customSubQty, customSubSize, customPostQty, customPostSize].forEach(function(el) {
    if (el && el.value.trim() !== '') {
        customValues.push(el.name);
    }
	});
		for (var k = 0; k < customValues.length; k++) {
    	formData.append('custom[]', customValues[k]);
		}

    // Add checked extras values
    var extras = document.querySelectorAll('.checkboxes input[type="checkbox"]:checked');
    for (var i = 0; i < extras.length; i++) {
        formData.append('extras[]', extras[i].value);
    }
		
		if (options && options.checked && customOptions && customOptions.value) {
				var values = customOptions.value.split(',');
				for (var j = 0; j < values.length; j++) {
						formData.append('extras[]', values[j]);
				}
		}
			
    // Add documents
    if (docInput.files.length > 0) {
        for (var l = 0; l < docInput.files.length; l++) {
            formData.append('customer_docs[]', docInput.files[l]);
        }
		}
		
		 if (imageInput.files.length > 0) {
        for (var m = 0; m < imageInput.files.length; m++) {
            formData.append('customer_images[]', imageInput.files[m]);
        }
		}
		
		for (const [key, value] of formData.entries()) {
				console.log(key, value);
		}
		
		saveForm(formData, ajaxNonce);
	});
	
	function saveForm(formData)
	{
		formData.append('action', 'save_forecast_form');
		formData.append('_wpnonce', ajaxNonce); 
		
		showLoading();
		// send via fetch
		fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: formData
		})
		.then(function(response) {
				return response.json(); // parse JSON
		})
		.then(function(data) {
				hideLoading();
				if (data && data.success) {
					if (successContainer){
						successContainer.classList.remove('hidden');
					}
					 setTimeout(function() {
							location.reload(); 
					}, 3000);
				} else {
					if (errorContainer){
						errorContainer.classList.remove('hidden');
					}
					alert('error:' + data.message);
				}
		});
	}
});
</script>
