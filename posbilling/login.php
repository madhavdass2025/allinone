<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    $conn = get_db_conn();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Username not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pharmacy ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .login-card { max-width: 400px; margin: 100px auto; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .login-card .card-header { background: #0d6efd; color: white; border-radius: 15px 15px 0 0; text-align: center; padding: 2rem; }
        .login-card .card-body { padding: 2rem; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-header">
            <h3><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy ERP</h3>
            <p class="mb-0">Please login to your account</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 mt-2">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
