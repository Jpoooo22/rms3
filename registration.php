<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
	<title>Registration Form</title>
	<!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
	<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f2f2f2;
		}
		
		h1 {
			text-align: center;
			margin-top: 50px;
		}
		
		form {
			max-width: 500px;
			margin: auto;
			padding: 20px;
			background-color: #fff;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
			border-radius: 5px;
		}
		
		input[type=text], input[type=email], input[type=password] {
			width: 100%;
			padding: 12px 20px;
			margin: 8px 0;
			display: inline-block;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-sizing: border-box;
		}
		
		label {
			font-weight: bold;
		}
		
		button[type=submit] {
			background-color: #4CAF50;
			color: white;
			padding: 14px 20px;
			margin: 8px 0;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			width: 100%;
		}
		
		button[type=submit]:hover {
			background-color: #45a049;
		}
		
		.error {
			color: red;
			font-size: 0.8em;
			margin-bottom: 10px;
		}
		/* user creation form */
		.form-group {
			position: relative;
			margin-bottom: 2rem;
			margin-top: 1rem;
		}
		.form-control {
			border: none;
			border-bottom: 1px solid #ddd;
			border-radius: 0;
			padding-left: 2rem;
			font-size: 1.2rem;
			transition: all 0.3s;
		}
		.form-control:focus {
			border-color: #4c8bf5;
			box-shadow: none;
			outline: none;
		}
		.form-control:focus + .form-label {
			transform: translateY(-1.5rem) scale(0.8);
			color: #4c8bf5;
		}
		.form-label {
			position: absolute;
			top: 1.1rem;
			left: 2.5rem;
			font-size: 1rem;
			color: #666;
			transition: all 0.3s;
			transform-origin: left top;
			pointer-events: none;
		}
		.icon {
			position: absolute;
			top: 1rem;
			left: 0.5rem;
			font-size: 1.2rem;
			color: black;
		}	
		.form-group input, .form-group select {
			backdrop-filter: blur(10px);
			border-radius: 12px;
			color: black;
			padding-left: 40px;
		}
		.form-group input:focus{
			outline: none;
			background-color: rgba(255, 255, 255, 1);  
		}
	</style>
	
	<script>
		function toggleLabelVisibility(input) {
			var label = input.previousElementSibling;
		  if (input.value ) {
			$(input).next('.form-label').hide();
		  } else {
			$(input).next('.form-label').show();
		  }
		}
		function recommend(input) {
			var firstName = input.value;
			var lastName = document.getElementById("lastname").value;
			var username = lastName.toLowerCase() + "_" + firstName.toLowerCase();
			document.getElementById("usernameLabel").style.display = 'none';
			document.getElementById("username").value = username;
		}
		window.onload = function() {
		  var inputs = document.querySelectorAll('.form-control');
		  for (var i = 0; i < inputs.length; i++) {
			toggleLabelVisibility(inputs[i]);
			inputs[i].addEventListener('input', function() {
			  toggleLabelVisibility(this);
			});
		  }
		};
		function clearForm() {
		  // Get the form element
		  var form = document.getElementById("reg-form");

		  // Reset the form
		  form.reset();

		  // Clear the _POST array
		  var inputs = form.getElementsByTagName("input");
		  for (var i = 0; i < inputs.length; i++) {
			if (inputs[i].type !== "submit" && inputs[i].type !== "reset") {
			  inputs[i].value = "";
			}
		  }
		  // Reload the page
		  location.reload();
		}
	</script>
</head>
<body>

	<h1>Registration Form</h1>
	<form method="post" action="">
		<ul id="errors"></ul>
		
		<div class="form-group">
			<input type="text" class="form-control" id="name" name="name" onchange="toggleLabelVisibility(this.value)" >
			<label for="serial_number" class="form-label">Firstname*</label>
			<i class="icon bi bi-card-list"></i>
			<div class="error" id="name-error"></div>
		</div>
		
		<div class="form-group">
			<input type="email" class="form-control" id="email" name="email">
			<label for="email" class="form-label">Email*</label>
			<i class="icon bi bi-envelope"></i>
			<div class="error" id="email-error"></div>
		</div>
		<div class="form-group">
			<input type="password" class="form-control" id="password" name="password">
			<label for="password" class="form-label">Enter your password</label>
			<i class="icon bi bi-key"></i>
			<div class="error" id="password-error"></div>
		</div>
		<div class="form-group">
			<input type="password" class="form-control" id="confirm-password" name="confirm-password">
			<label for="confirm-password" class="form-label">Confirm your password</label>
			<i class="icon bi bi-key-fill"></i>
			<div class="error" id="confirm-password-error"></div>
		</div>
		<button type="submit">Register</button>
	</form>

		
	

    <script src="vendor/jquery/jquery.min.js"></script>                           
<script>
  const form = document.querySelector('form');
  const nameInput = document.getElementById('name');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm-password');

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    let errors = [];
    if (nameInput.value.trim() === '') {
      errors.push({ field: 'name', message: 'Name is required' });
    }
    if (emailInput.value.trim() === '') {
      errors.push({ field: 'email', message: 'Email is required' });
    } else if (!emailIsValid(emailInput.value)) {
      errors.push({ field: 'email', message: 'Please enter a valid email address' });
    }
    if (passwordInput.value.trim() === '') {
      errors.push({ field: 'password', message: 'Password is required' });
    } else if (passwordInput.value.length < 8) {
      errors.push({ field: 'password', message: 'Password must be at least 8 characters long' });
    } else if (passwordInput.value !== confirmPasswordInput.value) {
      errors.push({ field: 'confirm-password', message: 'Passwords do not match' });
    }
if (errors.length > 0) {
  // remove any existing error messages
  const errorElements = document.querySelectorAll('.error');
  errorElements.forEach((errorElement) => {
    errorElement.remove();
  });

  // display the latest error message
  const latestError = errors[errors.length - 1];
  const errorElement = document.createElement('div');
  errorElement.className = 'error';
  errorElement.textContent = latestError.message;
  const inputElement = document.getElementById(latestError.field);
  inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
} else {
  // submit form
  form.submit();
}

// clear errors array
errors = [];

  });
</script>
</body>
</html>