<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #f8d7da !important;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Login
                    </div>
                    <div class="card-body">
                        <form action="login.php" method="POST">
                            <div class="form-group">
                                <label for="email">Email address</label>
                                <input type="email" class="form-control <?php echo isset($_SESSION['login_error']) ? 'is-invalid' : ''; ?>" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control <?php echo isset($_SESSION['login_error']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                            </div>
                            <?php if (isset($_SESSION['login_error'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $_SESSION['login_error']; ?>
                                </div>
                                <?php unset($_SESSION['login_error']); ?>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
