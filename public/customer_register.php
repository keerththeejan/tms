<?php
require_once __DIR__ . '/../app/bootstrap.php';

$error = '';
$success = '';
$name = '';
$current_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $selected_date = $_POST['selected_date'] ?? $current_date;
    
    // Basic validation - Only name is required
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        // If validation passes, you can save to database here
        // For now, we'll just show success message
        $success = 'Customer details saved successfully!';
        
        // Clear form
        $name = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Customer Registration Form</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name <span style="color:red">*</span></label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        
        <div class="form-group">
            <button type="submit">Save</button>
        </div>
        
        <div class="form-group">
            <label for="selected_date">Date</label>
            <input type="date" id="selected_date" name="selected_date" 
                   value="<?php echo htmlspecialchars($selected_date); ?>"
                   style="padding: 8px; width: 100%;">
        </div>
    </form>
    
    <script>
        // Initialize date picker
        document.addEventListener('DOMContentLoaded', function() {
            var dateInput = document.getElementById('selected_date');
            // Set minimum date to today
            var today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
            // Ensure calendar shows on click in all browsers
            dateInput.addEventListener('focus', function() {
                this.type = 'date';
                this.showPicker();
            });
            // For mobile devices
            dateInput.addEventListener('touchend', function() {
                this.type = 'date';
                this.focus();
            });
        });
        
        // Client-side validation for phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    </script>
</body>
</html>
