<?php
session_start();
require_once __DIR__ . '/../../Controllers/DBController.php';

$db = new DBController();
$conn = $db->openConnection();

$user_id = $_SESSION['user']['id'] ?? 0;

$unread = 0;

if($user_id){
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM notification
        WHERE user_id = ? 
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unread = $stmt->get_result()->fetch_assoc()['total'];
}
$notifications = [];

if($user_id){

    // 🔔 نجيب آخر 5 notifications
    $stmt = $conn->prepare("
        SELECT message, created_at
        FROM notification
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $notifications[] = $row;
    }
}

$user = $_SESSION['user'] ?? null;
$canCreateTrip = in_array((int)($user['role_id'] ?? 0), [1, 2], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WanderPlan | Collaborative Travel Planner</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f8fafc;
    color:#0f172a;
}

/* NAVBAR */
nav{
    width:100%;
    padding:18px 8%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:white;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    position:sticky;
    top:0;
    z-index:100;
}

.logo{
    font-size:28px;
    font-weight:bold;
    color:#2563eb;
}

nav ul{
    list-style:none;
    display:flex;
    gap:30px;
}

nav ul li a{
    text-decoration:none;
    color:#0f172a;
    font-weight:600;
}

nav ul li a:hover{
    color:#2563eb;
}

/* RIGHT SIDE */
.right-side{
    display:flex;
    align-items:center;
    gap:12px;
}

.logout{
    padding:10px 18px;
    border:none;
    border-radius:8px;
    background:#e2e8f0;
    cursor:pointer;
    font-weight:bold;
}

/* NOTIFICATION */
.notify-btn{
    width:42px;
    height:42px;
    border:none;
    border-radius:50%;
    background:#f1f5f9;
    cursor:pointer;
    font-size:18px;
    position:relative;
}

.badge{
    position:absolute;
    top:-4px;
    right:-2px;
    background:red;
    color:white;
    width:18px;
    height:18px;
    border-radius:50%;
    font-size:11px;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* ACCOUNT BUTTON */
.account-btn{
    width:42px;
    height:42px;
    border-radius:50%;
    border:none;
    background:#2563eb;
    color:white;
    font-weight:bold;
    cursor:pointer;
    font-size:16px;
}

/* HERO */
.hero{
    padding:80px 8%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:40px;
    flex-wrap:wrap;
}

.hero-text{
    flex:1;
}

.hero-text h1{
    font-size:52px;
    margin-bottom:20px;
}

.hero-text span{
    color:#2563eb;
}

.hero-text p{
    color:#475569;
    line-height:1.7;
    margin-bottom:30px;
    font-size:18px;
}

.hero-buttons button{
    padding:14px 22px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    margin-right:10px;
}

.start{
    background:#2563eb;
    color:white;
}

.demo{
    background:#14b8a6;
    color:white;
}

.hero-image{
    flex:1;
}

.hero-image img{
    width:100%;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.15);
}

/* COUNTERS */
.stats{
    padding:50px 8%;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.stat-box{
    background:white;
    padding:30px;
    border-radius:18px;
    text-align:center;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}

.stat-box h2{
    color:#2563eb;
    font-size:36px;
}

.stat-box p{
    color:#64748b;
    margin-top:8px;
}

/* FEATURES */
.features{
    padding:70px 8%;
    text-align:center;
}

.features h2{
    font-size:38px;
    margin-bottom:45px;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
}

.card{
    background:white;
    padding:28px;
    border-radius:18px;
    box-shadow:0 8px 20px rgba(0,0,0,.07);
}

.card h3{
    color:#2563eb;
    margin-bottom:12px;
}

/* DESTINATIONS */
.destinations{
    padding:70px 8%;
}

.destinations h2{
    text-align:center;
    font-size:38px;
    margin-bottom:40px;
}

.dest-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
}

.dest-card{
    background:white;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
}

.dest-card img{
    width:100%;
    height:180px;
    object-fit:cover;
}

.dest-info{
    padding:18px;
}

.dest-info h3{
    color:#2563eb;
    margin-bottom:8px;
}

/* CTA */
.cta{
    margin:70px 8%;
    padding:60px;
    background:#2563eb;
    color:white;
    text-align:center;
    border-radius:20px;
}

.create-trip-btn{
    display:inline-block;
    margin-top:20px;
    padding:15px 28px;
    background:#f59e0b;
    color:white;
    text-decoration:none;
    border-radius:10px;
    font-weight:bold;
}

/* FOOTER */
footer{
    padding:25px;
    background:#0f172a;
    color:white;
    text-align:center;
    margin-top:60px;
}

/* MODAL */
.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:999;
}

.modal-box{
    background:white;
    width:400px;
    max-width:95%;
    padding:30px;
    border-radius:18px;
    position:relative;
}

.close{
    position:absolute;
    right:18px;
    top:14px;
    font-size:24px;
    cursor:pointer;
    color:red;
}

.modal-box h2{
    color:#2563eb;
    margin-bottom:20px;
}

.info{
    background:#f1f5f9;
    padding:12px;
    border-radius:10px;
    margin-bottom:12px;
}

/* UPLOAD */
.upload-box{
    margin-top:15px;
    background:#eff6ff;
    padding:15px;
    border-radius:12px;
}

.upload-box input{
    margin-top:10px;
    width:100%;
}

.upload-btn{
    margin-top:12px;
    width:100%;
    padding:12px;
    border:none;
    background:#16a34a;
    color:white;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}

.status{
    margin-top:12px;
    color:#16a34a;
    font-weight:bold;
}

@media(max-width:900px){
.hero-text h1{
    font-size:38px;
}

}




.hero-slider {
    width: 100%;
    height: 90vh;
    position: relative;
}

.slide {
    position: relative;
}

.slide .image {
    width: 100%;
    height: 90vh;
    object-fit: cover;
    filter: brightness(0.6);
}

.image-data {
    position: absolute;
    top: 50%;
    left: 8%;
    transform: translateY(-50%);
    color: white;
    max-width: 600px;
}

.image-data .text {
    font-size: 18px;
    color: #f1f5f9;
}

.image-data h2 {
    font-size: 48px;
    margin: 15px 0;
}

.button {
    display: inline-block;
    padding: 12px 20px;
    background: #2563eb;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

/* Swiper arrows */
.swiper-button-next,
.swiper-button-prev {
    color: white;
}

/* Pagination */
.swiper-pagination-bullet {
    background: white;
}
</style>


<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>

<body>

<!-- NAVBAR -->
<nav>

<div class="logo">WanderPlan</div>

<ul>
<li><a href="#">Home</a></li>
<li><a href="viewTrips.php">Upcoming Trips</a></li>
<li><a href="viewBookings.php">My Booking </a></li>
<!--  <li><a href="#">Features</a></li>  -->
</ul>

<div class="right-side">

<button class="notify-btn" onclick="openModal('notifyModal')">
🔔
<?php if($unread > 0){ ?>
<span class="badge"><?= $unread ?></span>
<?php } ?>
</button>

<button class="logout" onclick="openModal('logoutModal')">Logout</button>
<button class="account-btn" onclick="openModal('accountModal')">
👤
</button>

</div>

</nav>

<!-- HERO SLIDER -->
<section class="hero-slider swiper">

    <div class="content swiper-wrapper">

        <!-- SLIDE 1 -->
        <div class="slide swiper-slide">
            <img src="/Views/Assets/images/image1.jpg" class="image">

            <div class="image-data">
                <span class="text">Plan Your Dream Trips</span>
                <h2>
                    Explore the world <br>
                    with your friends
                </h2>
                <a href="/Views/User/Trips/createTrip.php" class="button">Start Planning</a>
            </div>
        </div>

        <!-- SLIDE 2 -->
        <div class="slide swiper-slide">
            <img src="/Views/Assets/images/image2.jpeg" class="image">

            <div class="image-data">
                <span class="text">Collaborative Planning</span>
                <h2>
                    Build itineraries <br>
                    together in real time
                </h2>
                <a href="viewTrips.php" class="button">View Trips</a>
            </div>
        </div>

        <!-- SLIDE 3 -->
        <div class="slide swiper-slide">
            <img src="/Views/Assets/images/image3.jpg" class="image">

            <div class="image-data">
                <span class="text">Discover Destinations</span>
                <h2>
                    From pyramids to beaches <br>
                    explore Egypt & beyond
                </h2>
                <a href="viewTrips.php" class="button" class="button">Explore</a>
            </div>
        </div>

    </div>

    <!-- Navigation -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>

</section>

<!-- STATIC COUNTERS -->
<section class="stats">

<div class="stat-box">
<h2>12K+</h2>
<p>Active Travelers</p>
</div>

<div class="stat-box">
<h2>3.5K+</h2>
<p>Trips Planned</p>
</div>

<div class="stat-box">
<h2>95%</h2>
<p>User Satisfaction</p>
</div>

<div class="stat-box">
<h2>80+</h2>
<p>Countries Explored</p>
</div>

</section>

<!-- FEATURES -->
<section class="features">

<h2>Why Choose WanderPlan?</h2>

<div class="cards">

<div class="card">
<h3>Collaborative Planning</h3>
<p>Invite friends and plan together in real time.</p>
</div>

<div class="card">
<h3>Smart Itinerary</h3>
<p>Organize every day of your trip easily.</p>
</div>

<div class="card">
<h3>Budget Split</h3>
<p>Track expenses instantly.</p>
</div>

<div class="card">
<h3>Booking Ready</h3>
<p>Upload passport/ID to enable booking trips.</p>
</div>

</div>
</section>

<!-- POPULAR DESTINATIONS -->
<section class="destinations">

<h2>Popular Destinations</h2>

<div class="dest-grid">

<!-- GIZA PYRAMIDS -->
<div class="dest-card">
<img src="/Views/Assets/images/pyramids.jpg" alt="Pyramids">
<div class="dest-info">
<h3>Giza Pyramids</h3>
<p>The iconic Great Pyramids and Sphinx — one of the Seven Wonders of the Ancient World.</p>
</div>
</div>

<!-- LUXOR -->
<div class="dest-card">
<img src="/Views/Assets/images/luxor.jpeg" alt="Luxor">
<div class="dest-info">
<h3>Luxor</h3>
<p>Ancient temples, tombs, and the Valley of the Kings — Egypt’s greatest open-air museum.</p>
</div>
</div>

<!-- SHARM EL SHEIKH -->
<div class="dest-card">
<img src="/Views/Assets/images/sharmelsheikh.jpeg" alt="SharmElSheikh">
<div class="dest-info">
<h3>Sharm El Sheikh</h3>
<p>Crystal Red Sea waters, coral reefs, and world-class diving and beaches.</p>
</div>
</div>

</div>

</section>

<!-- CTA -->
<section class="cta">

<h2>Your Next Adventure Starts Here</h2>
<p><?= $canCreateTrip ? 'Create your next group trip now.' : 'Explore available trips and manage your bookings.' ?></p>



<?php if($canCreateTrip){ ?>
<a href="/Views/User/Trips/createTrip.php" class="create-trip-btn">
Create New Trip
</a>
<?php } ?>

</a>

</section>

<!-- FOOTER -->
<footer>
© 2026 WanderPlan. All Rights Reserved.
</footer>



<!-- SWIPER JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    new Swiper(".hero-slider", {
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev"
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true
        }
    });
});
</script>

<!-- NOTIFICATION MODAL -->
<div class="modal" id="notifyModal">
<div class="modal-box">
<span class="close" onclick="closeModal('notifyModal')">&times;</span>
<h2>Notifications</h2>

<?php if(count($notifications) > 0){ ?>

    <?php foreach($notifications as $n){ ?>

        <div class="info">
            🔔 <?= htmlspecialchars($n['message']) ?><br>
            <small><?= $n['created_at'] ?></small>
        </div>

    <?php } ?>

<?php } else { ?>

    <div class="info">No notifications yet</div>

<?php } ?>

</div>
</div>

<!-- LOGOUT MODAL -->
<div class="modal" id="logoutModal">
<div class="modal-box">
<span class="close" onclick="closeModal('logoutModal')">&times;</span>
<h2>Logout</h2>
<p>Are you sure you want to logout?</p>
<br>
<button class="upload-btn" onclick="window.location.href='/Views/Auth/logout.php'">
Logout
</button>
</div>
</div>

<!-- ACCOUNT MODAL -->
<div class="modal" id="accountModal">
<div class="modal-box">

<span class="close" onclick="closeModal('accountModal')">&times;</span>

<h2>User Account</h2>




<div class="info">
<b>Email:</b>
<?= htmlspecialchars($user['email'] ?? '-') ?>
</div>

<div class="info">
<b>Member Since:</b>
<?= date("Y") ?>
</div>



<div class="upload-box">

<h3>Upload Travel Document</h3>

<p style="font-size:14px;color:#475569;">
Upload Passport or National ID to enable trip booking.
</p>

<input type="file" id="docFile" name="document" accept=".pdf,.jpg,.png,.jpeg">

<button class="upload-btn" onclick="uploadDocument()">
Upload Document
</button>

<div class="status" id="uploadStatus"></div>

</div>

</div>
</div>

<script>

function openModal(id){
    document.getElementById(id).style.display="flex";
}

function closeModal(id){
    document.getElementById(id).style.display="none";
}

window.onclick=function(e){
    if(e.target.classList.contains("modal")){
        e.target.style.display="none";
    }
}

function uploadDocument(){

    let file = document.getElementById("docFile").files[0];
    const statusElement = document.getElementById("uploadStatus");

    if(!file){
        alert("Please choose a document first.");
        return;
    }

    const formData = new FormData();
    formData.append("document", file);

    statusElement.innerHTML = "Uploading...";

    fetch("../../Controllers/DocumentController.php", {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        }
    })
    .then((response) => response.json())
    .then((result) => {
        if(result.success){
            statusElement.innerHTML =
            "✅ " + file.name + " uploaded and saved successfully.<br>Booking is now enabled.";
            return;
        }

        statusElement.innerHTML = "❌ " + (result.message || "Upload failed.");
    })
    .catch(() => {
        statusElement.innerHTML = "❌ Upload failed. Please try again.";
    });
}




</script>

</body>
</html>
