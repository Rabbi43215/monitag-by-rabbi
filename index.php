<?php
// === Simple file based storage ===
$file = 'adsdata.json';
if (!file_exists($file)) file_put_contents($file, '{}');
$adsData = json_decode(file_get_contents($file), true);
if (!is_array($adsData)) $adsData = [];

// Handle AJAX POST request for claiming reward
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }
    $user_id = intval($input['user_id']);
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit;
    }
    if (!isset($adsData[$user_id])) $adsData[$user_id] = 0;
    if ($adsData[$user_id] >= 100) {
        echo json_encode(['status' => 'error', 'message' => 'Maximum ads watched']);
        exit;
    }
    $adsData[$user_id]++;
    file_put_contents($file, json_encode($adsData));
    echo json_encode(['status' => 'success', 'ads_watched' => $adsData[$user_id]]);
    exit;
}

// Handle GET request for fetching stats
if (isset($_GET['action']) && $_GET['action'] === 'stats' && isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    $user_id = intval($_GET['user_id']);
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit;
    }
    $ads_watched = isset($adsData[$user_id]) ? $adsData[$user_id] : 0;
    echo json_encode(['status' => 'success', 'ads_watched' => $ads_watched]);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Telegram WebApp Monetag Ads</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: Arial,sans-serif; max-width: 500px; margin: auto; padding: 20px; background: #111; color: #eee; }
    button { padding: 12px 20px; font-size: 18px; cursor: pointer; border-radius: 8px; border:none; background: #0f0; color:#000; }
    #message { margin-top: 15px; font-weight: bold; }
    .stats { margin-top: 30px; }
    .progress-container {
      width: 100%; background: #222; border-radius: 10px; overflow: hidden; margin-top: 10px;
    }
    .progress-bar {
      height: 25px; width: 0%; background: #0f0; text-align: center; line-height: 25px; color:#000; font-weight: bold;
    }
  </style>
</head>
<body>

<h2>Watch Monetag Ad & Earn Coins</h2>

<button id="watch-ads">Watch Ad</button>
<button id="cancel" style="background:#f44; margin-left: 10px;">Cancel</button>

<div id="message"></div>

<div class="stats">
  <div>Total Ads Available: <span id="total-ads">100</span></div>
  <div>Ads Watched: <span id="ads-watched">0</span></div>
  <div>Rewards Earned: <span id="rewards-earned">0</span></div>
  <div class="progress-container">
    <div id="progress-bar" class="progress-bar">0%</div>
  </div>
</div>

<!-- Monetag SDK -->
<script src="//solseewuthi.net/sdk.js" data-zone="8615603" data-sdk="show_8615603"></script>

<script>
  Telegram.WebApp.ready();
  const watchAdsButton = document.getElementById('watch-ads');
  const cancelButton = document.getElementById('cancel');
  const messageEl = document.getElementById('message');
  const totalAds = 100;
  const totalAdsEl = document.getElementById('total-ads');
  const adsWatchedEl = document.getElementById('ads-watched');
  const rewardsEarnedEl = document.getElementById('rewards-earned');
  const progressBarEl = document.getElementById('progress-bar');

  // Telegram user ID
  const user_id = Telegram.WebApp.initDataUnsafe.user.id;

  totalAdsEl.textContent = totalAds;

  function loadAd() {
    try {
      window.show_8615603();
      messageEl.textContent = 'Ad loaded. Waiting for completion...';

      // Monetag অ্যাড শেষ হওয়ার approx 15 সেকেন্ড পরে রিওয়ার্ড ক্লেইম করা হবে (simulate)
      setTimeout(() => {
        claimReward();
      }, 15000);
    } catch(e) {
      messageEl.textContent = 'Failed to load ad.';
    }
  }

  function claimReward() {
    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id })
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        messageEl.textContent = '✅ Reward added!';
        updateStats();
      } else {
        messageEl.textContent = data.message || 'Failed to claim reward.';
      }
    })
    .catch(() => {
      messageEl.textContent = 'Error claiming reward.';
    });
  }

  function updateStats() {
    fetch(`?action=stats&user_id=${user_id}`)
      .then(res => res.json())
      .then(data => {
        if(data.status === 'success'){
          const adsWatched = data.ads_watched || 0;
          const rewardsEarned = adsWatched * 10;
          adsWatchedEl.textContent = adsWatched;
          rewardsEarnedEl.textContent = rewardsEarned;
          const percent = Math.min((adsWatched/totalAds)*100, 100);
          progressBarEl.style.width = percent + '%';
          progressBarEl.textContent = Math.round(percent) + '%';
        } else {
          messageEl.textContent = data.message || 'Failed to load stats.';
        }
      })
      .catch(() => {
        messageEl.textContent = 'Error loading stats.';
      });
  }

  watchAdsButton.addEventListener('click', () => {
    messageEl.textContent = '';
    loadAd();
  });

  cancelButton.addEventListener('click', () => {
    window.location.href = '/';
  });

  updateStats();
</script>

</body>
</html>
