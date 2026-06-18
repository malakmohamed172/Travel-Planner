<?php
session_start();

require_once '../../Models/users.php';
require_once '../../Controllers/AuthController.php';

$error = "";
$message = "";

/* =========================
   SUCCESS MESSAGES (GET)
========================= */
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "✅ You have registered successfully. Please sign in.";
}

if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    $message = "🔐 Password has been reset successfully. Please sign in.";
}

/* =========================
   LOGIN HANDLER
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $user = new User();
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];

        $auth = new AuthController();

      /*  if ($auth->signin($user)) {

            header("Location: ../User/homepage.php");
            exit();


        

        } else {
            $error = "Invalid Email or Password";
        }

    } else {
        $error = "Please fill in all fields.";
    }  */


          if ($auth->signin($user)) {

            // 🔥 ROLE CHECK AFTER LOGIN SUCCESS
            if ($_SESSION['user']['role_id'] == 1) {
                header("Location: ../Admin/adminDashboard.php");
                exit();
            } else {
                header("Location: ../User/homepage.php");
                exit();
            }

        } else {
            $error = "Invalid Email or Password";
        }

    } else {
        $error = "Please fill in all fields.";
    }


    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In</title>

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

.row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    font-size:14px;
}

.row label{
    display:flex;
    gap:7px;
    align-items:center;
    color:#334155;
}

.row a{
    text-decoration:none;
    color:#2563eb;
}

.row a:hover{
    text-decoration:underline;
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

.divider{
    text-align:center;
    margin:22px 0;
    color:#94a3b8;
    position:relative;
}

.divider:before,
.divider:after{
    content:"";
    position:absolute;
    top:50%;
    width:38%;
    height:1px;
    background:#e2e8f0;
}

.divider:before{ left:0; }
.divider:after{ right:0; }

.social{
    display:flex;
    justify-content:center;
    gap:15px;
}

.social a{
    width:46px;
    height:46px;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    color:#0f172a;
    text-decoration:none;
    font-size:18px;
    transition:.2s;
}

.social a:hover{
    transform:scale(1.08);
    background:#eff6ff;
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

<h1>Welcome Back</h1>
<p class="sub">Sign in to your account</p>

<!-- SUCCESS MESSAGE -->
<?php if($message != "") { ?>
    <div class="success"><?php echo $message; ?></div>
<?php } ?>

<!-- ERROR MESSAGE -->
<?php if($error != "") { ?>
    <div class="error"><?php echo $error; ?></div>
<?php } ?>

<form method="POST">

<div class="input-group">
<i class="fas fa-envelope"></i>
<input type="email" name="email" placeholder="Email Address" required>
</div>

<div class="input-group">
<i class="fas fa-lock"></i>
<input type="password" name="password" placeholder="Password" required>
</div>

<div class="row">


<a href="forgotPassword.php">Forgot Password?</a>
</div>

<button type="submit">Sign In</button>

</form>

<div class="divider">or</div>

<div class="social">
<a href="#"><i class="fab fa-google"></i></a>
<a href="#"><i class="fab fa-github"></i></a>
<a href="#"><i class="fab fa-facebook-f"></i></a>
</div>

<div class="footer">
Don't have an account?
<a href="register.php?success=1">Create one</a>
</div>

</div>
</div>

</body>
</html>