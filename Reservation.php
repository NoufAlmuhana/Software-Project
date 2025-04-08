<?php
session_start();
$host = 'localhost';
$username = 'root'; 
$password = 'root'; 
$dbname = 'wisaldb';

$conn = new mysqli($host, $username, $password, $dbname, 8889);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['userID'])) {
    die("Access denied. Please log in first.");
}

$userID = $_SESSION['userID'];

// Save or Update Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $numOfPeople = $_POST['numPeople'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = 'Pending';
    $id = $_POST['id'] ?? null;

    $startTimeRaw = explode('-', $time)[0];
    $meridiem = trim(substr($time, -2));
    $startTimeFormatted = date('H:i:s', strtotime("$startTimeRaw $meridiem"));
    $datetime = date('Y-m-d H:i:s', strtotime("$date $startTimeFormatted"));

    if (strtotime($datetime) < strtotime(date('Y-m-d H:i:s'))) {
        echo json_encode(['success' => false, 'message' => 'You cannot reserve a past date or time.']);
        exit;
    }

    $checkSQL = $id
        ? "SELECT COUNT(*) as count FROM reservation WHERE date = ? AND reservationID != ?"
        : "SELECT COUNT(*) as count FROM reservation WHERE date = ?";
    $checkStmt = $conn->prepare($checkSQL);
    $id ? $checkStmt->bind_param("si", $datetime, $id) : $checkStmt->bind_param("s", $datetime);
    $checkStmt->execute();
    $res = $checkStmt->get_result()->fetch_assoc();
    if ($res['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'This date and time are already reserved.']);
        exit;
    }

    // Get available table
    $tableStmt = $conn->prepare("SELECT tableID FROM tables WHERE isAvailable = 1 AND capacity >= ? ORDER BY capacity ASC LIMIT 1");
    $tableStmt->bind_param("i", $numOfPeople);
    $tableStmt->execute();
    $tableResult = $tableStmt->get_result();
    if ($tableResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No available table fits the number of guests.']);
        exit;
    }
    $tableID = $tableResult->fetch_assoc()['tableID'];

    // If updating, free old table
    if ($id) {
        $oldTable = $conn->query("SELECT tableID FROM reservation WHERE reservationID = $id AND userID = $userID")->fetch_assoc()['tableID'] ?? null;
        if ($oldTable) {
            $conn->query("UPDATE tables SET status='Available', isAvailable=1 WHERE tableID = $oldTable");
        }

        $stmt = $conn->prepare("UPDATE reservation SET numOfPeople=?, date=?, status=?, tableID=?, userID=? WHERE reservationID=? AND userID=?");
        $stmt->bind_param("issiiii", $numOfPeople, $datetime, $status, $tableID, $userID, $id, $userID);
    } else {
        $stmt = $conn->prepare("INSERT INTO reservation (numOfPeople, date, status, tableID, userID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $numOfPeople, $datetime, $status, $tableID, $userID);
    }

    if ($stmt->execute()) {
        $conn->query("UPDATE tables SET status='Reserved', isAvailable=0 WHERE tableID = $tableID");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// Delete Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $tableRow = $conn->query("SELECT tableID FROM reservation WHERE reservationID = $id AND userID = $userID")->fetch_assoc();
    if ($tableRow) {
        $tableID = $tableRow['tableID'];
        $stmt = $conn->prepare("DELETE FROM reservation WHERE reservationID = ? AND userID = ?");
        $stmt->bind_param("ii", $id, $userID);
        if ($stmt->execute()) {
            $conn->query("UPDATE tables SET status='Available', isAvailable=1 WHERE tableID = $tableID");
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Fetch Reservations
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $reservations = [];
    $sql = "SELECT reservationID, numOfPeople, DATE_FORMAT(date, '%Y-%m-%d') as resDate, DATE_FORMAT(date, '%h-%i %p') as resTime FROM reservation WHERE userID = ? ORDER BY date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    echo json_encode($reservations);
    exit;
}

// Search Available Tables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $date = $_POST['searchDate'];
    $time = $_POST['searchTime'];

    $startTimeRaw = explode('-', $time)[0];
    $meridiem = trim(substr($time, -2));
    $startTimeFormatted = date('H:i:s', strtotime("$startTimeRaw $meridiem"));
    $datetime = date('Y-m-d H:i:s', strtotime("$date $startTimeFormatted"));

    $sql = "SELECT * FROM tables WHERE isAvailable = 1 AND tableID NOT IN (
        SELECT tableID FROM reservation WHERE date = ?
    )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $datetime);
    $stmt->execute();
    $result = $stmt->get_result();

    $availableTables = [];
    while ($row = $result->fetch_assoc()) {
        $availableTables[] = $row;
    }
    echo json_encode($availableTables);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Reservation System</title>

    <style>
.header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #493E32;
            padding: 15px 30px;
            color: white;
        }
        .header .logo{
            margin-left: 8%;
        }
        .text {
            color: #C5A478;
            margin-left: 5px;
        }
        .nav-menu a {
            color: #ccc;
            text-decoration: none;
            margin: 0 15px;
            position: relative;
        }
        .nav-menu a.active,
        .nav-menu a:hover {
            color: white;
            font-weight: bold;
        }
        .nav-menu a.active::after,
        .nav-menu a:hover::after {
            content: "";
            display: block;
            width: 100%;
            height: 2px;
            background-color: #C5A478;
            position: absolute;
            bottom: -5px;
            left: 0;
        }
        .icons{
            width: 20%;
        }
        .icons i {
            margin-left: 15px;
            font-size: 18px;
            cursor: pointer;
        }
        .icons a{
            padding: 10px;
        }
        .icons svg{
            width: 10%;
        }
        .icons svg path{
            fill: white
        }



        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 20px;
            color: #000000;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #3C3B2A;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #3C3B2A;
        }
        input[type="date"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0 20px;
            border: 1px solid #3C3B2A;
            border-radius: 4px;
            cursor: pointer;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #005B5C;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #004b4c;
        }
        .message {
            color: red;
            margin: 10px 0;
        }
        .time-slot {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 10px;
        }
        .time-slot button {
            flex: 1 1 calc(33% - 10px);
            margin: 5px;
            background-color: #e7e7e7;
            color: #3C3B2A;
            border: 1px solid #3C3B2A;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .time-slot button:hover,
        .time-slot button.active {
            background-color: #005B5C;
            color: white;
        }
        .reservation-list {
            margin-top: 20px;
        }





footer {
      background-color: #382904;
      color: white;
      padding: 40px 20px;
      text-align: center;
  }
  .footer-container {
      display: flex;
      justify-content: space-around;
      flex-wrap: wrap;
  }
  .footer-section {
      margin: 20px;
      flex: 1;
      min-width: 200px;
  }
  .footer-section h3 {
      border-bottom: 2px solid #f9d7a1;
      padding-bottom: 10px;
  }
  .footer-section a {
      color: white;
      text-decoration: none;
      display: block;
      margin: 5px 0;
      transition: color 0.3s;
  }
  .footer-section a:hover {
      color: #f3ddb9;
  }
  .social-icons {
      margin: 10px 0;
  }
  .social-icons a {
      margin: 0 10px;
  }
  .contact-info {
      font-size: 0.9em;
  }
  .copyright {
      margin-top: 20px;
      font-size: 0.8em;
  }

    </style>
</head>


<body>
<header class="header">
        <div class="logo">
                <img src="images/logo copy.jpg" alt="logo" style="width: 100px;">
        </div>
        <nav class="nav-menu">
            <a href="index.html">Home</a>
            <a href="Gameorder.html">Game And Ordering</a>
            <a href="cafefood.html" class="active">Cafe And Food</a>
            <a href="Loyalty.html">Loyalty Program</a>
        </nav>
        <div class="icons">
            <a href="Main.html" onclick="confirmLogout()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="30" height="30" fill="currentColor">
                    <path d="M497.9 273L353.9 417c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9L434.1 272 320 159.9c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0L497.9 239c9.4 9.4 9.4 24.6 0 34zM160 32h64c13.3 0 24-10.7 24-24S237.3 0 224 0h-64C71.6 0 0 71.6 0 160v192c0 88.4 71.6 160 160 160h64c13.3 0 24-10.7 24-24s-10.7-24-24-24h-64c-61.9 0-112-50.1-112-112V160c0-61.9 50.1-112 112-112z"/>
                </svg>
            </a>
            
            <script>
                function confirmLogout() {
                    let confirmAction = confirm("Are you sure you want to log out?");
                    if (confirmAction) {
                        window.location.href = "Main.html"; 
                    }
                }
            </script>
            <a href="cart.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="width: 11%;"><path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg></a>
            </div>

    </header>

<div class="container">

  <!-- 🔍 Search -->
  <div class="search-section">
    <form id="searchForm">
      <label>Search Available Tables:</label>
      <input type="date" id="searchDate" required />
      <select id="searchTime" required>
        <option value="">Select Time</option>
        <option>3-4 PM</option><option>4-5 PM</option><option>5-6 PM</option>
        <option>6-7 PM</option><option>7-8 PM</option><option>8-9 PM</option>
        <option>9-10 PM</option><option>10-11 PM</option>
      </select>
      <button type="submit">Search</button>
    </form>
    <div id="searchResults" class="search-result"></div>
  </div>

  <!-- 📋 Reservation Form -->
  <h1>Table Reservation</h1>
  <form id="reservationForm">
    <input type="hidden" id="resID" />
    <label for="numPeople">Number of Guests:</label>
    <input type="number" id="numPeople" required min="1" max="10" />
    <label for="date">Select Date:</label>
    <input type="date" id="date" required />
    <label for="time">Select Time:</label>
    <div class="time-slot">
      <button type="button" onclick="selectTime(this)">3-4 PM</button>
      <button type="button" onclick="selectTime(this)">4-5 PM</button>
      <button type="button" onclick="selectTime(this)">5-6 PM</button>
      <button type="button" onclick="selectTime(this)">6-7 PM</button>
      <button type="button" onclick="selectTime(this)">7-8 PM</button>
      <button type="button" onclick="selectTime(this)">8-9 PM</button>
      <button type="button" onclick="selectTime(this)">9-10 PM</button>
      <button type="button" onclick="selectTime(this)">10-11 PM</button>
    </div>
    <input type="hidden" id="time" />
    <button type="submit">Confirm Reservation</button>
    <div class="message" id="errorMessage"></div>
  </form>

  <!-- 📄 Reservations -->
  <div class="reservation-list" id="reservationList">
    <h3>Current Reservations:</h3>
  </div>
</div>
<footer>
                        <div class="footer-container">
                                <div class="footer-section">
                                        <h3>Quick Links</h3>
                                        <a href="index.html">Home</a>
                                        <a href="Gameorder.html">Game And Ordering</a>
                                        <a href="cafefood.html">Cafe And food</a>
                                        <a href="Regster.html">ٍRegister Page</a>
                                        <a href="Loyalty.html">Loyalty</a>
                                </div>

                                <div class="footer-section">
                                        <h3>Follow Us</h3>
                                        <div class="social-icons">
                                                <a href="#">Facebook</a>
                                                <a href="#">Twitter</a>
                                                <a href="#">Instagram</a>
                                                <a href="#">LinkedIn</a>
                                        </div>
                                </div>
                                <div class="footer-section">
                                        <h3>Contact Info</h3>
                                        <p class="contact-info">Phone: 123-456-7890</p>
                                        <p class="contact-info">Email: info@example.com</p>
                                        <p class="contact-info">Address: 123 Main St, City, Country</p>
                                </div>
                        </div>
                        <div class="copyright">
                                <p>©️ 2023 All Rights Reserved.</p>
                        </div>
                </footer>

<script>
function selectTime(button) {
  document.querySelectorAll('.time-slot button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
  document.getElementById('time').value = button.textContent;
}

function getSelectedTime() {
  const btn = document.querySelector('.time-slot button.active');
  return btn ? btn.textContent : null;
}

function showMessage(msg, color = 'red') {
  const msgBox = document.getElementById('errorMessage');
  msgBox.textContent = msg;
  msgBox.style.color = color;
  setTimeout(() => msgBox.textContent = '', 3000);
}

document.getElementById('reservationForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new URLSearchParams();
  formData.append('numPeople', document.getElementById('numPeople').value);
  formData.append('date', document.getElementById('date').value);
  formData.append('time', getSelectedTime());
  formData.append('action', 'save');
  const id = document.getElementById('resID').value;
  if (id) formData.append('id', id);

  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showMessage('Reservation saved!', 'green');
      document.getElementById('reservationForm').reset();
      document.getElementById('resID').value = '';
      document.querySelectorAll('.time-slot button').forEach(btn => btn.classList.remove('active'));
      fetchReservations();
    } else {
      showMessage(data.message || 'Something went wrong.');
    }
  });
});

function fetchReservations() {
  fetch(window.location.href + '?action=fetch')
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('reservationList');
      list.innerHTML = '<h3>Current Reservations:</h3>';
      data.forEach(res => {
        list.innerHTML += `
          <div>
            <strong>${res.numOfPeople} guests</strong> on ${res.resDate} at ${res.resTime}
            <button onclick="editReservation('${res.reservationID}', '${res.numOfPeople}', '${res.resDate}', '${res.resTime}')">Edit</button>
            <button onclick="deleteReservation('${res.reservationID}')">Delete</button>
          </div>`;
      });
    });
}

function editReservation(id, people, date, time) {
  document.getElementById('resID').value = id;
  document.getElementById('numPeople').value = people;
  document.getElementById('date').value = date;
  document.querySelectorAll('.time-slot button').forEach(btn => {
    btn.classList.remove('active');
    if (btn.textContent === time) {
      btn.classList.add('active');
      document.getElementById('time').value = time;
    }
  });
}

function deleteReservation(id) {
  if (!confirm('Delete this reservation?')) return;
  const formData = new URLSearchParams();
  formData.append('id', id);
  formData.append('action', 'delete');

  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showMessage('Deleted!', 'green');
      fetchReservations();
    } else {
      showMessage(data.message || 'Failed to delete.');
    }
  });
}

// 🔍 Search
document.getElementById('searchForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new URLSearchParams();
  formData.append('action', 'search');
  formData.append('searchDate', document.getElementById('searchDate').value);
  formData.append('searchTime', document.getElementById('searchTime').value);

  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    const resultDiv = document.getElementById('searchResults');
    if (data.length === 0) {
      resultDiv.innerHTML = "<p>No available tables found.</p>";
    } else {
      resultDiv.innerHTML = "<strong>Available Tables:</strong><ul>" +
        data.map(t => `<li>Table #${t.tableID} (Seats ${t.capacity})</li>`).join('') + "</ul>";
    }
  });
});

window.onload = fetchReservations;
</script>
</body>
</html>
