<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coziest</title>
    <link rel="stylesheet" href="./styles/index.css">

    <style>
        .error-message{
            color:red;
            font-size:14px;
            margin-top:5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left"></div>

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

                    <?php if(isset($_GET['error'])) { ?>
                        <div class="error-message">Incorrect email or password</div>
                    <?php } ?>
                </div>

                <button type="submit">Login</button>

            </form>
        </div>
    </div>
</body>
</html>