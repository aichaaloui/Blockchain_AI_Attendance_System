<?php
//require 'database_connection.php'; // Ensure you include your database connection file

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set content type to JSON
    $attendanceData = json_decode(file_get_contents("php://input"), true);
    $response = [];

    if ($attendanceData) {
        try {
            $sql = "INSERT INTO tblattendance (studentRegistrationNumber, course, unit, attendanceStatus, dateMarked)  
                VALUES (:studentID, :course, :unit, :attendanceStatus, :date)";
            $stmt = $pdo->prepare($sql);

            foreach ($attendanceData as $data) {
                $studentID = $data['studentID'];
                $attendanceStatus = $data['attendanceStatus'];
                $course = $data['course'];
                $unit = $data['unit'];
                $date = date("Y-m-d");

                // Bind parameters and execute for each attendance record
                $stmt->execute([
                    ':studentID' => $studentID,
                    ':course' => $course,
                    ':unit' => $unit,
                    ':attendanceStatus' => $attendanceStatus,
                    ':date' => $date
                ]);
            }

            $response['message'] = "Attendance recorded successfully for all entries.";
            http_response_code(200); // Set HTTP response code to 200 OK
        } catch (PDOException $e) {
            $response['message'] = "Error inserting attendance data: " . $e->getMessage();
            http_response_code(500); // Set HTTP response code to 500 Internal Server Error
        }
    } else {
        $response['message'] = "No attendance data received.";
        http_response_code(400); // Set HTTP response code to 400 Bad Request
    }

    echo json_encode($response); // Return JSON response
    exit; // Ensure no further output is sent
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Lecture Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <script defer src="resources/assets/javascript/face_logics/face-api.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/web3/dist/web3.min.js"></script>
</head>

<body>

    <?php include 'includes/topbar.php'; ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">
            <div id="messageDiv" class="messageDiv" style="display:none;"></div>
            <p style="font:80px; font-weight:400; color:blue; text-align:center; padding-top:2px;">Please select course, unit, and venue first. Before Launching Facial Recognition</p>
            <form class="lecture-options" id="selectForm">
                <select required name="course" id="courseSelect" onChange="updateTable()">
                    <option value="" selected>Select Course</option>
                    <?php
                    $courseNames = getCourseNames();
                    foreach ($courseNames as $course) {
                        echo '<option value="' . $course["courseCode"] . '">' . $course["name"] . '</option>';
                    }
                    ?>
                </select>

                <select required name="unit" id="unitSelect" onChange="updateTable()">
                    <option value="" selected>Select Unit</option>
                    <?php
                    $unitNames = getUnitNames();
                    foreach ($unitNames as $unit) {
                        echo '<option value="' . $unit["unitCode"] . '">' . $unit["name"] . '</option>';
                    }
                    ?>
                </select>

                <select required name="venue" id="venueSelect" onChange="updateTable()">
                    <option value="" selected>Select Venue</option>
                    <?php
                    $venueNames = getVenueNames();
                    foreach ($venueNames as $venue) {
                        echo '<option value="' . $venue["className"] . '">' . $venue["className"] . '</option>';
                    }
                    ?>
                </select>
            </form>

            <div class="attendance-button">
                <button id="startButton" class="add">Launch Facial Recognition</button>
                <button id="endButton" class="add" style="display:none">End Attendance Process</button>
                <button id="endAttendance" class="add">END Attendance Taking</button>
            </div>

            <div class="video-container" style="display:none;">
                <video id="video" width="600" height="450" autoplay></video>
                <canvas id="overlay"></canvas>
            </div>

            <div class="table-container">
                <div id="studentTableContainer"></div>
            </div>

        </div>
    </section>

    <script>
        let attendanceData = []; // Global variable to hold attendance data

        document.getElementById('startButton').addEventListener('click', function() {
            // Start your facial recognition logic here
            // Simulating facial recognition results
            attendanceData = [
                { studentID: '124568', attendanceStatus: 'present' }, // Example data
                { studentID: '126754', attendanceStatus: 'present' }   // Example data
                // Add more predictions as needed
            ];
            console.log('Facial recognition completed. Attendance data:', attendanceData);
        });

        document.getElementById('endAttendance').addEventListener('click', function() {
            // Ensure we have attendance data from the facial recognition model
            if (attendanceData.length === 0) {
                alert('No attendance data available. Please run facial recognition first.');
                return;
            }

            // Add course and unit information
            const course = document.getElementById('courseSelect').value;
            const unit = document.getElementById('unitSelect').value;

            // Update attendanceData with course and unit values
            attendanceData = attendanceData.map(data => ({
                ...data,
                course: course,
                unit: unit
            }));

            // Send attendance data to the server
            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(attendanceData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json(); // This parses the JSON response
            })
            .then(data => {
                console.log('Attendance recorded:', data);
                document.getElementById('messageDiv').style.display = 'block';
                document.getElementById('messageDiv').innerText = data.message || "Attendance processed.";

                // Call the blockchain function
                recordAttendanceOnBlockchain(attendanceData);
            })
            .catch((error) => {
                console.error('Error:', error);
                document.getElementById('messageDiv').style.display = 'block';
                document.getElementById('messageDiv').innerText = "Error processing attendance.";
            });
        });

        async function recordAttendanceOnBlockchain(attendanceData) {
            if (typeof window.ethereum !== 'undefined') {
                const web3 = new Web3(window.ethereum);
                const contractAddress = '0xE38Be253d36aD980fBda75071d09F9D79904BFD1'; // Your contract address
                const contractABI = [
                    {
                        "inputs": [
                            { "internalType": "string", "name": "_studentId", "type": "string" },
                            { "internalType": "string", "name": "_courseCode", "type": "string" },
                            { "internalType": "string", "name": "_unitCode", "type": "string" },
                            { "internalType": "string", "name": "_date", "type": "string" }
                        ],
                        "name": "markAttendance",
                        "outputs": [],
                        "stateMutability": "nonpayable",
                        "type": "function"
                    }
                ];

                const contract = new web3.eth.Contract(contractABI, contractAddress);
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const account = accounts[0];

                for (let data of attendanceData) {
                    try {
                        const receipt = await contract.methods.markAttendance(
                            data.studentID,
                            data.course,
                            data.unit,
                            new Date().toISOString().split('T')[0] // Use current date
                        ).send({ from: account });

                        console.log('Attendance recorded on blockchain:', receipt);
                    } catch (error) {
                        console.error('Error recording on blockchain:', error);
                    }
                }
            } else {
                console.error('Ethereum wallet not found. Please install MetaMask.');
                document.getElementById('messageDiv').style.display = 'block';
                document.getElementById('messageDiv').innerText = "Ethereum wallet not found. Please install MetaMask.";
            }
        }
    </script>

    <?php js_asset(["active_link", 'face_logics/script']); ?>

</body>

</html>