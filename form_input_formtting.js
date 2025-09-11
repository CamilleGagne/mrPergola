<script>	
document.addEventListener('DOMContentLoaded', function () {
	var form;  
	
	if (document.getElementById('fix_list_form')){
		form = document.getElementById('fix_list_form');
	}else if(document.getElementById('Forecast_form')){
		form = document.getElementById('Forecast_form');
	}else if (document.getElementById('todolist_form')){
		form = document.getElementById('todolist_form');
	}else{
		return;
	}
		
	
  //Disable chrome's autocomplete 
	form.setAttribute('autocomplete', 'off');
      
	
	/* Originally written to block past date in date picker, but removed on 06-09-2025
	var date = document.querySelector('input[type="date"]');
	if (date){
		  var today = new Date();
			var dd = String(today.getDate()).padStart(2, '0');
			var mm = String(today.getMonth() + 1).padStart(2, '0'); 
			var yyyy = today.getFullYear();
			today = yyyy + '-' + mm + '-' + dd;
			date.setAttribute('min', today);
	}*/
	
	//Prevent user from select the placeholder - Select - function 
	var selects = form.querySelectorAll('select');
	
		selects.forEach(function (select) {
			if (select.options.length > 0 && select.options[0].text.trim() === '- Select -') {
    		select.options[0].value = '';        // Make value empty
    		select.options[0].selected = true;   // Select as default
    		select.options[0].hidden = true;     // Hide from dropdown options
  }
	});
	
	
	 //Auto-formatting phone number
	  var phoneNumberInput = document.getElementById('form-field-fixlist_phone_number') ? document.getElementById('form-field-fixlist_phone_number') : document.getElementById('form-field-forecast_form_phone_num');
  	
		if (phoneNumberInput){
			phoneNumberInput.addEventListener('input', function () {formatPhoneNumber(phoneNumberInput);});
		}


	//Auto-formatting postal_code
	  var postalCodeInput = document.getElementById('form-field-fixlist_postal_code') ? document.getElementById('form-field-fixlist_postal_code') : document.getElementById('form-field-forecast_form_postal_code') ;
  	
		if (postalCodeInput){
				postalCodeInput.addEventListener('input', function() {formatPostalCode(postalCodeInput);});
		}
	
});
	
function formatPhoneNumber(phoneNumberInput) {
	var inputValue = phoneNumberInput.value;
	inputValue = inputValue.replace(/\D/g, ''); // Remove non-digit characters

	if (inputValue.length > 10) {
		inputValue = inputValue.slice(0, 10);
	}

	var formattedValue, areaCode, prefix, lineNumber = '';

	if (inputValue.length > 6) {
		areaCode = inputValue.slice(0, 3);
		prefix = inputValue.slice(3, 6);
		lineNumber = inputValue.slice(6, 10);
		formattedValue = areaCode + '-' + prefix + '-' +lineNumber;
	} else if (inputValue.length > 3) {
		areaCode = inputValue.slice(0, 3);
		prefix = inputValue.slice(3, 6);
		formattedValue = areaCode + '-' + prefix;
	} else {
		formattedValue = inputValue;
	}
	phoneNumberInput.value = formattedValue;
}

function formatPostalCode(postalCodeInput) {
		var inputValue = postalCodeInput.value;

		var firstPart, secondPart, formattedValue = '';

		if (inputValue.length === 6) {
			firstPart = inputValue.slice(0, 3).toUpperCase();
			secondPart = inputValue.slice(3, 6).toUpperCase();
			formattedValue = firstPart + ' ' + secondPart;
		}else {
			formattedValue = inputValue.toUpperCase();
		}
		postalCodeInput.value = formattedValue;
}
	
</script>	
