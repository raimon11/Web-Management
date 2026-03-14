<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coziest Login</title>
    <link rel="stylesheet" href="./styles/index.css">
    <style>
        .error-message{
            color:red;
            font-size:14px;
            margin-top:5px;
        }
        .left, .right { flex: 1; padding: 40px; }
        .right { display: flex; flex-direction: column; justify-content: center; }
        form { display: flex; flex-direction: column; }
        .input-group { margin-bottom: 15px; }
        label { margin-bottom: 5px; font-weight: bold; }
        input { padding: 10px; font-size: 16px; }
        button { padding: 10px; font-size: 16px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        h2 { margin-bottom: 20px; }
        .register-link { margin-top: 10px; font-size: 14px; }
        .register-link a { color: #4CAF50; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="left">
            <!-- Add images or branding here -->
        </div>

        <div class="right">
            <h2>Coziest Login</h2>
            <form method="POST" action="authenticate.php">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>

                    <?php if(isset($_GET['login_error'])) { ?>
                        <div class="error-message">Incorrect email or password</div>
                    <?php } ?>
                </div>

                <button type="submit">Login</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>
</html>