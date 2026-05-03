<?php
session_start(); 
?>

<!DOCTYPE html>
<html>
<head>
<title>Homepage</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background-color: black; font-family: helvetica; }

  .search-wrap {
    position: relative;
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  #search-input {
    width: 500px;
    padding: 10px 20px;
    font-size: 15px;
    background-color: lightgray;
    border: 2px solid #1DB954;
    border-radius: 20px;
    outline: none;
    color: black;
  }

  #dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%);
    background: #1a1a1a;
    border: 1px solid #1DB954;
    border-radius: 12px;
    max-height: 220px;
    overflow-y: auto;
    z-index: 100;
    display: none;
    width: 500px;
  }

  #dropdown.open { display: block; }

  .drop-item {
    padding: 10px 16px; cursor: pointer; font-size: 14px;
    color: #1DB954; border-bottom: 1px solid #2a2a2a;
    transition: background 0.15s;
  }
  .drop-item:last-child { border-bottom: none; }
  .drop-item:hover { background: #2a2a2a; }

  .loading  { color: #1DB954; margin-top: 20px; display: none; text-align: center; }
  .error-msg { color: #e05c5c; margin-top: 20px; display: none; text-align: center; }

  #results-section { display: none; margin-top: 24px; max-width: 800px; margin-left: auto; margin-right: auto; }
  .selected-label { color: #888; font-size: 14px; margin-bottom: 16px; text-align: center; }
  .selected-label span { color: #1DB954; font-weight: 600; }

  .result-card {
    background: #1a1a1a; border: 1px solid #2e2e2e;
    border-radius: 12px; padding: 14px 20px;
    margin-bottom: 10px; display: flex;
    align-items: center; gap: 16px;
    transition: border-color 0.2s;
  }
  .result-card:hover { border-color: #1DB95455; }

  .rank { font-size: 13px; color: #555; min-width: 20px; text-align: center; }
  .song-info { flex: 1; }
  .song-name { font-size: 15px; font-weight: 500; color: #fff; margin-bottom: 3px; }
  .song-meta { font-size: 13px; color: #666; }

  .bar-wrap { display: flex; align-items: center; gap: 10px; min-width: 160px; }
  .bar-bg { flex: 1; height: 6px; background: #2e2e2e; border-radius: 4px; overflow: hidden; }
  .bar-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, #1DB954, #1ed760);
    transition: width 0.6s ease;
  }
  .pct { font-size: 13px; color: #888; min-width: 38px; text-align: right; }
</style>
</head>
<body>

<!-- Header -->
<div style="display:flex; align-items:center; justify-content:center; gap:20px; color:#1DB954; padding-top:20px;">
  <img src="Pictures/Spotify_icon.png" alt="Spotify Logo" width="90">
  <div style="text-align:center;">
    <h1 style="margin:0; font-size:48px;">Spotify Mood Exploration</h1>
    <h2 style="margin:0; font-size:26px;">A Database Project by Carson and Pranav</h2>
  </div>
</div>

<br><br><br>

<!-- Welcome -->
<div style="color:#1DB954; padding-left:20px;">
  <?php echo "Welcome, " . htmlspecialchars($_SESSION['username']) . "!"; ?>
</div>

<br><br>

<!-- Search -->
<div class="search-wrap">
  <input type="text" id="search-input" placeholder="Search for a song..." autocomplete="off"/>
  <div id="dropdown"></div>
</div>

<div class="loading"   id="loading">Finding similar songs...</div>
<div class="error-msg" id="error-msg">Something went wrong. Please try again.</div>

<!-- Results -->
<div id="results-section">
  <p class="selected-label">Showing results for: <span id="selected-name"></span></p>
  <div id="results-list"></div>
</div>

<!-- History -->
<div id="history-section" style="margin-top:48px; max-width:600px; margin-left:auto; margin-right:auto; padding-bottom:60px;">
  <div style="display:flex; align-items:center; justify-content:center; gap:16px; margin-bottom:16px;">
    <h3 style="color:#1DB954; font-size:18px;">Your Search History</h3>
    <button onclick="clearHistory()"
      style="background:none; border:1px solid #e05c5c; color:#e05c5c;
             border-radius:20px; padding:4px 14px; font-size:13px; cursor:pointer;">
      Clear All
    </button>
  </div>
  <div id="history-list"></div>
</div>


<script>
  const FLASK_API = 'https://spotify-similarity.onrender.com/api';
  const PHP_BASE  = ''; 

  let allTracks = [];

  const input          = document.getElementById('search-input');
  const dropdown       = document.getElementById('dropdown');
  const loading        = document.getElementById('loading');
  const errorMsg       = document.getElementById('error-msg');
  const resultsSection = document.getElementById('results-section');
  const resultsList    = document.getElementById('results-list');
  const selectedName   = document.getElementById('selected-name');

  // ── Load all track names from Flask ──────────────────────────
  async function loadTracks() {
    try {
      const res = await fetch(`${FLASK_API}/tracks`);
      allTracks = await res.json();
    } catch(e) { console.error('Could not load tracks', e); }
  }

  // ── Dropdown filtering ────────────────────────────────────────
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase().trim();
    if (!q) { dropdown.classList.remove('open'); return; }
    const matches = allTracks.filter(t => t.toLowerCase().includes(q)).slice(0, 30);
    if (!matches.length) { dropdown.classList.remove('open'); return; }
    dropdown.innerHTML = matches.map(t =>
      `<div class="drop-item" data-track="${t}">${t}</div>`
    ).join('');
    dropdown.classList.add('open');
  });

  dropdown.addEventListener('click', e => {
    const item = e.target.closest('.drop-item');
    if (!item) return;
    input.value = item.dataset.track;
    dropdown.classList.remove('open');
    fetchSimilar(item.dataset.track);
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.search-wrap')) dropdown.classList.remove('open');
  });

  // ── Main search ───────────────────────────────────────────────
  async function fetchSimilar(track) {
    loading.style.display        = 'block';
    resultsSection.style.display = 'none';
    errorMsg.style.display       = 'none';

    try {
      // 1. Get similarity from Flask
      const res  = await fetch(`${FLASK_API}/similar?track=${encodeURIComponent(track)}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      // 2. Save to MySQL via PHP
      await fetch(`${PHP_BASE}/save_history.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          track_name:  data.selected,
          artist_name: data.selected_details?.artist_name || '',
          genre:       data.selected_details?.genre || ''
        })
      });

      // 3. Render results
      selectedName.textContent = data.selected;
      resultsList.innerHTML = data.results.map((song, i) => `
        <div class="result-card">
          <div class="rank">${i + 1}</div>
          <div class="song-info">
            <div class="song-name">${song.track_name}</div>
            <div class="song-meta">${song.artist_name} &nbsp;·&nbsp; ${song.genre}</div>
          </div>
          <div class="bar-wrap">
            <div class="bar-bg">
              <div class="bar-fill" style="width:${song.similarity}%"></div>
            </div>
            <div class="pct">${song.similarity}%</div>
          </div>
        </div>`).join('');

      resultsSection.style.display = 'block';

      // 4. Refresh history
      loadHistory();

    } catch(e) {
      errorMsg.style.display = 'block';
      console.error(e);
    } finally {
      loading.style.display = 'none';
    }
  }

  // ── History ───────────────────────────────────────────────────
  async function loadHistory() {
    try {
      const res   = await fetch(`${PHP_BASE}/get_history.php`);
      const items = await res.json();
      renderHistory(items);
    } catch(e) { console.error('Could not load history', e); }
  }

  function renderHistory(items) {
    const list = document.getElementById('history-list');
    if (!items || !items.length) {
      list.innerHTML = '<p style="color:#555; text-align:center; font-size:14px;">No search history yet.</p>';
      return;
    }
    list.innerHTML = items.map(item => `
      <div onclick="reuseSearch('${item.track_name.replace(/'/g, "\\'")}')"
        style="background:#1a1a1a; border:1px solid #2e2e2e; border-radius:12px;
               padding:12px 20px; margin-bottom:8px; display:flex;
               align-items:center; justify-content:space-between; cursor:pointer;"
        onmouseover="this.style.borderColor='#1DB95455'"
        onmouseout="this.style.borderColor='#2e2e2e'">
        <div>
          <div style="font-size:15px; font-weight:500; color:#fff;">${item.track_name}</div>
          <div style="font-size:13px; color:#666; margin-top:2px;">${item.artist_name} &nbsp;·&nbsp; ${item.genre}</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:12px; color:#444;">${item.searched_at}</div>
          ${item.search_count > 1
            ? `<div style="font-size:11px; color:#1DB954; margin-top:2px;">searched ${item.search_count}×</div>`
            : ''}
        </div>
      </div>`).join('');
  }

  function reuseSearch(trackName) {
    document.getElementById('search-input').value = trackName;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    fetchSimilar(trackName);
  }

  async function clearHistory() {
    if (!confirm('Clear your entire search history?')) return;
    try {
      await fetch(`${PHP_BASE}/clear_history.php`);
      renderHistory([]);
    } catch(e) { console.error('Could not clear history', e); }
  }

  // ── Init ──────────────────────────────────────────────────────
  loadTracks();
  loadHistory();
</script>

</body>
</html>
