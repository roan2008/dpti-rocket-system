<?php include '../includes/header.php'; ?>

<div class="container-sm">
<div class="login-container">
    <div class="card-modern">
        <div class="card-content">
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
                <div class="form-group-modern">
                    <label for="username" class="form-label-modern">Username:</label>
                    <input type="text" id="username" name="username" class="form-input-modern" required>
                </div>
                
                <div class="form-group-modern">
                    <label for="password" class="form-label-modern">Password:</label>
                    <input type="password" id="password" name="password" class="form-input-modern" required>
                </div>
                
                <div class="form-group-modern">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<!-- End container -->

<?php include '../includes/footer.php'; ?>
