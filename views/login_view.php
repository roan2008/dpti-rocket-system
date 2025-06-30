<?php include '../includes/header.php'; ?>

<div class="login-container">
    <div class="login-form">
        <h2>DPTI Rocket System - Login</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                switch ($_GET['error']) {
                    case 'invalid_credentials':
                        echo htmlspecialchars('Invalid username or password.');
                        break;
                    case 'missing_fields':
                        echo htmlspecialchars('Please fill in all fields.');
                        break;
                    case 'method_not_allowed':
                        echo htmlspecialchars('Invalid request method.');
                        break;
                    default:
                        echo htmlspecialchars('An error occurred. Please try again.');
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controllers/login_controller.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-login">Login</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
