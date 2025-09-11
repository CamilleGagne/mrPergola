<script>
	
document.addEventListener('DOMContentLoaded', function() {
var emailField = document.querySelector('[name="form_fields[todolist_email]"]');	
var customEmailField = document.querySelector('[name="form_fields[todo_custom_email]"]');	
	
customEmailField.style.display = 'none';

emailField.addEventListener('change', function(){
	if (emailField.value && emailField.value === 'Other'){
		customEmailField.style.display = 'block';
	}else{
		customEmailField.style.display = 'none';
		customEmailField.value = null;
	}
});	
	
	
if (customEmailField) {
  customEmailField.addEventListener('blur', function(event) {
   var input = event.target.value.trim();

    // Split input into individual emails
    var rawEmails = input.split(',');
    var emails = [];
    var invalidEmails = [];

    var emailPattern =  /^[^\s@]+@[^\s@]+\.(com|net|org|edu|gov|co|io|us|ca|uk)$/i;

    for (var i = 0; i < rawEmails.length; i++) {
      var email = rawEmails[i].trim();
      if (email) {
        emails.push(email);
        if (!emailPattern.test(email)) {
          invalidEmails.push(email);
        }
      }
    }

    if (invalidEmails.length !== 0) {
			 alert('Email is not formatted properly.');
    } 
  });
}
	

});	
</script>	
