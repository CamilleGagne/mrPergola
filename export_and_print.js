<script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
<script>

function sendError(){
	alert('Error while trying to export this table. This can happen if there is no data to show.');
	return;
}
	
function cloneTable(pageTitle){
	var tablist = document.querySelectorAll('.e-n-tab-title');
	var selectedIndex = 0; 
	var table, title, status = '';

	tablist.forEach(function (tab, index) {
		if (tab.getAttribute('aria-selected') === 'true') {
			selectedIndex = index;
		}
	});

	if (selectedIndex === 1){
		status = ' - Completed';
	}else{
		status = ' - To Do';
	}
	
	if (pageTitle.includes('Forecast') && pageTitle.includes('Mr Pergola')){
		if (selectedIndex === 2){
			table = document.getElementById('materialTable');
			title = 'Material - Forecast';
		}else{
			table = document.getElementById('forecastTable_' + selectedIndex);
			title = 'Forecast' + status;
		}
	}else if(pageTitle.includes('Fixlist') && pageTitle.includes('Mr Pergola')){
		table = document.getElementById('fixlistTable_' + selectedIndex);
		title = 'Fixlist' + status;
	}else if(pageTitle.includes('To Do List') && pageTitle.includes('Mr Pergola')){
		table = document.getElementById('todoTable_' + selectedIndex);
		title = 'To Do List' + status;
	}else{
		sendError();
		return;
	}

	if (!table || table === ''){
		sendError();
		return;
	}

	var clonedTable = table.cloneNode(true);

	if (table.id !== 'materialTable') {
		var headers = clonedTable.getElementsByTagName('th');
		var statusIndex = 0;

		if (headers.length > statusIndex) {
			headers[statusIndex].remove();
		}

		var rows = clonedTable.getElementsByTagName('tr');
		for (var j = 0; j < rows.length; j++) {
			var cells = rows[j].getElementsByTagName('td');
			if (cells.length > statusIndex) {
				rows[j].deleteCell(statusIndex);
			}
		}
	}
	
	if (table.id.includes('forecastTable_')){
			for (var i = 0; i < clonedTable.rows.length; i++) {
						var row = clonedTable.rows[i];
						if (row.cells.length > 0) {
							row.deleteCell(row.cells.length - 1);
						}
					}			
	}
	

	return { table: clonedTable, title: title };
}

document.addEventListener('DOMContentLoaded', function () {
	var exportButton = document.getElementById('export-excel-btn');
	var printButton = document.getElementById('print-btn');
	var pageTitle = document.title;

	if (exportButton) {
		exportButton.addEventListener('click', function () {
			var result = cloneTable(pageTitle);
			if (!result) {return;}
			
			TableToExcel.convert(result.table, {
				name: result.title + '.xlsx',
				sheet: {
					name: result.title
				}
			});
		});
	}
	
	if (printButton) {
		printButton.addEventListener('click', function () {
			var result = cloneTable(pageTitle);
			if (!result) {return;}
			
			var printWindow = window.open('', '', 'height=600,width=800');
			printWindow.document.write('<html><head><title>' + result.title + '</title>');
			printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; font-size:11px; } td, th { border: 1px solid #000; padding: 1px; }</style>');
			printWindow.document.write('</head><body>');
			printWindow.document.write(result.table.outerHTML);
			printWindow.document.write('</body></html>');
			printWindow.document.close();
			printWindow.focus();
			printWindow.print();
			printWindow.close();
			
		});
	}
});
	
//Remove last column to exclude the folder icon	
function removeLastColumn(table){
	for (var i = 0; i < table.rows.length; i++) {
		var row = table.rows[i];
		if (row.cells.length > 0) {
			row.deleteCell(row.cells.length - 1);
		}
	}
	return table;
}	
	
</script>
