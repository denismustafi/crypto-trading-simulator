<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_usd = 10000;
$user_holdings = [];
$avg_prices = [];

if ($user_id) {
    $stmt = $conn->prepare("SELECT usd_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $user_usd = $res['usd_balance'];
    }

    $holdings_res = $conn->query("SELECT coin, amount FROM holdings WHERE user_id = $user_id");
    while($row = $holdings_res->fetch_assoc()) {
        $user_holdings[$row['coin']] = (float)$row['amount'];
    }

    $avg_res = $conn->query("SELECT coin, SUM(amount_usd)/SUM(coins) as avg_price FROM transactions WHERE user_id = $user_id AND type='buy' GROUP BY coin");
    while($row = $avg_res->fetch_assoc()) {
        $avg_prices[$row['coin']] = (float)$row['avg_price'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoSim</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>₿</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg:        #f8fafc; 
            --panel:     #ffffff; 
            --surface:   #f1f5f9; 
            --border:    #e2e8f0; 
            --border-hi: #cbd5e1;
            --blue:      #2563eb; 
            --blue-dim:  #eff6ff;
            --green:     #10b981; 
            --green-dim: #ecfdf5;
            --red:       #ef4444; 
            --red-dim:   #fef2f2;
            --gold:      #f59e0b;
            --text:      #0f172a; 
            --muted:     #64748b; 
            --mono:      'Space Mono', monospace;
            --sans:      'Outfit', sans-serif;
            --shadow:    0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            background-image:
                linear-gradient(rgba(37,99,235,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37,99,235,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .app { display: flex; height: 100vh; }

        .stat-card, .chart-panel, .trade-panel, .holding-card, .history-panel, .mini-card, .auth-modal {
            box-shadow: var(--shadow);
            border: 1px solid #ffffff; 
            border-radius: 12px;
        }

        .sidebar { width: 220px; background: var(--panel); border-right: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; z-index: 10; }
        .logo { padding: 22px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 34px; height: 34px; background: var(--blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; flex-shrink: 0; }
        .logo-text { font-size: 16px; font-weight: 800; }
        .logo-text span { color: var(--blue); }
        .sidebar nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 2px; }
        .nav-link { display: flex; align-items: center; gap: 10px; color: var(--muted); text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.18s; }
        .nav-link i { font-size: 18px; }
        .nav-link:hover { color: var(--text); background: var(--surface); }
        .nav-link.active { color: var(--blue); background: var(--blue-dim); }
        .sidebar-user { padding: 16px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--blue); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-tag  { font-size: 11px; color: var(--muted); font-family: var(--mono); }
        .btn-logout { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 16px; padding: 4px; transition: color 0.2s; }
        .btn-logout:hover { color: var(--red); }

        .main { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 14px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; }
        .topbar-title { font-size: 22px; font-weight: 800; }
        .topbar-title span { color: var(--blue); }
        .topbar-time { font-family: var(--mono); font-size: 12px; color: var(--muted); }

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .stat-card { background: var(--panel); padding: 16px 18px; display: flex; align-items: center; gap: 14px; transition: border-color 0.3s, box-shadow 0.3s; }
        .stat-card.flash-green { animation: flashGreen 0.6s ease; }
        .stat-card.flash-red   { animation: flashRed   0.6s ease; }
        @keyframes flashGreen { 0%,100%{box-shadow:var(--shadow);border-color:#ffffff} 50%{box-shadow:0 0 30px rgba(16,185,129,0.25);border-color:var(--green)} }
        @keyframes flashRed   { 0%,100%{box-shadow:var(--shadow);border-color:#ffffff} 50%{box-shadow:0 0 30px rgba(239,68,68,0.25);border-color:var(--red)} }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .stat-icon.blue  { background: var(--blue-dim);  color: var(--blue); }
        .stat-icon.green { background: var(--green-dim); color: var(--green); }
        .stat-icon.gold  { background: rgba(245,158,11,0.12); color: var(--gold); }
        .stat-label { font-size: 11px; color: var(--muted); margin-bottom: 4px; font-family: var(--mono); letter-spacing: 1px; }
        .stat-value { font-size: 18px; font-weight: 700; font-family: var(--mono); }

        .content-grid { display: grid; grid-template-columns: 1fr 290px; gap: 14px; }
        .chart-panel { background: var(--panel); padding: 20px; display: flex; flex-direction: column; min-height: 330px; }
        .chart-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
        .chart-asset-name { font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 2px; font-family: var(--mono); letter-spacing: 1px; }
        .chart-price { font-family: var(--mono); font-size: 26px; font-weight: 700; transition: color 0.4s; }
        .chart-price.up   { color: var(--green); }
        .chart-price.down { color: var(--red); }
        .chart-price.neutral { color: var(--blue); }
        .chart-change { font-family: var(--mono); font-size: 12px; margin-top: 4px; }
        .chart-change.up   { color: var(--green); }
        .chart-change.down { color: var(--red); }
        .time-filters { display: flex; gap: 3px; background: var(--surface); padding: 4px; border-radius: 8px; height: fit-content; }
        .filter-btn { background: none; border: none; color: var(--muted); padding: 5px 9px; cursor: pointer; border-radius: 6px; font-weight: 700; font-size: 11px; font-family: var(--mono); transition: all 0.18s; }
        .filter-btn:hover { color: var(--text); }
        .filter-btn.active { background: var(--blue); color: white; }
        .canvas-wrap { flex: 1; position: relative; min-height: 200px; }
        .canvas-wrap canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; transition: opacity 0.3s; }

        .chart-skeleton { position: absolute; inset: 0; border-radius: 8px; display: none; background: linear-gradient(90deg, var(--surface) 25%, #fff 50%, var(--surface) 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; }
        .chart-skeleton.show { display: block; }
        @keyframes shimmer { from{background-position:200% 0} to{background-position:-200% 0} }

        .trade-panel { background: var(--panel); padding: 18px; display: flex; flex-direction: column; gap: 14px; }
        .trade-panel h3 { font-size: 14px; font-weight: 800; }
        .form-label { font-size: 11px; font-family: var(--mono); color: var(--muted); margin-bottom: 6px; display: block; letter-spacing: 1px; }
        .form-control { width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: var(--mono); font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--blue); }
        .input-prefix { position: relative; }
        .input-prefix span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-family: var(--mono); font-size: 14px; }
        .input-prefix input { padding-left: 24px; }
        .trade-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .btn-buy, .btn-sell { border: none; padding: 11px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 13px; font-family: var(--sans); display: flex; align-items: center; justify-content: center; gap: 6px; color: #fff; transition: all 0.18s; position: relative; overflow: hidden; }
        .btn-buy  { background: var(--green); color: #fff; }
        .btn-sell { background: var(--red); }
        .btn-buy:hover  { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-sell:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-buy.flash, .btn-sell.flash { animation: btnPop 0.35s ease; }
        @keyframes btnPop { 0%{transform:scale(1)} 50%{transform:scale(0.95)} 100%{transform:scale(1)} }
        .pct-btns { display: flex; gap: 5px; }
        .pct-btn { flex: 1; background: var(--surface); border: 1px solid var(--border); color: var(--muted); border-radius: 6px; padding: 5px; cursor: pointer; font-size: 11px; font-family: var(--mono); transition: all 0.18s; }
        .pct-btn:hover { color: var(--blue); border-color: var(--blue); }
        .msg-box { font-size: 12px; font-family: var(--mono); min-height: 18px; margin-top: 5px;}
        .msg-box.success { color: var(--green); }
        .msg-box.error   { color: var(--red); }

        .holdings-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .holding-card { background: var(--panel); padding: 14px; transition: border-color 0.2s; }
        .holding-card.active { border: 1px solid rgba(16,185,129,0.3); }
        .holding-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .holding-sym { font-family: var(--mono); font-size: 13px; font-weight: 700; color: var(--blue); }
        .holding-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--border); }
        .holding-dot.on { background: var(--green); box-shadow: 0 0 6px rgba(16,185,129,0.5); }
        .holding-name { font-size: 11px; color: var(--muted); margin-bottom: 4px; }
        .holding-amount { font-family: var(--mono); font-size: 13px; font-weight: 700; }
        .holding-usd { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-top: 2px; }

        .history-panel { background: var(--panel); padding: 18px; }
        .history-panel h3 { font-size: 14px; font-weight: 800; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .history-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .history-table th { font-family: var(--mono); font-size: 10px; color: var(--muted); text-align: left; padding: 6px 10px; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        .history-table td { padding: 9px 10px; border-bottom: 1px solid var(--border); font-family: var(--mono); }
        .history-table tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; }
        .badge.buy  { background: var(--green-dim); color: var(--green); }
        .badge.sell { background: var(--red-dim);   color: var(--red); }
        .history-empty { text-align: center; color: var(--muted); font-family: var(--mono); font-size: 12px; padding: 20px 0; }

        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
        .mini-card { background: var(--panel); padding: 16px 18px; }
        .mini-card h4 { font-size: 11px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 7px; color: var(--muted); font-family: var(--mono); letter-spacing: 1px; }
        .calc-row { display: flex; gap: 8px; }
        .btn-calc { background: var(--blue); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 12px; font-family: var(--sans); }
        .calc-result { font-size: 12px; font-family: var(--mono); color: var(--muted); margin-top: 10px; }
        .calc-result b { color: var(--text); }
        .market-stats { display: flex; justify-content: space-around; align-items: center; height: 100%; }
        .market-stat { text-align: center; }
        .ms-label { font-size: 10px; font-family: var(--mono); color: var(--muted); margin-bottom: 6px; letter-spacing: 1px; }
        .ms-val { font-size: 15px; font-weight: 700; font-family: var(--mono); }
        .ms-divider { width: 1px; height: 40px; background: var(--border); }

        .ticker { overflow: hidden; white-space: nowrap; border-bottom: 1px solid var(--border); background: var(--panel); padding: 6px 0; font-family: var(--mono); font-size: 12px; }
        .ticker-inner { display: inline-block; animation: tickerMove 30s linear infinite; padding-left: 100%; }
        .ticker-item { display: inline-block; margin: 0 24px; }
        .t-name { color: var(--text); font-weight: 700; margin-right: 6px; }
        .t-up   { color: var(--green); }
        .t-down { color: var(--red); }
        .t-muted { color: var(--muted); }
        @keyframes tickerMove { from{transform:translateX(0)} to{transform:translateX(-50%)} }

        .auth-overlay { position: fixed; inset: 0; z-index: 999; background: rgba(248,250,252,0.9); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; }
        .auth-overlay.hidden { display: none; }
        .auth-modal { background: var(--panel); width: 100%; max-width: 400px; padding: 36px; }
        .auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .auth-tabs { display: flex; background: var(--surface); border-radius: 9px; padding: 4px; margin-bottom: 26px; }
        .auth-tab { flex: 1; text-align: center; padding: 8px; border-radius: 7px; font-weight: 700; font-size: 13px; cursor: pointer; color: var(--muted); border: none; background: none; }
        .auth-tab.active { background: var(--blue); color: #fff; }
        .auth-form { display: flex; flex-direction: column; gap: 16px; }
        .auth-input { background: var(--surface); border: 1px solid var(--border); border-radius: 9px; padding: 11px 14px; width: 100%; }
        .btn-auth { background: var(--blue); color: #fff; border: none; border-radius: 9px; padding: 13px; font-weight: 700; cursor: pointer; }
        .auth-msg { text-align: center; font-size: 13px; min-height: 18px; font-family: var(--mono); }
        .auth-msg.error { color: var(--red); }
    </style>
</head>
<body>

<div class="auth-overlay" id="authOverlay">
    <div class="auth-modal">
        <div class="auth-logo">
            <div class="logo-icon"><i class="ph-fill ph-currency-btc"></i></div>
            <div class="logo-text" style="font-size:22px;">Crypto<span style="color:var(--blue)">Sim</span></div>
        </div>
        <div class="auth-tabs">
            <button class="auth-tab active" id="tabLogin" onclick="switchTab('login')">Sign In</button>
            <button class="auth-tab" id="tabRegister" onclick="switchTab('register')">Register</button>
        </div>
        <div id="loginForm" class="auth-form">
            <div>
                <label class="form-label">USERNAME</label>
                <input class="auth-input" type="text" id="loginUser" placeholder="your_username">
            </div>
            <div>
                <label class="form-label">PASSWORD</label>
                <input class="auth-input" type="password" id="loginPass" placeholder="••••••••">
            </div>
            <div class="auth-msg" id="loginMsg"></div>
            <button class="btn-auth" onclick="doLogin()">Sign In →</button>
        </div>
        <div id="registerForm" class="auth-form" style="display:none;">
            <div>
                <label class="form-label">USERNAME</label>
                <input class="auth-input" type="text" id="regUser" placeholder="choose_username">
            </div>
            <div>
                <label class="form-label">EMAIL</label>
                <input class="auth-input" type="email" id="regEmail" placeholder="you@email.com">
            </div>
            <div>
                <label class="form-label">PASSWORD</label>
                <input class="auth-input" type="password" id="regPass" placeholder="min 6 characters">
            </div>
            <div class="auth-msg" id="registerMsg"></div>
            <button class="btn-auth" onclick="doRegister()">Create Account →</button>
        </div>
    </div>
</div>

<div class="app">
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-fill ph-currency-btc"></i></div>
            <div class="logo-text">Crypto<span>Sim</span></div>
        </div>
        <nav>
            <a href="#" class="nav-link active" onclick="scrollToSection('top', this)"><i class="ph ph-chart-line-up"></i> Dashboard</a>
            <a href="#" class="nav-link" onclick="scrollToSection('walletSection', this)"><i class="ph ph-wallet"></i> Wallet</a>
            <a href="#" class="nav-link" onclick="scrollToSection('historySection', this)"><i class="ph ph-clock-counter-clockwise"></i> History</a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar" id="userAvatar">?</div>
            <div class="user-info">
                <div class="user-name" id="sidebarUsername">Guest</div>
                <div class="user-tag">Trader</div>
            </div>
            <button class="btn-logout" title="Logout" onclick="doLogout()"><i class="ph ph-sign-out"></i></button>
        </div>
    </aside>

    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">
        <div class="ticker">
            <div class="ticker-inner" id="tickerInner">
                <span class="ticker-item"><span class="t-name">BTC</span><span class="t-muted">Loading...</span></span>
            </div>
        </div>

        <main class="main">
            <div class="topbar">
                <div class="topbar-title">Market <span>Dashboard</span></div>
                <div class="topbar-time" id="liveTime">--:--:--</div>
            </div>

            <div class="stats">
                <div class="stat-card" id="cardUsd">
                    <div class="stat-icon blue"><i class="ph ph-wallet"></i></div>
                    <div>
                        <div class="stat-label">USD BALANCE</div>
                        <div class="stat-value" id="usdBalance" data-raw="10000">$10,000.00</div>
                    </div>
                </div>
                <div class="stat-card" id="cardCrypto">
                    <div class="stat-icon gold"><i class="ph ph-coins"></i></div>
                    <div>
                        <div class="stat-label">CRYPTO HELD</div>
                        <div class="stat-value" id="cryptoBalance" data-raw="0">0.000000</div>
                    </div>
                </div>
                <div class="stat-card" id="cardPortfolio">
                    <div class="stat-icon green"><i class="ph ph-trend-up"></i></div>
                    <div>
                        <div class="stat-label">PORTFOLIO</div>
                        <div class="stat-value" id="portfolioValue" data-raw="10000">$10,000.00</div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="chart-panel">
                    <div class="chart-header">
                        <div>
                            <div class="chart-asset-name" id="assetName">BITCOIN / USD</div>
                            <div class="chart-price neutral" id="price">$0.00</div>
                            <div class="chart-change" id="priceChange">—</div>
                        </div>
                        <div class="time-filters">
                            <button class="filter-btn active" onclick="setPeriod(this,7)">7D</button>
                            <button class="filter-btn" onclick="setPeriod(this,30)">1M</button>
                            <button class="filter-btn" onclick="setPeriod(this,90)">3M</button>
                            <button class="filter-btn" onclick="setPeriod(this,365)">1Y</button>
                        </div>
                    </div>
                    <div class="canvas-wrap">
                        <div class="chart-skeleton" id="chartSkeleton"></div>
                        <canvas id="priceChart"></canvas>
                    </div>
                </div>

                <div class="trade-panel">
                    <h3>⚡ Trade</h3>
                    <div>
                        <label class="form-label">ASSET</label>
                        <select id="cryptoSelect" class="form-control" onchange="loadData()">
                            <option value="bitcoin">Bitcoin (BTC)</option>
                            <option value="ethereum">Ethereum (ETH)</option>
                            <option value="solana">Solana (SOL)</option>
                            <option value="dogecoin">Dogecoin (DOGE)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">AMOUNT (USD)</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="tradeAmount" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">LIMIT PRICE (OPTIONAL)</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="limitPrice" class="form-control" placeholder="Leave empty for Market">
                        </div>
                    </div>
                    <div class="trade-btns">
                        <button class="btn-buy" id="btnBuy" onclick="handleTrade('buy')"><i class="ph-bold ph-arrow-up-right"></i> BUY</button>
                        <button class="btn-sell" id="btnSell" onclick="handleTrade('sell')"><i class="ph-bold ph-arrow-down-right"></i> SELL</button>
                    </div>
                    <div class="pct-btns">
                        <button class="pct-btn" onclick="setPercent(25)">25%</button>
                        <button class="pct-btn" onclick="setPercent(50)">50%</button>
                        <button class="pct-btn" onclick="setPercent(75)">75%</button>
                        <button class="pct-btn" onclick="setPercent(100)">MAX</button>
                    </div>
                    <div class="msg-box" id="tradeMessage"></div>
                </div>
            </div>

            <div class="holdings-grid" id="walletSection"></div>

            <div class="history-panel" id="historySection">
                <h3><i class="ph ph-clock-counter-clockwise"></i> Session History</h3>
                <div class="history-empty" id="historyEmpty">No trades made in this session.</div>
                <table class="history-table" id="historyTable" style="display:none;">
                    <thead>
                        <tr><th>TIME</th><th>TYPE</th><th>ASSET</th><th>USD</th><th>PRICE</th><th>COINS</th></tr>
                    </thead>
                    <tbody id="historyBody"></tbody>
                </table>
            </div>

            <div class="bottom-grid">
                <div class="mini-card">
                    <h4><i class="ph ph-chart-pie"></i> PORTFOLIO ASSETS</h4>
                    <div style="height: 100px; position: relative;">
                        <canvas id="portfolioPieChart"></canvas>
                    </div>
                </div>

                <div class="mini-card">
                    <h4><i class="ph ph-calculator"></i> PROFIT SIM</h4>
                    <div class="calc-row">
                        <input type="number" id="simAmount" class="form-control" placeholder="Invest $" style="flex:1;">
                        <button class="btn-calc" onclick="calculateProfit()">Calc</button>
                    </div>
                    <div class="calc-result" id="simResult">Enter an amount above ↑</div>
                </div>

                <div class="mini-card">
                    <div class="market-stats">
                        <div class="market-stat"><div class="ms-label">24H HIGH</div><div class="ms-val" id="maxPrice">—</div></div>
                        <div class="ms-divider"></div>
                        <div class="market-stat"><div class="ms-label">24H LOW</div><div class="ms-val" id="minPrice">—</div></div>
                        <div class="ms-divider"></div>
                        <div class="market-stat"><div class="ms-label">CHANGE</div><div class="ms-val" id="percentChange">—</div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let chart, pieChart;
let currentPrice = 0;
let currentPeriod = 7;
let tradeHistory = [];

const coinPrices = {bitcoin:0, ethereum:0, solana:0, dogecoin:0};
const SYM  = {bitcoin:'BTC', ethereum:'ETH', solana:'SOL', dogecoin:'DOGE'};
const NAME = {bitcoin:'BITCOIN / USD', ethereum:'ETHEREUM / USD', solana:'SOLANA / USD', dogecoin:'DOGECOIN / USD'};
const FULL = {bitcoin:'Bitcoin', ethereum:'Ethereum', solana:'Solana', dogecoin:'Dogecoin'};
const money = new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'});
const fmt6  = n => n.toLocaleString('en-US',{maximumFractionDigits:6});

let usdBalance = <?php echo $user_usd; ?>;
let holdings = <?php echo empty($user_holdings) ? '{}' : json_encode($user_holdings); ?>;
let avgPrices = <?php echo empty($avg_prices) ? '{}' : json_encode($avg_prices); ?>; 

['bitcoin', 'ethereum', 'solana', 'dogecoin'].forEach(c => {
    if (!holdings[c]) holdings[c] = 0;
});

function switchTab(tab) {
    document.getElementById('loginForm').style.display    = tab==='login' ? 'flex' : 'none';
    document.getElementById('registerForm').style.display = tab==='register' ? 'flex' : 'none';
    document.getElementById('tabLogin').classList.toggle('active', tab==='login');
    document.getElementById('tabRegister').classList.toggle('active', tab==='register');
}

function setAuthMsg(id, msg, type) {
    const el = document.getElementById(id+'Msg');
    el.textContent = msg; el.className = 'auth-msg '+type;
}

async function doLogin() {
    const u = document.getElementById('loginUser').value.trim();
    const p = document.getElementById('loginPass').value;
    if(!u || !p){ setAuthMsg('login','Please fill all fields.','error'); return; }
    
    const fd = new FormData(); fd.append('action','login'); fd.append('username',u); fd.append('password',p);
    try {
        const res = await fetch('auth.php',{method:'POST',body:fd});
        const d = await res.json();
        if(d.success) location.reload(); else setAuthMsg('login',d.message,'error');
    } catch(e) { setAuthMsg('login','Server error.','error'); }
}

async function doRegister() {
    const u = document.getElementById('regUser').value.trim();
    const e = document.getElementById('regEmail').value.trim();
    const p = document.getElementById('regPass').value;
    if(!u || !e || !p){ setAuthMsg('register','Please fill all fields.','error'); return; }
    
    const fd = new FormData(); fd.append('action','register'); fd.append('username',u); fd.append('email',e); fd.append('password',p);
    try {
        const res = await fetch('auth.php',{method:'POST',body:fd});
        const d = await res.json();
        if(d.success) location.reload(); else setAuthMsg('register',d.message,'error');
    } catch(e) { setAuthMsg('register','Server error.','error'); }
}

async function doLogout() {
    const fd = new FormData(); fd.append('action','logout');
    await fetch('auth.php',{method:'POST',body:fd}); 
    location.reload();
}

(async()=>{
    const fd = new FormData(); fd.append('action','check');
    try {
        const res = await fetch('auth.php',{method:'POST',body:fd});
        const d = await res.json();
        if (!d.loggedIn) {
            document.getElementById('authOverlay').classList.remove('hidden');
        } else {
            document.getElementById('authOverlay').classList.add('hidden');
            document.getElementById('sidebarUsername').textContent = d.username;
            document.getElementById('userAvatar').textContent = d.username.charAt(0).toUpperCase();
        }
    } catch(e) {}
})();

setInterval(() => {
    document.getElementById('liveTime').textContent = new Date().toLocaleTimeString('en-US',{hour12:false});
}, 1000);

function animateVal(id, newVal, fmtFn) {
    const el = document.getElementById(id);
    const oldVal = parseFloat(el.dataset.raw)||0;
    const t0 = performance.now(), dur = 500;
    (function step(now){
        const p = Math.min((now-t0)/dur,1), e = 1-Math.pow(1-p,3);
        el.textContent = fmtFn(oldVal+(newVal-oldVal)*e);
        p < 1 ? requestAnimationFrame(step) : (el.dataset.raw=newVal, el.textContent=fmtFn(newVal));
    })(t0);
}

function flashCard(id, type) {
    const c = document.getElementById(id);
    c.classList.remove('flash-green','flash-red');
    void c.offsetWidth;
    c.classList.add(type==='green'?'flash-green':'flash-red');
    setTimeout(()=>c.classList.remove('flash-green','flash-red'),700);
}

function flashBtn(id) {
    const b = document.getElementById(id);
    b.classList.remove('flash'); void b.offsetWidth; b.classList.add('flash');
    setTimeout(()=>b.classList.remove('flash'),400);
}

function setMsg(msg, type) {
    const el = document.getElementById('tradeMessage');
    el.textContent = msg; el.className = 'msg-box '+type;
}

window.onload = () => { loadData(); renderHoldings(); };

async function loadData() {
    const coin = document.getElementById('cryptoSelect').value;
    document.getElementById('assetName').textContent = NAME[coin];
    document.getElementById('price').textContent = 'Loading...';
    document.getElementById('price').className = 'chart-price neutral';

    const sk = document.getElementById('chartSkeleton');
    const cv = document.getElementById('priceChart');
    sk.classList.add('show'); cv.style.opacity = '0';

    try {
        const res  = await fetch(`https://api.coingecko.com/api/v3/coins/${coin}/market_chart?vs_currency=usd&days=${currentPeriod}`);
        const data = await res.json();
        if (!data.prices) throw new Error('No data');

        const pArr = data.prices.map(p => p[1]);
        const vArr = (data.total_volumes||[]).map(v => v[1]);
        const tArr = data.prices.map(p => {
            const d = new Date(p[0]);
            return currentPeriod <= 1 ? d.toLocaleTimeString() : d.toLocaleDateString();
        });

        const maPeriod = 7;
        const maArr = [];
        for (let i = 0; i < pArr.length; i++) {
            if (i < maPeriod - 1) {
                maArr.push(null); 
            } else {
                let sum = 0;
                for (let j = 0; j < maPeriod; j++) sum += pArr[i - j];
                maArr.push(sum / maPeriod);
            }
        }

        currentPrice = pArr[pArr.length-1];
        coinPrices[coin] = currentPrice;
        const change = ((pArr[pArr.length-1]-pArr[0])/pArr[0])*100;
        const isUp = change >= 0;

        const priceEl = document.getElementById('price');
        priceEl.textContent = money.format(currentPrice);
        priceEl.className = 'chart-price '+(isUp?'up':'down');

        const chEl = document.getElementById('priceChange');
        chEl.textContent = (isUp?'▲ +':'▼ ')+Math.abs(change).toFixed(2)+'% ('+currentPeriod+'d)';
        chEl.className = 'chart-change '+(isUp?'up':'down');

        updateStats(pArr);
        updateWallet();
        renderChart(tArr, pArr, vArr, isUp, maArr); 
        buildTicker(coin, pArr);
        renderHoldings();

    } catch(err) {
        document.getElementById('price').textContent = 'API Error';
    } finally {
        sk.classList.remove('show');
        cv.style.opacity = '1';
    }
}

function renderChart(labels, data, volumes, isUp, maData) {
    const ctx = document.getElementById('priceChart').getContext('2d');
    const color = isUp ? '#10b981' : '#ef4444'; 
    const rgb = isUp ? '16,185,129' : '239,68,68';
    const grad = ctx.createLinearGradient(0,0,0,280);
    grad.addColorStop(0,`rgba(${rgb},0.15)`);
    grad.addColorStop(1,`rgba(${rgb},0.0)`);

    if(chart) chart.destroy();
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { type:'line', label:'Price', data, borderColor:color, borderWidth:2, backgroundColor:grad, fill:true, pointRadius:0, tension:0.2, yAxisID:'price', order:2 },
                { type:'line', label:'MA(7)', data:maData, borderColor:'#f59e0b', borderWidth:1.5, borderDash:[5,5], fill:false, pointRadius:0, tension:0.4, yAxisID:'price', order:1 },
                { type:'bar', label:'Volume', data:volumes, backgroundColor:'rgba(0,0,0,0.03)', yAxisID:'volume', order:3 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: true, align:'end', labels:{color:'#64748b', boxWidth:6} } },
            scales: {
                x: { display: false },
                price: { position: 'right', grid: {color:'rgba(0,0,0,0.04)'}, ticks: {color:'#64748b'} },
                volume: { position: 'left', display: false }
            }
        }
    });
}

function setPeriod(btn, days) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentPeriod = days; 
    loadData();
}

function updateWallet(action) {
    const coin = document.getElementById('cryptoSelect').value;
    const held = holdings[coin];
    const portfolio = usdBalance + Object.keys(holdings).reduce((s,k) => s + holdings[k]*(coinPrices[k]||0), 0);

    animateVal('usdBalance', usdBalance, v=>money.format(v));
    animateVal('cryptoBalance', held, v=>fmt6(v));
    animateVal('portfolioValue', portfolio, v=>money.format(v));

    if(action==='buy')  { flashCard('cardUsd','red'); flashCard('cardCrypto','green'); }
    if(action==='sell') { flashCard('cardUsd','green'); flashCard('cardCrypto','red'); }
}

function updateStats(pArr) {
    document.getElementById('maxPrice').textContent = money.format(Math.max(...pArr));
    document.getElementById('minPrice').textContent = money.format(Math.min(...pArr));
    const change = ((pArr[pArr.length-1]-pArr[0])/pArr[0])*100;
    const el = document.getElementById('percentChange');
    el.textContent = (change>=0?'+':'') + change.toFixed(2) + '%';
    el.style.color = change>=0 ? 'var(--green)' : 'var(--red)';
}

function renderHoldings() {
    document.getElementById('walletSection').innerHTML = ['bitcoin','ethereum','solana','dogecoin'].map(c => {
        const amt = holdings[c] || 0, usd = amt * (coinPrices[c] || 0), has = amt > 0;
        let plHtml = '';
        if (has && avgPrices[c]) {
            const avgP = avgPrices[c], plUsd = usd - (amt * avgP), plPct = (plUsd / (amt * avgP)) * 100;
            const isP = plUsd >= 0, col = isP ? 'var(--green)' : 'var(--red)', sign = isP ? '+' : '';
            plHtml = `<div style="font-size:11px; font-family:var(--mono); color:${col}; margin-top:5px; font-weight:bold;">P/L: ${sign}${money.format(plUsd)}</div>`;
        }
        return `<div class="holding-card ${has?'active':''}">
            <div class="holding-top"><span class="holding-sym">${SYM[c]}</span><span class="holding-dot ${has?'on':''}"></span></div>
            <div class="holding-name">${FULL[c]}</div>
            <div class="holding-amount">${fmt6(amt)}</div>
            <div class="holding-usd">${has ? '≈ '+money.format(usd) : 'No holdings'}</div>
            ${plHtml}
        </div>`;
    }).join('');
    renderPieChart();
}

function renderPieChart() {
    const ctx = document.getElementById('portfolioPieChart').getContext('2d');
    const labels = [], data = [], colors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'];
    ['bitcoin','ethereum','solana','dogecoin'].forEach(c => {
        const val = (holdings[c] || 0) * (coinPrices[c] || 0);
        if (val > 0) { labels.push(SYM[c]); data.push(val); }
    });
    if (data.length === 0) { labels.push('Empty'); data.push(1); colors.unshift('#f1f5f9'); }
    if (pieChart) pieChart.destroy();
    pieChart = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'right', labels:{color:'#64748b', boxWidth:10} } } }
    });
}

async function handleTrade(type) {
    const coin = document.getElementById('cryptoSelect').value;
    const usd = parseFloat(document.getElementById('tradeAmount').value);
    const limitPrice = parseFloat(document.getElementById('limitPrice').value);
    
    if(!usd || usd <= 0) { setMsg('Invalid amount.', 'error'); return; }

    if (limitPrice && limitPrice > 0) {
        const fd = new FormData(); fd.append('action', 'create_order'); fd.append('type', type); fd.append('coin', coin); fd.append('amount_usd', usd); fd.append('target_price', limitPrice);
        try {
            const res = await fetch('limit.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.success) { setMsg(data.message, 'success'); setTimeout(() => location.reload(), 1500); } 
            else setMsg(data.message, 'error');
        } catch(e) { setMsg('Server error.', 'error'); }
        return;
    }

    if (type === 'buy') buyCrypto(coin, usd);
    if (type === 'sell') sellCrypto(coin, usd);
}

async function buyCrypto(coin, usd) {
    if(usd > usdBalance) { setMsg('Insufficient USD funds.','error'); return; }
    const fd = new FormData(); fd.append('action', 'buy'); fd.append('coin', coin); fd.append('amount_usd', usd); fd.append('price', currentPrice);
    try {
        const res = await fetch('trade.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const bought = usd / currentPrice;
            holdings[coin] += bought; usdBalance -= usd;
            flashBtn('btnBuy'); setMsg(`Bought ${fmt6(bought)} ${SYM[coin]}`, 'success');
            logTrade('buy', coin, usd, currentPrice, bought);
            updateWallet('buy'); renderHoldings();
        } else setMsg(data.message, 'error');
    } catch (e) { setMsg('Server error.', 'error'); }
}

async function sellCrypto(coin, usd) {
    const sellAmount = usd / currentPrice;
    if(sellAmount > holdings[coin]) { setMsg('Insufficient crypto balance.','error'); return; }
    const fd = new FormData(); fd.append('action', 'sell'); fd.append('coin', coin); fd.append('amount_usd', usd); fd.append('price', currentPrice);
    try {
        const res = await fetch('trade.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            holdings[coin] -= sellAmount; usdBalance += usd;
            flashBtn('btnSell'); setMsg(`Sold ${fmt6(sellAmount)} ${SYM[coin]}`, 'success');
            logTrade('sell', coin, usd, currentPrice, sellAmount);
            updateWallet('sell'); renderHoldings();
        } else setMsg(data.message, 'error');
    } catch (e) { setMsg('Server error.', 'error'); }
}

function setPercent(pct){ document.getElementById('tradeAmount').value = ((usdBalance*pct)/100).toFixed(2); }

function calculateProfit(){
    const invest = parseFloat(document.getElementById('simAmount').value);
    const change = parseFloat(document.getElementById('percentChange').textContent);
    if(!invest) return;
    const profit = invest * (change/100), total = invest + profit;
    document.getElementById('simResult').innerHTML = `Result: <b>${money.format(total)}</b> &nbsp;(${profit>=0?'+':''}${money.format(profit)})`;
}

function logTrade(type, coin, usd, price, coins){
    tradeHistory.unshift({time:new Date().toLocaleTimeString('en-US',{hour12:false}), type, coin, usd, price, coins});
    if(tradeHistory.length>10) tradeHistory.pop();
    const empty = document.getElementById('historyEmpty'), table = document.getElementById('historyTable');
    empty.style.display='none'; table.style.display='table';
    document.getElementById('historyBody').innerHTML = tradeHistory.map((t,i)=>`
        <tr>
            <td style="color:var(--muted)">${t.time}</td>
            <td><span class="badge ${t.type}">${t.type.toUpperCase()}</span></td>
            <td style="color:var(--blue);font-weight:700">${SYM[t.coin]}</td>
            <td>${money.format(t.usd)}</td>
            <td style="color:var(--muted)">${money.format(t.price)}</td>
            <td>${fmt6(t.coins)}</td>
        </tr>`).join('');
}

function buildTicker(activeCoin, pArr){
    const change = ((pArr[pArr.length-1]-pArr[0])/pArr[0]*100).toFixed(2);
    const isUp = parseFloat(change) >= 0;
    let html = ['bitcoin','ethereum','solana','dogecoin'].map(c => {
        const a = c === activeCoin;
        const val = a ? `<span class="${isUp?'t-up':'t-down'}">${money.format(currentPrice)} ${isUp?'▲+':'▼'}${Math.abs(change)}%</span>` : `<span class="t-muted">—</span>`;
        return `<span class="ticker-item"><span class="t-name">${SYM[c]}</span>${val}</span>`;
    }).join('');
    document.getElementById('tickerInner').innerHTML = html + html;
}

setInterval(async () => {
    if (Object.keys(coinPrices).length === 0 || coinPrices.bitcoin === 0) return;
    const fd = new FormData(); fd.append('action', 'check_orders'); fd.append('prices', JSON.stringify(coinPrices));
    try {
        const res = await fetch('limit.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success && data.executed > 0) location.reload(); 
    } catch(e) {}
}, 15000);

function scrollToSection(id, btn) {
    document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => link.classList.remove('active'));
    btn.classList.add('active');

    if (id === 'top') {
        document.querySelector('.main').scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        const el = document.getElementById(id);
        if (el) {
            const mainContainer = document.querySelector('.main');
            const targetPos = el.offsetTop - 20;
            mainContainer.scrollTo({ top: targetPos, behavior: 'smooth' });
        }
    }
}
</script>
</body>
</html>