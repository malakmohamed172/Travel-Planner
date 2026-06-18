<?php
require_once __DIR__ . '/../../Controllers/DBController.php';
require_once __DIR__ . '/../../Models/EmergencyContact.php';

$db = new DBController();
$conn = $db->openConnection();

$em = new EmergencyContact($conn);

$trip_id = (int)($_GET['trip_id'] ?? 0);

$em->trip_id = $trip_id;
$contacts = $em->getByTrip();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Emergency Contacts</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    min-height:100vh;
    padding:40px 20px;
    color:#0f172a;
}

.container{
    max-width:850px;
    margin:auto;
}

.page-header{
    margin-bottom:28px;
}

.page-header h1{
    font-size:34px;
    font-weight:700;
    margin-bottom:8px;
    color:#0f172a;
}

.page-header p{
    color:#64748b;
    font-size:15px;
}

.card{
    background:white;
    border-radius:24px;
    padding:28px;
    margin-bottom:24px;
    box-shadow:0 10px 30px rgba(15,23,42,.08);
    border:1px solid #e2e8f0;
}

.card h2{
    font-size:24px;
    margin-bottom:22px;
    color:#0f172a;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:16px;
}

.input-group{
    display:flex;
    flex-direction:column;
}

.input-group label{
    font-size:14px;
    font-weight:600;
    margin-bottom:8px;
    color:#334155;
}

.input-group input{
    padding:14px;
    border-radius:14px;
    border:1px solid #cbd5e1;
    background:#f8fafc;
    outline:none;
    transition:.2s;
    font-size:14px;
}

.input-group input:focus{
    border-color:#2563eb;
    background:white;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}

.submit-btn{
    width:100%;
    margin-top:22px;
    border:none;
    padding:14px;
    border-radius:16px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}

.submit-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 24px rgba(37,99,235,.25);
}

.contacts-grid{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.contact-card{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:20px;
    padding:22px;
    transition:.2s;
}

.contact-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(0,0,0,.05);
}

.contact-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}

.contact-name{
    font-size:20px;
    font-weight:700;
    color:#0f172a;
}

.badge{
    background:#dbeafe;
    color:#1d4ed8;
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
}

.contact-info{
    margin-top:18px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:14px;
}

.info-box{
    background:white;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding:14px;
}

.info-label{
    font-size:12px;
    color:#64748b;
    margin-bottom:6px;
}

.info-value{
    font-size:15px;
    font-weight:600;
    color:#0f172a;
}

.empty{
    text-align:center;
    padding:40px;
    color:#64748b;
    font-size:16px;
}

.footer{
    text-align:center;
    margin-top:30px;
    color:#94a3b8;
    font-size:13px;
}

@media(max-width:768px){

    body{
        padding:20px 14px;
    }

    .card{
        padding:20px;
    }

    .page-header h1{
        font-size:28px;
    }

}

</style>
</head>

<body>

<div class="container">

    <div class="page-header">
        <h1>🚨 Emergency Contacts</h1>
        
    </div>

    <!-- ADD CONTACT -->

    <div class="card">

        <h2>➕ Add Emergency Contact</h2>

        <form method="POST" action="../../Controllers/EmergencyController.php">

            <input type="hidden" name="trip_id" value="<?= $trip_id ?>">

            <div class="form-grid">

                <div class="input-group">
                    <label>Contact Name</label>
                    <input 
                    type="text" 
                    name="name" 
                    placeholder="Enter contact name" 
                    required>
                </div>

                <div class="input-group">
                    <label>Phone Number</label>
                    <input 
                    type="text" 
                    name="phone" 
                    placeholder="Enter phone number" 
                    required>
                </div>

            </div>

            <button class="submit-btn" name="create">
                Save Contact
            </button>

        </form>

    </div>

    <!-- CONTACT LIST -->

    <div class="card">

        <h2>📋 Saved Contacts</h2>

        <div class="contacts-grid">

        <?php if($contacts && $contacts->num_rows > 0){ ?>

            <?php while($row = $contacts->fetch_assoc()){ ?>

            <div class="contact-card">

                <div class="contact-top">

                    <div class="contact-name">
                        👤 <?= htmlspecialchars($row['name']) ?>
                    </div>

                    <div class="badge">
                        Emergency Contact
                    </div>

                </div>

                <div class="contact-info">

                    <div class="info-box">

                        <div class="info-label">
                            Contact Name
                        </div>

                        <div class="info-value">
                            <?= htmlspecialchars($row['name']) ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Phone Number
                        </div>

                        <div class="info-value">
                            📞 <?= htmlspecialchars($row['phone']) ?>
                        </div>

                    </div>

                </div>

            </div>

            <?php } ?>

        <?php } else { ?>

            <div class="empty">
                No emergency contacts added yet.
            </div>

        <?php } ?>

        </div>

    </div>

    <div class="footer">
        Travel Planner • Emergency Contact Management
    </div>

</div>

</body>
</html>