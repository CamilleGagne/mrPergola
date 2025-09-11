<script>
function makeDateFormat(input){
	try{
		var parts = input.split('/');
		var day = parts[0];
		var month = parts[1];
		var year = new Date().getFullYear(); // get current year

	// Manually pad day and month if needed
		if (day.length < 2){day = '0' + day;}
		if (month.length < 2) {month = '0' + month;}

		var dateString = year + '-' + month + '-' + day;
		
		return dateString;
	}catch (err){
		return {error: 'Date format must be DD/MM' };
	}
}	
	
function checkForecastInputs(data) {	
	if (!['Free', 'Wall', 'None'].includes(data.values[4])) {
		return { error: 'Type value must be Free, Wall or None' };
	}
	
	if (!['3S', '4S', 'Modern', 'SF'].includes(data.values[3])) {
		return { error: 'Mdl value must be 3S, 4S, Modern, SF'};
	}
	
	data.values[1] = makeDateFormat(data.values[1]);
	data.values[2] = makeDateFormat(data.values[2]);
	
	if (data.values[1].error){
		return {error: data.values[1].error};
	}
	
	if (data.values[2].error){
		return {error: data.values[2].error};
	}
	
	var canadianPattern = /^[A-Z]\d[A-Z][ ]?\d[A-Z]\d$/;

	if (!canadianPattern.test(data.values[15])){
		alert('Postal Code does not respect Canadian format. Please double check your input.');
	}
	

	return data;
}
	
function checkFixListInputs(data){
	if (!['1', '2', '3'].includes(data.values[1])) {
		return {error: 'Priority value must be between 1 and 3.'};
	}
				
	data.values[2] = makeDateFormat(data.values[2]);
	data.values[3] = makeDateFormat(data.values[3]);
	
	if (data.values[2].error){
		return {error: data.values[2].error};
	}
	
	if (data.values[3].error){
		return {error: data.values[3].error};
	}
	
	return data;	
}	
	
	
function checkTodoInputs(data){
	if (!['1', '2', '3'].includes(data.values[1])) {
		return {error: 'Priority value must be between 1 and 3.'};
	}
		
	return data;	
}		
	
function saveData(tableIds, cbAction, btnId, ajaxNonce) {
		var changedData = [];
		var hasError = false;

    document.querySelectorAll(tableIds).forEach(function(row) {
        var rowData = {
            id: row.dataset.id,
            values: []
        };

        row.querySelectorAll('td').forEach(function(cell) {
            if (cell.querySelector('input[type="checkbox"]')) {
                var isChecked = cell.querySelector('input[type="checkbox"]').checked ? 1 : 0;
                rowData.values.push(isChecked);
            } else {
                rowData.values.push(cell.innerText);
            }
        });

			// Update completion date for fix list if completed
			if (btnId === 'fixlist-save-btn') {
				rowData = checkFixListInputs(rowData);
				if (rowData.error){
					hasError = true;
					alert(rowData.error);
				}else if (rowData.values[0] === 1) {
					rowData.values[3] = new Date().toISOString().slice(0, 10);
				} 
			}else if(btnId === 'forecast-save-btn'){
				rowData = checkForecastInputs(rowData);
				if (rowData.error){
					hasError = true;
					alert(rowData.error);
				}else if (rowData.values[0] === 1) {
					rowData.values[1] = new Date().toISOString().slice(0, 10);
				} 
			}else if(btnId === 'todo-save-btn'){
				rowData = checkTodoInputs(rowData);
				if (rowData.error){
					hasError = true;
					alert(rowData.error);
				}else if (rowData.values[0] === 1) {
					rowData.values[3] = new Date().toISOString().slice(0, 10);
				} 
			}
		
        changedData.push(rowData);
    });
	
		if (changedData.length > 0 && !hasError) {

			 // Send the AJAX request
			 fetch('/wp-admin/admin-ajax.php', {
				 method: 'POST',
				 headers: {
					 'Content-Type': 'application/x-www-form-urlencoded'
				 },
				 body: new URLSearchParams({
					 action: cbAction,
					 data: JSON.stringify(changedData),
					 _wpnonce: ajaxNonce
				 })
			 }).then(function(response) {
				 return response.json();
				 // Parse response as JSON
			 }).then(function(data) {
				 if (data && data.success) {
					 //Store index of active tab
					var activeTab = document.querySelector('.e-n-tab-title[aria-selected="true"]');
					if (activeTab) {
						var index = activeTab.getAttribute('data-tab-index');
					if (index !== null) {
							localStorage.setItem('activeEnTabIndex', index);
						}
					}
					 
					 // Reload page on success
					 location.reload();
					 alert('Table successfully saved!');

					 document.querySelectorAll('#fixlistTable_0 tbody tr.dirty, #fixlistTable_1 tbody tr.dirty, #forecastTable_0 tbody tr.dirty, #forecastTable_1 tbody tr.dirty, #todoTable_0 tbody tr.dirty, #todoTable_1 tbody tr.dirty').forEach(function(row) {
						 row.classList.remove('dirty');
					 });

				 } else {
					 console.log('error while saving');
					 alert('Error while saving your table: ' + data.data);
					 console.log('Error saving changes: ' + data.data);
				 }
			 }).catch(function(error) {
				 console.log('fetch error');
				 alert('Fetch error:', error);
				 console.error('Fetch error:', error);
			 });
		 } else if (!hasError) {
			 alert('No changes detected in the table');
		 }
	 }
	
document.addEventListener('DOMContentLoaded', function() {
	var fixlistSaveBtn = document.getElementById('fixlist-save-btn');
	var forecastSaveBtn = document.getElementById('forecast-save-btn');
	var todoSaveBtn = document.getElementById('todo-save-btn');
	
	//Ajax token to verify that the user making the request has no bad intentions.
	var ajaxDataDiv = document.getElementById('ajax-data');
	//var ajaxUrl = ajaxDataDiv.getAttribute('data-url');
	var ajaxNonce = ajaxDataDiv.getAttribute('data-nonce');
	
	if (fixlistSaveBtn){
		fixlistSaveBtn.addEventListener('click', function(e) {
      e.preventDefault();
			var btnId = fixlistSaveBtn.getAttribute('id');
			var tableIds = '#fixlistTable_0 tbody tr.dirty, #fixlistTable_1 tbody tr.dirty';
			var cbAction = 'save_fixlist_table'; 		
			saveData(tableIds, cbAction, btnId, ajaxNonce);
		});
	}
	
	if (forecastSaveBtn){
		forecastSaveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var btnId = forecastSaveBtn.getAttribute('id');
			var tableIds = '#forecastTable_0 tbody tr.dirty, #forecastTable_1 tbody tr.dirty';
			var cbAction = 'save_forecast_table'; 
			saveData(tableIds, cbAction, btnId, ajaxNonce);
		});
	}
	
	if (todoSaveBtn){
		todoSaveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var btnId = todoSaveBtn.getAttribute('id');
			var tableIds = '#todoTable_0 tbody tr.dirty, #todoTable_1 tbody tr.dirty';
			var cbAction = 'save_todo_table'; 
			saveData(tableIds, cbAction, btnId, ajaxNonce);
		});
	}
	
		//Automatically select tab user was on
	  var savedIndex = localStorage.getItem('activeEnTabIndex');
   	if (savedIndex === null) {return;}

		// Delay execution to allow Elementor to finish initializing
		setTimeout(function () {
			var tabToActivate = document.querySelector('.e-n-tab-title[data-tab-index="' + savedIndex + '"]');
			if (tabToActivate) {
				tabToActivate.click(); // Triggers Elementor's tab logic
			}

			localStorage.removeItem('activeEnTabIndex');
		}, 200);

});

</script>
