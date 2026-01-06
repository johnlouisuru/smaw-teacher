<?php require_once "../db-config/security.php";

// If already logged in and profile complete, redirect to dashboard
if (!isLoggedIn()) {
    header('Location: ../');
    exit;
}

// Get counts of students per welding_level
$sql = "SELECT sr.welding_level, COUNT(sr.student_id) AS total_students
        FROM student_result sr
        GROUP BY sr.welding_level
        ORDER BY sr.welding_level ASC";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build arrays for Chart.js
$labels = [];
$data   = [];

foreach ($results as $row) {
    $labels[] = "Level " . $row['welding_level']; // or just $row['welding_level']
    $data[]   = $row['total_students'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../<?=$_ENV['PAGE_ICON']?>">
  <link rel="icon" type="image/png" href="../<?=$_ENV['PAGE_ICON']?>">
  <title><?=$_ENV['PAGE_HEADER']?></title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
</head>

<body class="g-sidenav-show  bg-gray-100">
  
  <!-- Sidebar -->
  <?php 
    require_once "navbars/sidebar.php";
  ?>
  <!-- End of Sidebar -->

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <!-- TOpbar -->
      <?php 
          require_once "navbars/topbar.php";
      ?>
    <!-- End TOpbar -->

    <div class="container-fluid py-2">
      <div class="row">
        <div class="ms-3">
          <h3 class="mb-0 h4 font-weight-bolder">Dashboard</h3>
          <p class="mb-4">
            Main Dashboard for the [SMAW] Welding App.
          </p>
        </div>

        <!-- Card -->
         
        <?php 
            require_once "cards/dashboard-cards.php";
        ?>

        <!-- End of Card -->
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-6 mt-4 mb-4">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-0 ">Student's Attempt</h6>
              <p class="text-sm ">Student's Performance</p>
              <div class="pe-2">
                <div class="chart">
                  <canvas id="chart-bars" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"> Student's Performance Minutes Ago. </p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 mt-4 mb-3">
          <div class="card">
            <div class="card-body">
              <h6 class="mb-0 ">Average Time to Complete Each Level</h6>
              <p class="text-sm ">Student's Performance</p>
              <div class="pe-2">
                <div class="chart">
                  <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm">just updated</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Lagayan dito ng Table with Roadmap -->

      <!-- Lagayan dito ng Table with Roadmap -->
      
      <!-- Footer Area -->
      <?php 
          require_once "navbars/footers.php";
      ?>
      <!-- Footer Area -->
    </div>
  </main>
  
  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <script>
    var ctx = document.getElementById("chart-bars").getContext("2d");

new Chart(ctx, {
  type: "bar",
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: "Students Tried",
      tension: 0.4,
      borderWidth: 0,
      borderRadius: 4,
      borderSkipped: false,
      backgroundColor: "#43A047",
      data: <?= json_encode($data) ?>,
      barThickness: 'flex'
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
    scales: {
      y: {
        grid: {
          drawBorder: false,
          beginAtZero: true,
          display: true,
          drawOnChartArea: true,
          drawTicks: false,
          borderDash: [5, 5],
          color: '#e5e5e5'
        },
        ticks: {
          suggestedMin: 0,
          beginAtZero: true,
          padding: 10,
          font: { size: 14, lineHeight: 2 },
          color: "#737373"
        },
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
          borderDash: [5, 5]
        },
        ticks: {
          display: true,
          color: '#737373',
          padding: 10,
          font: { size: 14, lineHeight: 2 },
        }
      },
    },
  },
});

</script>
<?php


// Get average time_used grouped by welding_level
$sql = "SELECT sr.welding_level, AVG(sr.time_used) AS avg_time
        FROM student_result sr
        GROUP BY sr.welding_level
        ORDER BY sr.welding_level ASC";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build arrays for Chart.js
$labels = [];
$data   = [];

foreach ($results as $row) {
    $labels[] = "Level " . $row['welding_level']; // label for x-axis
    $data[]   = round($row['avg_time'], 2);      // average time used
}
?>

<script>



  var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

new Chart(ctx3, {
  type: "line",
  data: {
    labels: <?= json_encode($labels) ?>,   // ["Level 1","Level 2",...,"Level 8"]
    datasets: [{
      label: "Average Time Used",
      tension: 0.3,                        // slight curve
      borderWidth: 2,
      pointRadius: 4,
      pointBackgroundColor: "#43A047",
      pointBorderColor: "transparent",
      borderColor: "#43A047",
      backgroundColor: "rgba(67,160,71,0.2)", // light fill under line
      fill: true,
      data: <?= json_encode($data) ?>,     // [avg1, avg2, ..., avg8]
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: true }
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
    scales: {
      y: {
        beginAtZero: true,                 // vertical axis starts at 0
        grid: {
          drawBorder: false,
          display: true,
          drawOnChartArea: true,
          drawTicks: false,
          borderDash: [4, 4],
          color: '#e5e5e5'
        },
        ticks: {
          display: true,
          padding: 10,
          color: '#737373',
          font: { size: 14, lineHeight: 2 }
        }
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
          borderDash: [4, 4]
        },
        ticks: {
          display: true,
          color: '#737373',
          padding: 10,
          font: { size: 14, lineHeight: 2 }
        }
      }
    }
  }
});
  </script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>