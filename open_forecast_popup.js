<script>
document.addEventListener('DOMContentLoaded', function () {
  var ajaxDataEl = document.getElementById('ajax-data-block-forecast');
  if (!ajaxDataEl) {
    console.error('Missing ajax-data-block element. Make sure shortcode is on the page.');
    return;
  }
  var ajaxNonce = ajaxDataEl.getAttribute('data-nonce');
  var ajaxUrl = ajaxDataEl.getAttribute('data-url');
  var buttons = document.querySelectorAll('.view-doc-button');
  
	//Add listener for all buttons in the table
	for (var i = 0; i < buttons.length; i++) {
    buttons[i].addEventListener('click', function () {
      var rowId = this.getAttribute('data-rowid');
      if (!rowId) {
        alert('Row ID missing on button.');
        return;
      }

			//Get data for the row	
      var params = new URLSearchParams();
      params.append('action', 'get_row_data');
      params.append('row_id', rowId);
      params.append('_wpnonce', ajaxNonce);
			
			showLoading();
			
      fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (data.success) {
          openModalWithData(data, rowId);
        } else {
          alert('Failed to fetch data: ' + (data.data || 'Unknown error'));
        }
      })
      .catch(function(error) {
        console.error('AJAX error:', error);
				hideLoading();
        alert('AJAX request failed. Check document size.');
      });
    });
  }
});
	
function openModalWithData(data, rowId){
	var docData = data.data;
	//Open modal
	if (typeof elementorProFrontend !== 'undefined' &&
			elementorProFrontend.modules && elementorProFrontend.modules.popup) {
				hideLoading();
				elementorProFrontend.modules.popup.showPopup({ id: 3745 });

				//Get rowId to update the DB on save
				setTimeout(function () {
					var hiddenInput = document.getElementById('forecast-current-row-id');
					if (hiddenInput) {
						hiddenInput.value = rowId;
					}

					var title = document.getElementById('forecast-modal-customer-name');
					if (title) {
						title.textContent = docData.name || 'No Name';
					}

					//Create clickable list in the popup	
					var content = document.getElementById('doc-input');
					if (content) {
						content.innerHTML = '';

						var docs = docData.documents;

						if (!Array.isArray(docs)) {
							docs = [];
						}

						if (docs.length > 0) {
							var list = document.createElement('ul');
	
							for (var i = 0; i < docs.length; i++) {
								var li = document.createElement('li');
								var link = document.createElement('a');

								link.textContent = docs[i].substring(docs[i].lastIndexOf('/') + 1);
								link.href = docs[i];
								link.style.color = 'blue';
								link.style.textDecoration = 'underline';
								link.style.cursor = 'pointer';
								
								//Open the image in a new window	
								(function(url) {
									link.addEventListener('click', function(e) {
										e.preventDefault();
										e.stopPropagation();
										window.open(url, '_blank', 'noopener,noreferrer');
									});
								})(docs[i]);
								
								li.appendChild(link);
								list.appendChild(li);
							}

							content.appendChild(list);
						}
					}
				}, 300);
		}
}
</script>
