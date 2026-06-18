<?php
require_once '../../Controllers/AuthController.php';
require_once '../../Models/users.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user = new User();
    $user->name = $_POST['fullname'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];

    $auth = new AuthController();

  $result = $auth->register($user);

if ($result === "success") {
    header("Location: signin.php?success=1");
    exit;
} elseif ($result === "exists") {
    $message = "⚠️ Email already exists.";
} elseif ($result === "weak_password") {
    $message = "Password must be at least 8 characters.";
} else {
    $message = "❌ Something went wrong.";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Create Account | Auth Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f6f9fc 0%, #edf2f7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-card {
            width: 100%;
            max-width: 460px;
            background: rgba(255,255,255,0.97);
            border-radius: 2rem;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.25), 0 8px 18px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .form-panel {
            padding: 2rem 1.8rem;
            background: white;
        }

        .form-title {
    font-size: 1.85rem;
    font-weight: 700;
    background: linear-gradient(135deg, #1a2a3f, #1e3a5f);

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    background-clip: text; /* for future compatibility */

    margin-bottom: 0.5rem;
}

        .sub-text {
            color: #5c6f87;
            font-size: 0.9rem;
            margin-bottom: 1.8rem;
            border-left: 3px solid #3b82f6;
            padding-left: 0.75rem;
        }

        .message-toast {
            padding: 0.7rem 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            background: #b91c1c;
            color: white;
        }

        .input-group {
            margin-bottom: 1.4rem;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8e9dbb;
        }

        .input-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 1.2rem;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: 0.2s;
        }

        .input-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }

        button {
            width: 100%;
            border: none;
            padding: 14px;
            border-radius: 16px;
            background: linear-gradient(135deg,#1d4ed8,#2563eb);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            transform: translateY(-1px);
        }

        .toggle-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .toggle-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .toggle-link a:hover {
            text-decoration: underline;
        }

        footer {
            font-size: 0.7rem;
            text-align: center;
            padding: 1rem;
            color: #6c7a91;
            border-top: 1px solid #eef2f8;
        }

        @media (max-width: 480px) {
            .form-panel {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

<div class="auth-card">
    <div class="form-panel">

        <div class="form-title">Create account</div>
        <div class="sub-text">Join us today</div>

        <?php if (!empty($message)) : ?>
            <div class="message-toast"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fullname" placeholder="Full Name" required>
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" minlength="8" required>
            </div>

            <button type="submit">Register</button>

        </form>

        <div class="toggle-link">
            Already have an account? <a href="signin.php">Sign in</a>
        </div>

    </div>

    <footer>Secure authentication demo</footer>
</div>

</body>
</html>
