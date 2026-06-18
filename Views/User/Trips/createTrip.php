<?php
session_start();

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../Auth/signin.php");
    exit();
}

if (!in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 2], true)) {
    die("Unauthorized");
}

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Trip</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

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
}

.trip-container{
    width:100%;
    max-width:800px;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 20px 40px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-bottom:20px;
    color:#0f172a;
}

.success{
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    text-align:center;
}

.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
    margin-bottom:15px;
}

.input-group{
    display:flex;
    flex-direction:column;
}

.input-group label{
    margin-bottom:5px;
    font-size:14px;
    color:#475569;
}

.input-group input,
.input-group select,
.input-group textarea{
    padding:12px;
    border:1px solid #cbd5e1;
    border-radius:12px;
    font-size:14px;
    outline:none;
    transition:0.2s;
}

.input-group input:focus,
.input-group select:focus,
.input-group textarea:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,0.15);
}

textarea{
    min-height:120px;
    resize:vertical;
}

button{
    width:100%;
    margin-top:20px;
    padding:14px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(37,99,235,0.25);
}

.add-btn{
    margin-top:10px;
    background:#64748b;
}

.day-card{
    margin-top:14px;
    padding:14px;
    border:1px solid #cbd5e1;
    border-radius:12px;
}

.stops-wrapper{
    margin-top:10px;
}

.stop-row{
    display:flex;
    gap:10px;
    margin-bottom:8px;
}

.stop-row input{
    flex:1;
}

.remove-btn{
    border:none;
    border-radius:8px;
    
    background:#ef4444;
    color:#fff;
    cursor:pointer;
}

@media(max-width:768px){
    .grid-2{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<div class="trip-container">

<h2>Create New Trip</h2>

<?php if($success){ ?>
    <div class="success">✅ Trip created successfully!</div>
<?php } ?>

<form method="POST" action="../../../Controllers/TripController.php">

<!-- Name + Status -->
<div class="grid-2">

    <div class="input-group">
        <label>Trip Name</label>
        <input type="text" name="name" required>
    </div>

    <div class="input-group">
        <label>Status</label>
        <select name="status" required>
            <option value="planned">Planned</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
        </select>
    </div>

</div>

<!-- Dates -->
<div class="grid-2">

    <div class="input-group">
        <label>Start Date</label>
        <input type="date" name="start_date" required>
    </div>

    <div class="input-group">
        <label>End Date</label>
        <input type="date" name="end_date" required>
    </div>

</div>

<!-- Budget -->
<div class="input-group">
    <label>Budget</label>
    <input type="number" name="budget" min="0" required>
</div>

<!-- Description -->
<div class="input-group">
    <label>Description</label>
    <textarea name="description" required></textarea>
</div>

<!-- Itinerary -->
<h3 style="margin-top:20px; color:#0f172a;">Trip Itinerary</h3>

<div id="itinerary-wrapper"></div>

<button type="button" class="add-btn" onclick="addDay()">
    + Add Another Day
</button>

<button type="submit">Create Trip</button>

</form>

</div>

<script>
let dayIndex = 0;

function addStop(dayCard, dayIdx) {
    const stopsWrapper = dayCard.querySelector('.stops-wrapper');
    const stopRow = document.createElement('div');
    stopRow.classList.add('stop-row');
    stopRow.innerHTML = `
        <input type="text" name="itinerary[${dayIdx}][stops][]" placeholder="Stop name" required>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove This Stop</button>
    `;
    stopsWrapper.appendChild(stopRow);
}


function addDay() {
    const wrapper = document.getElementById('itinerary-wrapper');
    const currentDay = dayIndex + 1;
    const card = document.createElement('div');
    card.classList.add('day-card');

    card.innerHTML = `
        <h4 style="margin-bottom:10px;color:#0f172a;">Day ${currentDay}</h4>
        <div class="grid-2">
            <div class="input-group">
                <label>Date</label>
                <input type="date" name="itinerary[${dayIndex}][date]" required>
            </div>
            <div class="input-group">
                <label>Destination / Day Title</label>
                <input type="text" name="itinerary[${dayIndex}][title]" placeholder="e.g. Cairo City Tour" required>
            </div>
        </div>
        <label style="display:block; margin-top:10px; color:#475569;">Stops</label>
        <div class="stops-wrapper"></div>
        <button type="button" class="add-btn" onclick="addStop(this.parentElement, ${dayIndex})">+ Add Stop</button>
    `;

    wrapper.appendChild(card);
    addStop(card, dayIndex);
    dayIndex++;
}

addDay();
</script>

</body>
</html>
