<?php 
require_once '../config/db.php'; 
require_once '../includes/header.php';  

// Display any error messages
if(isset($_SESSION['register_errors'])) {
    $errors = $_SESSION['register_errors'];
    unset($_SESSION['register_errors']);
}

// Remember form data if there was an error
$formData = isset($_SESSION['register_data']) ? $_SESSION['register_data'] : [
    'name' => '',
    'email' => '',
    'specialty' => ''
];

if(isset($_SESSION['register_data'])) {
    unset($_SESSION['register_data']);
}
?>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <h2>Create Consultant Account</h2>
            <p>Join our network of professional consultants</p>
        </div>
        
        <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <form action="../actions/process_registrationCons.php" method="POST" class="register-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($formData['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($formData['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="specialty">Specialty/Expertise</label>
                <select name="specialty" class="form-control" required>
                    <option value="">Select Specialty</option>
                    <option value="Network Security" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Network Security') ? 'selected' : ''; ?>>Network Security</option>
                    <option value="Application Security" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Application Security') ? 'selected' : ''; ?>>Application Security</option>
                    <option value="Cloud Security" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Cloud Security') ? 'selected' : ''; ?>>Cloud Security</option>
                    <option value="Security Operations" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Security Operations') ? 'selected' : ''; ?>>Security Operations</option>
                    <option value="Risk Management" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Risk Management') ? 'selected' : ''; ?>>Risk Management</option>
                    <option value="Incident Response" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Incident Response') ? 'selected' : ''; ?>>Incident Response</option>
                    <option value="Other" <?php echo (isset($_POST['specialty']) && $_POST['specialty'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small class="form-text">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group terms-group">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="../terms-of-service.php">Terms of Service</a> and <a href="../privacy-policy.php">Privacy Policy</a></label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-register">Create Account</button>
            </div>
            
            <div class="form-links">
                Already have an account? <a href="login.php" class="login-link">Log in here</a>
            </div>
        </form>
    </div>
</div>

<style>
    .register-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        padding: 20px;
        background-color: #f5f8ff;
    }
    
    .register-card {
        width: 100%;
        max-width: 550px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 30px;
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .register-header h2 {
        color: #104da9;
        margin-bottom: 8px;
        font-size: 28px;
    }
    
    .register-header p {
        color: #6c757d;
        font-size: 16px;
    }
    
    .register-form .form-group {
        margin-bottom: 20px;
    }
    
    .register-form label {
        display: block;
        margin-bottom: 8px;
        color: #495057;
        font-weight: 500;
    }
    
    .register-form .form-control {
        width: 100%;
        padding: 12px 15px;
        font-size: 16px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        transition: border-color 0.2s ease;
    }
    
    .register-form .form-control:focus {
        border-color: #2c74df;
        outline: none;
        box-shadow: 0 0 0 3px rgba(44, 116, 223, 0.15);
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        font-size: 14px;
        color: #6c757d;
    }
    
    .terms-group {
        display: flex;
        align-items: flex-start;
    }
    
    .terms-group input {
        margin-right: 10px;
        margin-top: 4px;
    }
    
    .terms-group label {
        font-size: 14px;
        font-weight: normal;
        line-height: 1.4;
    }
    
    .terms-group a {
        color: #104da9;
        text-decoration: none;
    }
    
    .terms-group a:hover {
        text-decoration: underline;
    }
    
    .btn-register {
        width: 100%;
        padding: 12px;
        background-color: #104da9;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .btn-register:hover {
        background-color: #0d3d86;
    }
    
    .form-links {
        text-align: center;
        margin-top: 20px;
        font-size: 15px;
        color: #6c757d;
    }
    
    .login-link {
        color: #104da9;
        text-decoration: none;
    }
    
    .login-link:hover {
        text-decoration: underline;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert ul {
        margin: 0;
        padding-left: 20px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>