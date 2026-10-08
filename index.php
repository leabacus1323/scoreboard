<?php
// ============================================================================
// CKC-SMA PANAGHIUSA 2026 TABULATION SYSTEM - PROFESSIONAL DASHBOARD EDITION
// Single-file InfinityFree-ready system
// ============================================================================

// --------------------------- DATABASE SETTINGS -----------------------------
const DB_HOST = 'sql103.infinityfree.com';
const DB_PORT = 3306;
const DB_NAME = 'if0_42700284_panaghiusa';
const DB_USER = 'if0_42700284';
const DB_PASS = 'm4aOs5Zs8Oa';

// --------------------------- ADMIN SETTINGS ---------------------------------
const ADMIN_USERNAME = 'admin';
// Password: violet2026
const ADMIN_PASSWORD_HASH = '$2y$12$nNT/jEBryMpgcwA3.15TpOzc3XW0mCW5WsBjB6e2Omr22xi4FvmpS';

// --------------------------- SITE SETTINGS ----------------------------------
const SITE_TITLE = 'CKC-SMA Panaghiusa 2026';
const AUTO_REFRESH_SECONDS = 30;

// --------------------------- BOOTSTRAP --------------------------------------
date_default_timezone_set('Asia/Manila');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function safeColor($value): string {
    $value = trim((string)$value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#64748b';
}

function ordinal(int $n): string {
    $m100 = $n % 100;
    if ($m100 >= 11 && $m100 <= 13) return $n . 'th';
    $m10 = $n % 10;
    if ($m10 === 1) return $n . 'st';
    if ($m10 === 2) return $n . 'nd';
    if ($m10 === 3) return $n . 'rd';
    return $n . 'th';
}

function redirectTo(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): array {
    $f = $_SESSION['flash'] ?? ['message' => '', 'type' => 'success'];
    unset($_SESSION['flash']);
    return $f;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $posted = $_POST['csrf'] ?? '';
    if (!is_string($posted) || !hash_equals($_SESSION['csrf'] ?? '', $posted)) {
        http_response_code(403);
        exit('Invalid request token. Please go back, refresh the page, and try again.');
    }
}

function num($value, int $dec = 2): string {
    return number_format((float)$value, $dec);
}

function progressStatusClass(float $pct): string {
    if ($pct >= 100) return 'complete';
    if ($pct > 0) return 'progress';
    return 'waiting';
}

// --------------------------- DATABASE CONNECTION ----------------------------
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    $technical = $e->getMessage();
    $hint = 'The website cannot currently connect to the InfinityFree MySQL database.';
    if (stripos($technical, 'access denied') !== false) {
        $hint = 'InfinityFree rejected the MySQL username or password. Re-copy the MySQL password from the hosting panel.';
    } elseif (stripos($technical, 'unknown database') !== false) {
        $hint = 'The configured database name does not exist or is not available yet.';
    } elseif (stripos($technical, 'timed out') !== false || stripos($technical, 'connection refused') !== false || stripos($technical, 'server has gone away') !== false) {
        $hint = 'The InfinityFree MySQL server is not reachable right now. Try again after the hosting account finishes processing.';
    }
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Database Connection Unavailable</title>
    <style>
    body{margin:0;background:#f4f7fb;font-family:system-ui,-apple-system,Segoe UI,sans-serif;color:#172033}
    .wrap{max-width:760px;margin:10vh auto;padding:20px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:24px;padding:28px;box-shadow:0 15px 40px rgba(15,23,42,.08)}
    .badge{display:inline-block;padding:6px 11px;border-radius:999px;background:#fff2f1;color:#b42318;font-weight:800;font-size:.82rem}
    h1{font-size:1.55rem;margin:15px 0 10px}
    p{color:#667085;line-height:1.65}
    .info{background:#f8fafc;border:1px solid #eaecf0;border-radius:16px;padding:15px;margin-top:18px;font-size:.95rem}
    .info b{color:#344054}.retry{display:inline-block;margin-top:18px;padding:12px 16px;border-radius:12px;background:#0b7a9b;color:#fff;text-decoration:none;font-weight:800}
    </style></head><body><div class="wrap"><div class="card">
    <span class="badge">Database unavailable</span><h1><?= h(SITE_TITLE) ?></h1>
    <p><?= h($hint) ?></p>
    <div class="info"><b>Configured database:</b> <?= h(DB_NAME) ?><br><b>MySQL host:</b> <?= h(DB_HOST) ?></div>
    <a class="retry" href="index.php">Try Again</a>
    </div></div></body></html>
    <?php
    exit;
}

// --------------------------- AUTO-SETUP TABLES ------------------------------
try {
    $db->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        color CHAR(7) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        weight DECIMAL(7,2) NOT NULL DEFAULT 10.00,
        max_score DECIMAL(10,2) NOT NULL DEFAULT 100.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS scores (
        event_id INT NOT NULL,
        team_id INT NOT NULL,
        score DECIMAL(10,2) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (event_id, team_id),
        CONSTRAINT fk_scores_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        CONSTRAINT fk_scores_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $teamCount = (int)$db->query('SELECT COUNT(*) FROM teams')->fetchColumn();
    if ($teamCount === 0) {
        $seed = [
            ['Group 1 Sky Blue', '#76b7ff'],
            ['Group 2 Yellow', '#f6c945'],
            ['Group 3 Red', '#ef4444'],
            ['Group 4 Orange', '#fb923c'],
            ['Group 5 Peach', '#fdba74'],
            ['Group 6 Violet', '#8b5cf6'],
            ['Group 7 Black', '#1f2937'],
            ['Group 8 Army Green', '#6b8e23'],
        ];
        $ins = $db->prepare('INSERT INTO teams (name, color) VALUES (?, ?)');
        foreach ($seed as $team) $ins->execute($team);
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('<h2>Database setup error</h2><p>The connection works, but the required tables could not be prepared. Open phpMyAdmin and confirm that this database account can create tables.</p>');
}

// --------------------------- AUTHENTICATION ---------------------------------
$page = $_GET['p'] ?? 'dashboard';
$admin = !empty($_SESSION['admin']);

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirectTo('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    verifyCsrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (hash_equals(ADMIN_USERNAME, $username) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_username'] = ADMIN_USERNAME;
        flash('Welcome. Administrator access granted.');
        redirectTo('index.php?p=admin');
    }
    flash('Invalid administrator username or password.', 'error');
    redirectTo('index.php?p=login');
}

$admin = !empty($_SESSION['admin']);
if (in_array($page, ['admin', 'events', 'scores'], true) && !$admin) {
    $page = 'login';
}

// --------------------------- ADMIN ACTIONS ----------------------------------
if ($admin && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    verifyCsrf();

    if (isset($_POST['add_event'])) {
        $name = trim((string)($_POST['name'] ?? ''));
        $weight = (float)($_POST['weight'] ?? 0);
        $maxScore = (float)($_POST['max_score'] ?? 0);
        if ($name === '' || $weight < 0 || $maxScore <= 0) {
            flash('Enter a valid event name, weight, and maximum score.', 'error');
        } else {
            $st = $db->prepare('INSERT INTO events (name, weight, max_score) VALUES (?, ?, ?)');
            $st->execute([$name, $weight, $maxScore]);
            flash('Event created successfully.');
        }
        redirectTo('index.php?p=events');
    }

    if (isset($_POST['update_event'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $weight = (float)($_POST['weight'] ?? 0);
        $maxScore = (float)($_POST['max_score'] ?? 0);
        if ($id <= 0 || $name === '' || $weight < 0 || $maxScore <= 0) {
            flash('Enter valid event information.', 'error');
        } else {
            $st = $db->prepare('UPDATE events SET name=?, weight=?, max_score=? WHERE id=?');
            $st->execute([$name, $weight, $maxScore, $id]);
            flash('Event updated successfully.');
        }
        redirectTo('index.php?p=events');
    }

    if (isset($_POST['delete_event'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $st = $db->prepare('DELETE FROM events WHERE id=?');
            $st->execute([$id]);
            flash('Event and its recorded scores were deleted.');
        }
        redirectTo('index.php?p=events');
    }

    if (isset($_POST['save_scores'])) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $st = $db->prepare('SELECT max_score FROM events WHERE id=?');
        $st->execute([$eventId]);
        $maxScore = $st->fetchColumn();
        if ($maxScore === false) {
            flash('Selected event was not found.', 'error');
            redirectTo('index.php?p=scores');
        }
        $maxScore = (float)$maxScore;
        $save = $db->prepare('INSERT INTO scores (event_id, team_id, score) VALUES (?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)');
        $delete = $db->prepare('DELETE FROM scores WHERE event_id=? AND team_id=?');
        foreach (($_POST['score'] ?? []) as $teamId => $value) {
            $teamId = (int)$teamId;
            $value = trim((string)$value);
            if ($value === '') {
                $delete->execute([$eventId, $teamId]);
                continue;
            }
            $score = (float)$value;
            if ($score < 0) $score = 0;
            if ($score > $maxScore) $score = $maxScore;
            $save->execute([$eventId, $teamId, $score]);
        }
        flash('Scores saved successfully.');
        redirectTo('index.php?p=scores&e=' . $eventId);
    }
}

// --------------------------- LOAD DASHBOARD DATA ----------------------------
$teams = $db->query('SELECT id, name, color FROM teams ORDER BY id')->fetchAll();
$events = $db->query('SELECT id, name, weight, max_score FROM events ORDER BY id')->fetchAll();

$raw = [];
$recordedScores = 0;
foreach ($db->query('SELECT event_id, team_id, score FROM scores') as $s) {
    $raw[(int)$s['team_id']][(int)$s['event_id']] = (float)$s['score'];
    $recordedScores++;
}

$totalWeight = 0.0;
foreach ($events as $e) $totalWeight += (float)$e['weight'];
$totalPossible = count($teams) * count($events);
$overallCompletion = $totalPossible > 0 ? ($recordedScores / $totalPossible) * 100 : 0;

$rows = [];
foreach ($teams as $team) {
    $total = 0.0;
    $per = [];
    $scored = 0;
    foreach ($events as $event) {
        $tid = (int)$team['id'];
        $eid = (int)$event['id'];
        $score = $raw[$tid][$eid] ?? null;
        if ($score === null) {
            $per[$eid] = null;
        } else {
            $scored++;
            $max = max(0.01, (float)$event['max_score']);
            $points = ($score / $max) * (float)$event['weight'];
            $per[$eid] = $points;
            $total += $points;
        }
    }
    $eventCount = count($events);
    if ($eventCount === 0 || $scored === 0) {
        $status = 'No scores yet'; $statusClass = 'waiting';
    } elseif ($scored >= $eventCount) {
        $status = 'Complete'; $statusClass = 'complete';
    } else {
        $status = 'In progress'; $statusClass = 'progress';
    }
    $rows[] = [
        'team' => $team,
        'per' => $per,
        'total' => $total,
        'scored' => $scored,
        'completion' => $eventCount > 0 ? ($scored / $eventCount) * 100 : 0,
        'status' => $status,
        'status_class' => $statusClass,
    ];
}

usort($rows, function ($a, $b) {
    if (abs($b['total'] - $a['total']) > 0.000001) return $b['total'] <=> $a['total'];
    return strcasecmp($a['team']['name'], $b['team']['name']);
});

$rank = 0;
$prev = null;
foreach ($rows as $i => &$r) {
    if ($prev === null || abs($r['total'] - $prev) > 0.0001) $rank = $i + 1;
    $r['rank'] = $rank;
    $prev = $r['total'];
}
unset($r);

$eventRecorded = [];
$eventAveragePct = [];
foreach ($events as $e) {
    $eventRecorded[(int)$e['id']] = 0;
    $eventAveragePct[(int)$e['id']] = ['sum' => 0.0, 'count' => 0];
}
foreach ($raw as $teamScores) {
    foreach ($teamScores as $eid => $score) {
        if (isset($eventRecorded[(int)$eid])) $eventRecorded[(int)$eid]++;
        foreach ($events as $ev) {
            if ((int)$ev['id'] === (int)$eid) {
                $pct = ((float)$score / max(0.01, (float)$ev['max_score'])) * 100;
                $eventAveragePct[(int)$eid]['sum'] += $pct;
                $eventAveragePct[(int)$eid]['count']++;
                break;
            }
        }
    }
}

$leader = $rows[0] ?? null;
$runnerUp = $rows[1] ?? null;
$leadMargin = ($leader && $runnerUp) ? max(0, $leader['total'] - $runnerUp['total']) : 0;
$completeTeams = 0;
foreach ($rows as $r) {
    if ($r['status_class'] === 'complete') $completeTeams++;
}

$chartMaxTotal = 0.0;
foreach ($rows as $r) {
    if ($r['total'] > $chartMaxTotal) $chartMaxTotal = $r['total'];
}
$chartMaxTotal = max($chartMaxTotal, 1);

$eventChartRows = [];
foreach ($events as $e) {
    $eid = (int)$e['id'];
    $encoded = $eventRecorded[$eid] ?? 0;
    $encodedPct = count($teams) > 0 ? ($encoded / count($teams)) * 100 : 0;
    $avgPct = ($eventAveragePct[$eid]['count'] ?? 0) > 0 ? ($eventAveragePct[$eid]['sum'] / $eventAveragePct[$eid]['count']) : 0;
    $eventChartRows[] = [
        'name' => $e['name'],
        'weight' => (float)$e['weight'],
        'encoded' => $encoded,
        'encoded_pct' => $encodedPct,
        'avg_pct' => $avgPct,
    ];
}

$flash = getFlash();
$csrf = csrfToken();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#182746">
<title><?= h(SITE_TITLE) ?> - Dashboard</title>
<?php if ($page === 'dashboard' && AUTO_REFRESH_SECONDS > 0): ?>
<meta http-equiv="refresh" content="<?= AUTO_REFRESH_SECONDS ?>">
<?php endif; ?>
<style>
:root{
    --ink:#14213d; --ink2:#344054; --muted:#667085; --muted2:#98a2b3;
    --line:#e4e7ec; --line2:#eef2f6; --bg:#f5f7fb; --card:#ffffff;
    --brand:#0b7a9b; --brand-dark:#075f78; --brand-soft:#e6f6fb;
    --success:#067647; --success-bg:#ecfdf3;
    --warning:#b54708; --warning-bg:#fff6ed;
    --neutral:#344054; --neutral-bg:#f2f4f7;
    --danger:#b42318; --danger-bg:#fef3f2;
    --shadow:0 10px 28px rgba(16,24,40,.06);
    --shadow-lg:0 18px 48px rgba(16,24,40,.10);
    --radius:20px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:radial-gradient(circle at top right,#eef7fb 0,#f7f9fc 35%,#f5f7fb 100%);color:var(--ink);font:15px/1.5 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
a{color:inherit}
.wrap{max-width:1240px;margin:auto;padding-left:18px;padding-right:18px}
header{background:linear-gradient(135deg,#162544 0%,#0b536e 50%,#0d8ab0 100%);color:#fff;position:relative;overflow:hidden}
header:before,header:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.08)}
header:before{width:260px;height:260px;right:-70px;top:-120px} header:after{width:180px;height:180px;left:-60px;bottom:-80px}
.hero{display:grid;grid-template-columns:1.8fr .95fr;gap:18px;padding:28px 0 24px;position:relative;z-index:1}
.hero h1{margin:0;font-size:clamp(1.55rem,4.1vw,2.25rem);line-height:1.1}
.hero p{margin:10px 0 0;color:rgba(255,255,255,.84);max-width:720px}
.hero-chip-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.chip{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;background:rgba(255,255,255,.13);backdrop-filter:blur(8px);font-size:.9rem;font-weight:700}.chip .pulse{width:9px;height:9px;border-radius:50%;background:#32d583;box-shadow:0 0 0 6px rgba(50,213,131,.2)}
.hero-side{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-content:start}
.hero-stat{background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.14);border-radius:18px;padding:15px 16px;min-height:92px}.hero-stat .k{font-size:.77rem;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.75);font-weight:800}.hero-stat .v{font-size:1.5rem;font-weight:900;margin-top:4px}.hero-stat .n{font-size:.84rem;color:rgba(255,255,255,.84)}
nav{background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20}.nav{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 0}.nav a{padding:9px 12px;border-radius:12px;text-decoration:none;font-weight:800;color:var(--ink2)}.nav a:hover{background:#eef4f8}.nav a.on{background:linear-gradient(135deg,var(--brand),#0d8ab0);color:#fff;box-shadow:0 8px 18px rgba(11,122,155,.18)}.spacer{flex:1}.adminname{font-size:.88rem;color:var(--muted)}
main{padding:22px 0 48px}.title{font-size:1.42rem;margin:0 0 3px}.subtitle{margin:0 0 20px;color:var(--muted)}
.msg{padding:12px 14px;border-radius:14px;margin-bottom:16px;border:1px solid}.msg.success{background:var(--success-bg);border-color:#abefc6;color:#05603a}.msg.error{background:var(--danger-bg);border-color:#fecdca;color:var(--danger)}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.card,.panel,.stat{background:rgba(255,255,255,.96);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}.stat{padding:18px;position:relative;overflow:hidden}.stat:after{content:"";position:absolute;right:-24px;bottom:-28px;width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#f4fbfe,#edf7fb)}.stat .label{font-size:.79rem;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.05em;position:relative;z-index:1}.stat .value{font-size:1.78rem;font-weight:900;margin-top:5px;position:relative;z-index:1}.stat .note{font-size:.84rem;color:var(--muted);position:relative;z-index:1}
.grid2{display:grid;grid-template-columns:1.2fr .8fr;gap:16px;margin-bottom:22px}.grid2.equal{grid-template-columns:1fr 1fr}.panel{overflow:hidden}.pad{padding:18px}.sectionhead{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;padding:18px 18px 0 18px}.sectionhead h2{margin:0;font-size:1.08rem}.sectionhead p,.sectionhead span{margin:4px 0 0;color:var(--muted);font-size:.84rem}
.quickgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.mini{border:1px solid var(--line2);background:#fbfcfe;border-radius:16px;padding:14px}.mini .k{font-size:.76rem;font-weight:800;text-transform:uppercase;color:var(--muted);letter-spacing:.05em}.mini .v{font-size:1.22rem;font-weight:900;margin-top:4px}.mini .n{font-size:.84rem;color:var(--muted)}
.groups{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.team{overflow:hidden}.strip{height:8px;background:var(--team)}.teambody{padding:16px}.teamtop{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}.teamname{font-size:1.03rem;font-weight:850;display:flex;align-items:center;gap:8px}.swatch{width:14px;height:14px;border-radius:50%;background:var(--team);border:1px solid rgba(0,0,0,.14);flex:none}.rankbadge{font-size:.77rem;font-weight:800;padding:5px 9px;border-radius:999px;background:#f2f4f7}.score{font-size:1.72rem;font-weight:900;line-height:1;margin:18px 0 5px}.score small{font-size:.75rem;color:var(--muted);font-weight:700}.statusline{display:flex;justify-content:space-between;align-items:center;gap:10px}.status{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:.75rem;font-weight:800}.complete{background:var(--success-bg);color:#05603a}.progress{background:var(--warning-bg);color:#93370d}.waiting{background:var(--neutral-bg);color:#344054}.progressbox{margin-top:10px}.progresslabel{display:flex;justify-content:space-between;color:var(--muted);font-size:.78rem;margin-bottom:5px}.bar{height:8px;background:#edf1f6;border-radius:999px;overflow:hidden}.bar i{display:block;height:100%;width:var(--pct);background:linear-gradient(90deg,var(--team),rgba(11,122,155,.95));border-radius:999px}
.legend{display:flex;gap:14px;flex-wrap:wrap;color:var(--muted);font-size:.83rem;margin-top:12px}.legend span{display:inline-flex;align-items:center;gap:7px}.legend i{width:12px;height:12px;border-radius:4px;display:inline-block}
.chartbox{padding:8px 18px 18px}.vchart{display:flex;gap:14px;align-items:flex-end;height:280px;padding:12px 0 6px;border-bottom:1px dashed #d9e1ea;overflow-x:auto}.vcol{min-width:88px;display:flex;flex-direction:column;align-items:center;gap:10px}.vcol .barwrap{height:210px;width:42px;display:flex;align-items:flex-end}.vcol .bar2{width:100%;height:var(--h);background:linear-gradient(180deg,var(--c),color-mix(in srgb,var(--c) 76%, white));border-radius:14px 14px 6px 6px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.25), 0 10px 18px rgba(15,23,42,.08)}.vcol .topv{font-size:.8rem;color:var(--ink2);font-weight:800}.vcol .name{font-size:.8rem;text-align:center;color:var(--ink2);font-weight:700;line-height:1.2}.hrows{display:grid;gap:12px;padding:10px 18px 18px}.hrow .top{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:6px}.hrow .ttl{font-weight:800;color:var(--ink2)}.hrow .meta{font-size:.82rem;color:var(--muted)}.dualbar{height:11px;background:#edf1f6;border-radius:999px;overflow:hidden;position:relative}.dualbar .a{position:absolute;inset:0 auto 0 0;width:var(--w1);background:linear-gradient(90deg,#0b7a9b,#32b0d0)}.dualbar .b{position:absolute;inset:2px auto 2px 0;width:var(--w2);background:rgba(245,166,35,.88);border-radius:999px}
.ring-wrap{display:flex;align-items:center;justify-content:center;padding:14px 0 4px}.ring{--deg:0deg;width:190px;height:190px;border-radius:50%;background:conic-gradient(#0b7a9b 0deg var(--deg), #e8eff5 var(--deg) 360deg);display:grid;place-items:center;box-shadow:inset 0 0 0 1px rgba(15,23,42,.04)}.ring:before{content:"";width:135px;height:135px;border-radius:50%;background:#fff;box-shadow:var(--shadow)}.ringtext{position:absolute;text-align:center}.ringtext .big{font-size:2rem;font-weight:900}.ringtext .small{font-size:.84rem;color:var(--muted)}
.podium{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end;padding:10px 18px 18px}.pod{border-radius:18px 18px 12px 12px;padding:15px;text-align:center;border:1px solid var(--line)}.pod.rank1{background:linear-gradient(180deg,#fff7d8,#fffbeb);min-height:168px}.pod.rank2{background:linear-gradient(180deg,#f8fafc,#f2f4f7);min-height:142px}.pod.rank3{background:linear-gradient(180deg,#fff4ea,#fff6ed);min-height:128px}.pod .r{font-size:.8rem;font-weight:800;color:var(--muted)}.pod .nm{font-weight:900;margin-top:6px}.pod .sc{font-size:1.32rem;font-weight:900;margin-top:5px}.pod .tag{display:inline-flex;padding:5px 10px;border-radius:999px;background:#fff;font-size:.75rem;font-weight:800;margin-top:10px}
.scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;background:#fff}th,td{padding:11px 13px;border-bottom:1px solid var(--line);text-align:left;white-space:nowrap}th{font-size:.76rem;text-transform:uppercase;letter-spacing:.04em;color:#475467;background:#f8fafc}td.n,th.n{text-align:right;font-variant-numeric:tabular-nums}tr:last-child td{border-bottom:0}.first td{background:#fffbeb;font-weight:750}.teamcell{display:flex;align-items:center;gap:8px;font-weight:700}
button,.btn,input,select{font:inherit}input,select{width:100%;padding:10px 11px;border:1px solid #d0d5dd;border-radius:11px;background:#fff}button,.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:0;border-radius:11px;background:linear-gradient(135deg,var(--brand),#0d8ab0);color:#fff;text-decoration:none;font-weight:800;cursor:pointer;box-shadow:0 10px 18px rgba(11,122,155,.16)}button:hover,.btn:hover{filter:brightness(.96)}button.danger{background:linear-gradient(135deg,#b42318,#d92d20)}button.secondary,.btn.secondary{background:#344054}.field{margin-top:14px}label{font-weight:750;color:#344054}.login{max-width:460px;margin:40px auto}.login .card{padding:26px}.login h2{margin:0 0 4px}.full{width:100%;margin-top:17px}.admincards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.action{padding:20px}.action h3{margin:0 0 5px}.action p{margin:0 0 15px;color:var(--muted)}
.eventgrid{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:9px;align-items:end}.eventlist{display:grid;gap:12px}.eventitem{padding:16px}.eventedit{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:9px;align-items:end}.actions{display:flex;gap:7px;flex-wrap:wrap}.scoreinput{width:115px}.muted{color:var(--muted)}.footer{text-align:center;color:var(--muted);font-size:.82rem;margin-top:20px}
.badge-soft{display:inline-flex;padding:5px 9px;border-radius:999px;background:var(--brand-soft);color:var(--brand);font-size:.75rem;font-weight:800}
@media(max-width:980px){.hero{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}.groups{grid-template-columns:repeat(2,1fr)}.grid2,.grid2.equal,.admincards{grid-template-columns:1fr}}
@media(max-width:680px){.wrap{padding-left:13px;padding-right:13px}.stats,.groups{grid-template-columns:1fr}.hero-side{grid-template-columns:1fr 1fr}.spacer,.adminname{display:none}.nav a{padding:8px 10px;font-size:.88rem}.eventgrid,.eventedit{grid-template-columns:1fr}.quickgrid,.podium{grid-template-columns:1fr}.ring{width:165px;height:165px}.ring:before{width:116px;height:116px}.vchart{height:250px}}
</style>
</head>
<body>
<header>
    <div class="wrap hero">
        <div>
            <h1><?= h(SITE_TITLE) ?> Professional Dashboard</h1>
           
            <div class="hero-chip-row">
                
                <span class="chip">Updated <?= h(date('M j, Y · g:i A')) ?></span>
               
            </div>
        </div>
        <div class="hero-side">
            <div class="hero-stat"><div class="k">Leading Group</div><div class="v"><?= h($leader['team']['name'] ?? '—') ?></div><div class="n"><?= $leader ? num($leader['total']) . ' weighted points' : 'No score data yet' ?></div></div>
            <div class="hero-stat"><div class="k">Lead Margin</div><div class="v"><?= num($leadMargin) ?></div><div class="n">Against current 2nd place</div></div>
            <div class="hero-stat"><div class="k">Events</div><div class="v"><?= count($events) ?></div><div class="n">Configured activities</div></div>
            <div class="hero-stat"><div class="k">Completion</div><div class="v"><?= number_format($overallCompletion, 0) ?>%</div><div class="n">Overall tabulation encoded</div></div>
        </div>
    </div>
</header>

<nav>
    <div class="wrap nav">
        <a href="index.php" class="<?= $page === 'dashboard' ? 'on' : '' ?>">Dashboard</a>
        <?php if ($admin): ?>
            <a href="?p=admin" class="<?= $page === 'admin' ? 'on' : '' ?>">Admin</a>
            <a href="?p=scores" class="<?= $page === 'scores' ? 'on' : '' ?>">Record Scores</a>
            <a href="?p=events" class="<?= $page === 'events' ? 'on' : '' ?>">Events</a>
            <span class="spacer"></span>
            <span class="adminname">Signed in as <?= h($_SESSION['admin_username'] ?? ADMIN_USERNAME) ?></span>
            <a href="?logout=1">Log out</a>
        <?php else: ?>
            <span class="spacer"></span>
            <a href="?p=login" class="<?= $page === 'login' ? 'on' : '' ?>">Admin Login</a>
        <?php endif; ?>
    </div>
</nav>

<main class="wrap">
<?php if ($flash['message']): ?>
    <div class="msg <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<?php if ($page === 'login' && !$admin): ?>
    <div class="login">
        <div class="card">
            <span class="badge-soft">Protected Area</span>
            <h2>Administrator Login</h2>
            <p class="subtitle" style="margin-bottom:0">Use the admin account to create events and record scores.</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <div class="field"><label>Username</label><input type="text" name="username" required></div>
                <div class="field"><label>Password</label><input type="password" name="password" required></div>
                <button class="full" type="submit" name="login">Log In</button>
            </form>
        </div>
    </div>

<?php elseif ($page === 'admin' && $admin): ?>
    <h2 class="title">Administrator Dashboard</h2>
    <p class="subtitle">Manage events and scores from one secured account.</p>

    <div class="stats">
        <div class="stat"><div class="label">Groups</div><div class="value"><?= count($teams) ?></div><div class="note">Registered color groups</div></div>
        <div class="stat"><div class="label">Events</div><div class="value"><?= count($events) ?></div><div class="note">Created events</div></div>
        <div class="stat"><div class="label">Scores Recorded</div><div class="value"><?= $recordedScores ?></div><div class="note">of <?= $totalPossible ?> expected</div></div>
        <div class="stat"><div class="label">Weight Allocation</div><div class="value"><?= num($totalWeight, 0) ?>%</div><div class="note"><?= abs($totalWeight - 100) < .001 ? 'Allocation complete' : 'Target total is 100%' ?></div></div>
    </div>

    <div class="admincards">
        <div class="card action"><h3>Create &amp; Manage Events</h3><p>Add activities, set maximum scores, and define the percentage weight of every event.</p><a class="btn" href="?p=events">Manage Events</a></div>
        <div class="card action"><h3>Record Group Scores</h3><p>Select an event and encode the score of each participating color group.</p><a class="btn" href="?p=scores">Record Scores</a></div>
    </div>

    <div class="grid2 equal" style="margin-top:16px">
        <div class="panel">
            <div class="sectionhead"><div><h2>Encoding Progress</h2><p>Overall and per-event encoding status</p></div><span><?= number_format($overallCompletion, 0) ?>% completed</span></div>
            <div class="hrows">
                <?php if (!$events): ?>
                    <div class="mini muted">No events yet. Create an event first.</div>
                <?php else: foreach ($eventChartRows as $ev): ?>
                    <div class="hrow">
                        <div class="top"><div class="ttl"><?= h($ev['name']) ?></div><div class="meta"><?= $ev['encoded'] ?> / <?= count($teams) ?> groups</div></div>
                        <div class="dualbar" style="--w1:<?= round($ev['encoded_pct'], 2) ?>%;--w2:<?= round($ev['avg_pct'], 2) ?>%"><i class="a"></i><i class="b"></i></div>
                        <div class="legend"><span><i style="background:#0b7a9b"></i> Encoding <?= number_format($ev['encoded_pct'], 0) ?>%</span><span><i style="background:#f5a623"></i> Average raw score <?= number_format($ev['avg_pct'], 0) ?>%</span></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="panel">
            <div class="sectionhead"><div><h2>Quick Insights</h2><p>Useful summaries for the tabulator</p></div></div>
            <div class="pad quickgrid">
                <div class="mini"><div class="k">Leading Group</div><div class="v"><?= h($leader['team']['name'] ?? '—') ?></div><div class="n"><?= $leader ? ordinal((int)$leader['rank']) . ' place' : 'No ranking yet' ?></div></div>
                <div class="mini"><div class="k">Runner-Up</div><div class="v"><?= h($runnerUp['team']['name'] ?? '—') ?></div><div class="n"><?= $runnerUp ? num($runnerUp['total']) . ' pts' : 'No ranking yet' ?></div></div>
                <div class="mini"><div class="k">Teams Complete</div><div class="v"><?= $completeTeams ?></div><div class="n">Fully encoded groups</div></div>
                <div class="mini"><div class="k">Last Refresh</div><div class="v"><?= h(date('g:i A')) ?></div><div class="n"><?= h(date('M j, Y')) ?></div></div>
            </div>
        </div>
    </div>

<?php elseif ($page === 'events' && $admin): ?>
    <h2 class="title">Manage Events</h2>
    <p class="subtitle">Create, edit, or delete events. Combined weights should normally total 100%.</p>
    <div class="stat" style="margin-bottom:15px"><div class="label">Current Total Weight</div><div class="value"><?= num($totalWeight) ?>%</div><div class="note"><?= abs($totalWeight - 100) < .001 ? 'Event weights total 100%.' : 'Adjust event weights until the total reaches 100%.' ?></div></div>

    <div class="panel pad" style="margin-bottom:16px">
        <form method="post" class="eventgrid">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <div><label>Event Name</label><input name="name" placeholder="e.g. Team Chant" required></div>
            <div><label>Weight %</label><input type="number" step="0.01" min="0" name="weight" value="10" required></div>
            <div><label>Max Score</label><input type="number" step="0.01" min="0.01" name="max_score" value="100" required></div>
            <div><button type="submit" name="add_event">Create Event</button></div>
        </form>
    </div>

    <div class="eventlist">
        <?php if (!$events): ?>
            <div class="card eventitem muted">No events have been created yet.</div>
        <?php else: foreach ($events as $e): ?>
            <div class="card eventitem">
                <form method="post" class="eventedit">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                    <div><label>Event Name</label><input name="name" value="<?= h($e['name']) ?>" required></div>
                    <div><label>Weight %</label><input type="number" step="0.01" min="0" name="weight" value="<?= h($e['weight']) ?>" required></div>
                    <div><label>Max Score</label><input type="number" step="0.01" min="0.01" name="max_score" value="<?= h($e['max_score']) ?>" required></div>
                    <div class="actions"><button type="submit" name="update_event">Save</button><button type="submit" name="delete_event" class="danger" onclick="return confirm('Delete this event and all of its scores?')">Delete</button></div>
                </form>
                <div class="note" style="margin-top:9px">Scores encoded: <?= $eventRecorded[(int)$e['id']] ?? 0 ?> / <?= count($teams) ?> groups</div>
            </div>
        <?php endforeach; endif; ?>
    </div>

<?php elseif ($page === 'scores' && $admin): ?>
    <?php $selectedId = (int)($_GET['e'] ?? ($events[0]['id'] ?? 0)); $selected = null; foreach ($events as $e) { if ((int)$e['id'] === $selectedId) { $selected = $e; break; } } ?>
    <h2 class="title">Record Scores</h2>
    <p class="subtitle">Choose an event and enter each group's score. Leave the field blank if it has not yet been recorded.</p>
    <?php if ($events): ?>
        <div class="panel pad" style="margin-bottom:15px">
            <form method="get">
                <input type="hidden" name="p" value="scores">
                <label for="ev">Select Event</label>
                <select id="ev" name="e" onchange="this.form.submit()" style="margin-top:6px;max-width:520px">
                    <?php foreach ($events as $e): ?>
                        <option value="<?= (int)$e['id'] ?>" <?= (int)$e['id'] === $selectedId ? 'selected' : '' ?>><?= h($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($selected): ?>
        <div class="panel">
            <div class="pad" style="border-bottom:1px solid var(--line)">
                <strong><?= h($selected['name']) ?></strong><br>
                <span class="muted">Maximum score: <?= num($selected['max_score']) ?> · Weight: <?= num($selected['weight']) ?>%</span>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="event_id" value="<?= $selectedId ?>">
                <div class="scroll">
                    <table>
                        <thead><tr><th>Group</th><th class="n">Score</th><th class="n">Weighted Points</th></tr></thead>
                        <tbody>
                        <?php foreach ($teams as $team): $tid = (int)$team['id']; $v = $raw[$tid][$selectedId] ?? ''; $wp = $v === '' ? null : ((float)$v / max(.01, (float)$selected['max_score'])) * (float)$selected['weight']; $c = safeColor($team['color']); ?>
                            <tr>
                                <td><div class="teamcell"><span class="swatch" style="--team:<?= h($c) ?>"></span><?= h($team['name']) ?></div></td>
                                <td class="n"><input class="scoreinput" type="number" step="0.01" min="0" max="<?= h($selected['max_score']) ?>" name="score[<?= $tid ?>]" value="<?= h($v) ?>"></td>
                                <td class="n"><?= $wp === null ? '—' : num($wp) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pad"><button type="submit" name="save_scores">Save Scores</button></div>
            </form>
        </div>
    <?php else: ?>
        <div class="panel pad">No events yet. <a href="?p=events">Create an event first.</a></div>
    <?php endif; ?>

<?php else: ?>
    <h2 class="title">Live Group Dashboard</h2>
    <p class="subtitle">Professional public dashboard showing rankings, chart-based analytics, and progress monitoring for all Panaghiusa color groups.</p>

    <div class="stats">
        <div class="stat"><div class="label">Groups</div><div class="value"><?= count($teams) ?></div><div class="note">Participating color groups</div></div>
        <div class="stat"><div class="label">Events</div><div class="value"><?= count($events) ?></div><div class="note">Included in tabulation</div></div>
        <div class="stat"><div class="label">Scores Recorded</div><div class="value"><?= $recordedScores ?></div><div class="note">of <?= $totalPossible ?> possible</div></div>
        <div class="stat"><div class="label">Tabulation Status</div><div class="value"><?= number_format($overallCompletion, 0) ?>%</div><div class="note">Overall encoding completion</div></div>
    </div>

    <div class="grid2">
        <div class="panel">
            <div class="sectionhead"><div><h2>Team Performance Chart</h2><p>Weighted totals of all color groups</p></div><span><?= count($rows) ?> groups ranked</span></div>
            <div class="chartbox">
                <div class="vchart">
                    <?php foreach ($rows as $r): $t = $r['team']; $c = safeColor($t['color']); $height = round(($r['total'] / $chartMaxTotal) * 100, 2); ?>
                        <div class="vcol">
                            <div class="topv"><?= num($r['total']) ?></div>
                            <div class="barwrap"><div class="bar2" style="--h:<?= max(6, $height) ?>%;--c:<?= h($c) ?>"></div></div>
                            <div class="name"><?= h($t['name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="legend"><span><i style="background:#0b7a9b"></i> Higher bars mean higher weighted total</span></div>
            </div>
        </div>

        <div class="panel">
            <div class="sectionhead"><div><h2>Overall Progress</h2><p>Encoding completion of all expected score entries</p></div></div>
            <div class="ring-wrap">
                <div class="ring" style="--deg:<?= round(($overallCompletion / 100) * 360, 2) ?>deg"></div>
                <div class="ringtext"><div class="big"><?= number_format($overallCompletion, 0) ?>%</div><div class="small">Completion</div></div>
            </div>
            <div class="pad quickgrid" style="padding-top:4px">
                <div class="mini"><div class="k">Leader</div><div class="v"><?= h($leader['team']['name'] ?? '—') ?></div><div class="n"><?= $leader ? num($leader['total']) . ' pts' : 'No data' ?></div></div>
                <div class="mini"><div class="k">Lead Margin</div><div class="v"><?= num($leadMargin) ?></div><div class="n">Difference from 2nd place</div></div>
                <div class="mini"><div class="k">Groups Complete</div><div class="v"><?= $completeTeams ?></div><div class="n">Fully encoded teams</div></div>
                <div class="mini"><div class="k">Updated</div><div class="v"><?= h(date('g:i A')) ?></div><div class="n"><?= h(date('M j, Y')) ?></div></div>
            </div>
        </div>
    </div>

    <div class="grid2 equal">
        <div class="panel">
            <div class="sectionhead"><div><h2>Top 3 Standings</h2><p>Current podium based on weighted score</p></div></div>
            <div class="podium">
                <?php $podRows = [$rows[1] ?? null, $rows[0] ?? null, $rows[2] ?? null]; $podClasses = ['rank2', 'rank1', 'rank3']; $podLabels = ['2nd', '1st', '3rd']; foreach ($podRows as $idx => $pr): ?>
                    <div class="pod <?= $podClasses[$idx] ?>">
                        <div class="r"><?= $podLabels[$idx] ?> Place</div>
                        <div class="nm"><?= h($pr['team']['name'] ?? '—') ?></div>
                        <div class="sc"><?= $pr ? num($pr['total']) : '0.00' ?></div>
                        <div class="tag"><?= $pr ? h($pr['status']) : 'No data' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel">
            <div class="sectionhead"><div><h2>Event Completion &amp; Average</h2><p>Blue = encoding progress, Gold = average raw score</p></div></div>
            <div class="hrows">
                <?php if (!$eventChartRows): ?>
                    <div class="mini muted">No events available yet.</div>
                <?php else: foreach ($eventChartRows as $ev): ?>
                    <div class="hrow">
                        <div class="top"><div class="ttl"><?= h($ev['name']) ?></div><div class="meta">Weight <?= num($ev['weight']) ?>%</div></div>
                        <div class="dualbar" style="--w1:<?= round($ev['encoded_pct'], 2) ?>%;--w2:<?= round($ev['avg_pct'], 2) ?>%"><i class="a"></i><i class="b"></i></div>
                        <div class="legend"><span><i style="background:#0b7a9b"></i> Encoded <?= number_format($ev['encoded_pct'], 0) ?>%</span><span><i style="background:#f5a623"></i> Average <?= number_format($ev['avg_pct'], 0) ?>%</span></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="sectionhead" style="padding-left:0;padding-right:0"><div><h2>Group Status Cards</h2><p>Snapshot of each group's standing, completion, and current weighted score</p></div><span>Updated <?= h(date('M j, Y g:i A')) ?></span></div>
    <div class="groups" style="margin-top:14px">
        <?php foreach ($rows as $r): $t = $r['team']; $c = safeColor($t['color']); $scorePct = $totalWeight > 0 ? min(100, max(0, ($r['total'] / $totalWeight) * 100)) : 0; ?>
            <div class="card team" style="--team:<?= h($c) ?>">
                <div class="strip"></div>
                <div class="teambody">
                    <div class="teamtop">
                        <div class="teamname"><span class="swatch"></span><?= h($t['name']) ?></div>
                        <span class="rankbadge"><?= ordinal((int)$r['rank']) ?></span>
                    </div>
                    <div class="score"><?= num($r['total']) ?> <small>weighted points</small></div>
                    <div class="statusline"><span class="status <?= h($r['status_class']) ?>"><?= h($r['status']) ?></span><span class="note"><?= $r['scored'] ?> / <?= count($events) ?> events</span></div>
                    <div class="progressbox"><div class="progresslabel"><span>Encoding progress</span><span><?= number_format($r['completion'], 0) ?>%</span></div><div class="bar" style="--pct:<?= round($r['completion'], 2) ?>%"><i></i></div></div>
                    <div class="progressbox"><div class="progresslabel"><span>Current weighted score</span><span><?= number_format($scorePct, 0) ?>%</span></div><div class="bar" style="--pct:<?= round($scorePct, 2) ?>%"><i></i></div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="sectionhead" style="padding-left:0;padding-right:0;margin-top:22px"><div><h2>Detailed Leaderboard</h2><p>Weighted scoring formula: score ÷ max score × event weight</p></div></div>
    <div class="panel scroll" style="margin-top:12px">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Group</th>
                    <?php foreach ($events as $e): ?>
                        <th class="n"><?= h($e['name']) ?><br><span class="note"><?= num($e['weight']) ?>%</span></th>
                    <?php endforeach; ?>
                    <th class="n">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="3" class="muted">No groups available.</td></tr>
                <?php else: foreach ($rows as $r): $t = $r['team']; $c = safeColor($t['color']); ?>
                    <tr class="<?= $r['rank'] === 1 && $r['total'] > 0 ? 'first' : '' ?>">
                        <td><strong><?= ordinal((int)$r['rank']) ?></strong></td>
                        <td><div class="teamcell"><span class="swatch" style="--team:<?= h($c) ?>"></span><?= h($t['name']) ?></div></td>
                        <?php foreach ($events as $e): $p = $r['per'][(int)$e['id']] ?? null; ?>
                            <td class="n"><?= $p === null ? '—' : num($p) ?></td>
                        <?php endforeach; ?>
                        <td class="n"><strong><?= num($r['total']) ?></strong></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

    <div class="footer">Public dashboard refreshes automatically every <?= AUTO_REFRESH_SECONDS ?> seconds.</div>
</main>
</body>
</html>
