<?php
require_once '../../Controllers/DBController.php';

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'];
    $newPassword = $_POST['password'];

    if (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters.";
    }

    $db = new DBController();
    $db->openConnection();

    // check if email exists
    $stmt = $db->connection->prepare(
        "SELECT user_id FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($error === "" && $stmt->num_rows == 1) {

        // update password
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $db->connection->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        );

        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {

            header("Location: signin.php?reset=1");
            exit;

        } else {
            $error = "❌ Failed to update password.";
        }

    } elseif ($error === "") {
        $error = "❌ Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:linear-gradient(135deg,#eef2f7,#dbeafe);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.card{
    width:100%;
    max-width:450px;
    background:white;
    border-radius:22px;
    box-shadow:0 20px 40px rgba(0,0,0,.12);
    overflow:hidden;
}

.content{
    padding:35px;
}

h1{
    font-size:32px;
    margin-bottom:8px;
    color:#0f172a;
}

.sub{
    color:#64748b;
    margin-bottom:25px;
}

.error{
    background:#fee2e2;
    color:#b91c1c;
    padding:12px;
    border-radius:12px;
    margin-bottom:18px;
    font-size:14px;
}

.success{
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:12px;
    margin-bottom:18px;
    font-size:14px;
}

.input-group{
    position:relative;
    margin-bottom:18px;
}

.input-group i{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#64748b;
}

.input-group input{
    width:100%;
    padding:14px 14px 14px 45px;
    border:1px solid #cbd5e1;
    border-radius:14px;
    font-size:15px;
    outline:none;
    transition:.2s;
}

.input-group input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

button{
    width:100%;
    border:none;
    padding:14px;
    border-radius:16px;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(37,99,235,.25);
}

.footer{
    text-align:center;
    margin-top:25px;
    color:#475569;
    font-size:14px;
}

.footer a{
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}

.footer a:hover{
    text-decoration:underline;
}

</style>
</head>

<body>

<div class="card">
<div class="content">

<h1>Reset Password</h1>
<p class="sub">Create a new password for your account</p>

<!-- ERROR MESSAGE -->
<?php if($error != "") { ?>
    <div class="error"><?php echo $error; ?></div>
<?php } ?>

<!-- SUCCESS MESSAGE -->
<?php if($message != "") { ?>
    <div class="success"><?php echo $message; ?></div>
<?php } ?>

<form method="POST">

<div class="input-group">
<i class="fas fa-envelope"></i>
<input type="email" name="email" placeholder="Email Address" required>
</div>

<div class="input-group">
<i class="fas fa-lock"></i>
<input type="password" name="password" placeholder="New Password" minlength="8" required>
</div>

<button type="submit">Update Password</button>

</form>

<div class="footer">
Remember your password?
<a href="signin.php">Sign In</a>
</div>

</div>
</div>

</body>
</html>
