<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coziest Register</title>
<style>
    /* General Reset */
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #dfa8a2, #c22b1b);
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 900px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .left {
        width: 50%;
        background-image: url("./assets/logo.png");
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        margin: 50px;
        position: relative;
    }

    .right {
        flex: 1;
        padding: 40px 30px;
    }

    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    form {
        display: flex;
        flex-direction: column;
    }

    .input-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: #555;
    }

    input {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 8px;
        transition: all 0.2s;
    }

    input:focus {
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.4);
        outline: none;
    }

    button {
        padding: 12px;
        background-color: #4CAF50;
        color: white;
        font-size: 16px;
        font-weight: bold;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }

    button:hover {
        background-color: #45a049;
    }

    .login-link, .success-message, .error-message {
        text-align: center;
        margin-top: 12px;
        font-size: 14px;
    }

    .login-link a, .success-message a {
        color: #4CAF50;
        text-decoration: none;
        font-weight: bold;
    }

    .login-link a:hover, .success-message a:hover {
        text-decoration: underline;
    }

    .error-message {
        color: #e74c3c;
    }

    .success-message {
        color: #2ecc71;
    }

    /* Responsive */
    @media (min-width: 768px) {
        .left { display: block; }
    }

    @media (max-width: 768px) {
        .container {
            flex-direction: column;
            max-width: 400px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="right">
        <h2>Create Account</h2>
        <form method="POST" action="register_action.php">
            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>

            <div class="input-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name">
            </div>

            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-group">
                <label>Delivery Address</label>
                <input type="text" name="delivery_address" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Register</button>

            <?php if(isset($_GET['success'])) { ?>
                <div class="success-message">Registration successful! <a href="index.php">Login here</a>.</div>
            <?php } ?>

            <?php if(isset($_GET['error'])) { ?>
                <div class="error-message">Email already exists or registration failed.</div>
            <?php } ?>
        </form>

        <div class="login-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</div>
</body>
</html>