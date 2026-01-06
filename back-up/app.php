<?php
include_once "db-config/security.php";

$_SESSION['student_id'] = 20;
$_SESSION['section_id'] = 3;
// $_SESSION['student_id'] = 20;

$user_id = $_SESSION['student_id'];

$sql = "
    SELECT 
        l.id,
        l.level_number,
        l.level_name,
        l.stage_id,
        l.created_at,
        MAX(sr.welding_level) AS welding_level
    FROM levels l
    LEFT JOIN student_result sr
        ON sr.student_id = :student_id
        AND sr.welding_level = l.level_number
    GROUP BY 
        l.id,
        l.level_number,
        l.level_name,
        l.stage_id,
        l.created_at
    ORDER BY l.level_number ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':student_id' => $user_id
]);

$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total levels 
$totalLevelsStmt = $pdo->query("SELECT COUNT(id) AS total_levels FROM levels");
$totalLevels = $totalLevelsStmt->fetch(PDO::FETCH_ASSOC)['total_levels'];
// Fetch unlocked stages for this student 
$unlockedStmt = $pdo->prepare(" SELECT DISTINCT welding_level FROM student_result WHERE student_id = :uid ORDER BY welding_level ASC ");
$unlockedStmt->execute([':uid' => $user_id]);
$unlockedStages = $unlockedStmt->fetchAll(PDO::FETCH_COLUMN);
// Calculate completion % 
$unlockedCount = count($unlockedStages);
$completionPercent = $totalLevels > 0 ? round(($unlockedCount / $totalLevels) * 100) : 0;
// Fetch all attempts sorted by shortest time 
$attemptsStmt = $pdo->prepare(" SELECT welding_level, time_used, date_created FROM student_result WHERE student_id = :uid ORDER BY time_used ASC ");
$attemptsStmt->execute([':uid' => $user_id]);
$attempts = $attemptsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- <title>Game Roadmap</title> -->
  <link rel="apple-touch-icon" sizes="76x76" href="<?= $_ENV['PAGE_ICON'] ?>">
  <link rel="icon" type="image/png" href="<?= $_ENV['PAGE_ICON'] ?>">
  <title><?= $_ENV['PAGE_HEADER'] ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />


  <link href="css/app.css" rel="stylesheet" />
</head>

<body>

  <div class="container mt-4">

    <!-- Stages Page -->
    <?php
    $sqlMax = "
          SELECT MAX(welding_level) 
          FROM student_result 
          WHERE student_id = :student_id
      ";
    $stmtMax = $pdo->prepare($sqlMax);
    $stmtMax->execute(['student_id' => $user_id]);

    $maxCompletedLevel = (int)$stmtMax->fetchColumn();
    ?>
    <div id="page-stages" role="region" aria-label="Stages page">
      <h3 class="text-center mb-4">Your SMAW Roadmap</h3>

      <div class="row g-3">
        <?php foreach ($levels as $row): ?>

          <?php
          $currentLevel = (int)$row['level_number'];

          // Unlock completed + next stage
          $isUnlocked = ($currentLevel === 1)
            || ($currentLevel <= ($maxCompletedLevel + 1));

          $badgeText  = $isUnlocked ? 'Unlocked' : 'Locked';
          $badgeClass = $isUnlocked ? 'bg-success' : 'bg-danger';
          $cardClass  = $isUnlocked ? '' : 'locked';
          $modalId    = 'stage' . $currentLevel . 'Modal';
          ?>

          <div class="col-6">
            <div class="card stage-card <?= $cardClass ?>"
              <?php if ($isUnlocked): ?>
              data-bs-toggle="modal"
              data-bs-target="#<?= $modalId ?>"
              <?php else: ?>
              data-bs-toggle="modal"
              data-bs-target="#lockedStageModal"
              <?php endif; ?>>
              <div class="card-body text-center py-4">
                <h5 class="mb-1">Stage <?= $currentLevel ?></h5>
                <span class="badge <?= $badgeClass ?>">
                  <?= $badgeText ?>
                </span>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
      </div>

      <?php foreach ($levels as $row): ?>

        <?php
        $currentLevel = (int)$row['level_number'];

        // SAME unlock logic as cards
        $isUnlocked = ($currentLevel === 1)
          || ($currentLevel <= ($maxCompletedLevel + 1));

        if (!$isUnlocked) continue;

        $nextLevel = $currentLevel + 1;
        $modalId   = 'stage' . $currentLevel . 'Modal';
        ?>

        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1"
          aria-hidden="true"
          aria-labelledby="<?= $modalId ?>Label">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                  Stage <?= $currentLevel ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                Welcome to <strong>Stage <?= $currentLevel ?></strong>.<br><br>
                This challenge is called
                <strong><?= htmlspecialchars($row['level_name']) ?></strong>.<br><br>
                Complete it to unlock <strong>Stage <?= $nextLevel ?></strong>.
              </div>

              <div class="modal-footer">
                <a href="stage_holder?level=<?= $currentLevel ?>&level_name=<?= urlencode($row['level_name']) ?>"
                  class="btn btn-success">
                  Start
                </a>
              </div>

            </div>
          </div>
        </div>

      <?php endforeach; ?>



    </div>


    <!-- Progress Page -->
    <div id="page-progress" role="region" aria-label="Progress page">
      <h3 class="text-center mb-4">Progress</h3> <!-- Overall Completion -->
      <div class="card mb-4">
        <div class="card-body">
          <p class="mb-2">Overall completion</p>
          <div class="progress" style="height: 30px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $completionPercent ?>%;" aria-valuenow="<?= $completionPercent ?>" aria-valuemin="0" aria-valuemax="100"> <?= $completionPercent ?>% Complete </div>
          </div>
          <div class="mt-3 d-flex justify-content-between"> <span>Stages unlocked</span> <span class="fw-bold text-warning"><?= $unlockedCount ?> / <?= $totalLevels ?></span> </div>
        </div>
      </div> <!-- Unlocked Stages -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="mb-3">Unlocked Stages</h5>
          <ul class="list-group"> <?php foreach ($unlockedStages as $stage): ?> <li class="list-group-item"> Stage <?= htmlspecialchars($stage) ?> </li> <?php endforeach; ?> </ul>
        </div>
      </div> <!-- Attempts Table -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Attempts (sorted by shortest time)</h5>
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Stage</th>
                <th>Time Used (seconds)</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody> <?php foreach ($attempts as $attempt): ?> <tr>
                  <td>Stage <?= htmlspecialchars($attempt['welding_level']) ?></td>
                  <td><?= htmlspecialchars($attempt['time_used']) ?></td>
                  <td><?= htmlspecialchars($attempt['date_created']) ?></td>
                </tr> <?php endforeach; ?> </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Profile Page -->
    <div id="page-profile" class="d-none" role="region" aria-label="Profile page">
      <h3 class="text-center mb-4">Profile</h3>
      <div class="card">
        <div class="card-body text-center">
          <img src="<?= $_ENV['PAGE_ICON'] ?>" class="rounded-circle mb-3 img-thumbnail" width="150px" height="150px" alt="Profile" />
          <h5 class="mb-1">Player Name</h5>
          <p class="mb-0">Level: 1</p>
          <p class="text-muted small mt-2">Keep progressing to unlock more stages and rewards.</p>
        </div>
      </div>
    </div>

  </div>


  <!-- Stage Modal Reusable -->
  <!-- <div class="modal fade" id="stage1Modal" tabindex="-1" aria-hidden="true" aria-labelledby="stage1Label">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="stage1Label" class="modal-title">Stage 1</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Welcome to Stage {levels.level_number} This is challenge is called {levels.level_name}. Complete it to unlock Stage {levels.level_number +1 }.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">Start</button>
        </div>
      </div>
    </div>
  </div> -->


  <!-- Bottom Navigation -->
  <nav class="bottom-nav" aria-label="Bottom navigation">
    <button onclick="showPage('stages')" id="btn-stages" class="active" aria-label="Go to Stages">
      <i class="fa-solid fa-gamepad"></i>
      Stages
    </button>
    <button onclick="showPage('progress')" id="btn-progress" aria-label="Go to Progress">
      <i class="fa-solid fa-chart-line"></i>
      Progress
    </button>
    <button onclick="showPage('profile')" id="btn-profile" aria-label="Go to Profile">
      <i class="fa-solid fa-user"></i>
      Profile
    </button>
  </nav>

  <div class="modal fade" id="lockedStageModal" tabindex="-1"
    aria-hidden="true"
    aria-labelledby="lockedStageLabel">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="lockedStageLabel">
            Stage Locked
          </h5>
          <button type="button" class="btn-close btn-close-white"
            data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <i class="fa-solid fa-lock fa-3x text-danger mb-3"></i>
          <p class="fw-bold mb-1">This Stage is LOCKED</p>
          <p class="text-muted mb-0">
            Complete the previous stage to unlock it.
          </p>
        </div>

        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-danger"
            data-bs-dismiss="modal">
            OK
          </button>
        </div>

      </div>
    </div>
  </div>



  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function showPage(page) {
      // Toggle pages
      document.getElementById("page-stages").classList.add("d-none");
      document.getElementById("page-progress").classList.add("d-none");
      document.getElementById("page-profile").classList.add("d-none");
      document.getElementById("page-" + page).classList.remove("d-none");

      // Update active button state
      document.getElementById("btn-stages").classList.remove("active");
      document.getElementById("btn-progress").classList.remove("active");
      document.getElementById("btn-profile").classList.remove("active");

      document.getElementById("btn-" + page).classList.add("active");
    }
  </script>
</body>

</html>