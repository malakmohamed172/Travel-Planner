<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Upload Document</title>

<style>
body{
    font-family:Arial;
    background:#eef2f7;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box{
    background:white;
    padding:30px;
    border-radius:15px;
    width:400px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    color:#2563eb;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:10px;
    margin-top:10px;
}

button{
    width:100%;
    margin-top:15px;
    padding:12px;
    background:#16a34a;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:#15803d;
}
</style>
</head>

<body>

<div class="box">

<h2>Upload Document</h2>

<form action="../../../Controllers/DocumentController.php" method="POST" enctype="multipart/form-data">

<input type="file" name="document" required>

<button type="submit">Upload</button>

</form>

</div>

</body>
</html>