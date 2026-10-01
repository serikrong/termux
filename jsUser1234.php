<?php
// == 1400 .php == // อย่าลบ อย่าแก้ บรรทัดนี้

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

$dir_user = __DIR__ . '/users';
$source_type = "Scrape";
$oil_cache_file = __DIR__ . '/14.json';

if (!is_dir($dir_user)) {
    mkdir($dir_user, 0755, true);
}

// ==================== SAVE PREF ====================
if (isset($_POST['action']) && $_POST['action'] == 'save_pref') {
    $user = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['user'] ?? 'u_guest');
    $user_dir = $dir_user . '/' . $user . '/14';
    $pref_file = $user_dir . '/14.json';

    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    $all_data = file_exists($pref_file)
        ? json_decode(file_get_contents($pref_file), true)
        : ["date" => [], "price" => [], "price1" => []];

    if (!is_array($all_data)) {
        $all_data = ["date" => [], "price" => [], "price1" => []];
    }

    $api_result = loadFromAPI();
    $date_key = $api_result['cache_date'] ?? date('Y-m-d');

    $all_data['date'][$date_key] = [
        "update_date" => $api_result['update_date'] ?? date('Y-m-d'),
        "stations"    => $api_result['stations'] ?? [],
        "fetched_at"  => date('Y-m-d H:i:s'),
        "source"      => $source_type
    ];

    $price_date = $_POST['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $price_date)) {
        $price_date = date('Y-m-d');
    }

    $save_type = $_POST['save_type'] ?? 'price';
    $entry = [
        'group'      => $_POST['group'] ?? '',
        'oil'        => $_POST['oil'] ?? '',
        'station'    => $_POST['station'] ?? '',
        'ref_price'  => $_POST['ref_price'] ?? "0",
        'real_price' => $_POST['real_price'] ?? "0",
        'money'      => $_POST['money'] ?? "0",
        'liters'     => $_POST['liters'] ?? "0",
        'consume'    => $_POST['consume'] ?? "0",
        'distance'   => $_POST['distance'] ?? "0",
        'location'   => $_POST['location'] ?? '',
        'date'       => $price_date,
        'time'       => $_POST['time'] ?? date('H:i'),
        'timestamp'  => date('Y-m-d H:i:s')
    ];

    if ($save_type === 'price1') {
        $all_data['price1'][$price_date] = $entry;
    } else {
        $all_data['price'][$price_date] = $entry;
    }

    file_put_contents($pref_file, json_encode($all_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'saved']);
    exit;
}

// ==================== แปลงชื่อน้ำมัน → key ====================
function mapOilKey($nameTh) {
    $n = trim(preg_replace('/\s+/u', ' ', $nameTh));

    if (preg_match('/วี-?เพาเวอร์.*แก๊สโซฮอล์\s*95|แก๊สโซฮอล์\s*95\s*พรีเมียม|พรีเมียม\s*95/u', $n)) {
        return 'premium_gasohol_95';
    }
    if (preg_match('/แก๊สโซฮอล์\s*99/u', $n)) {
        return 'premium_gasohol_99';
    }
    if (preg_match('/วี-?เพาเวอร์\s*ดีเซล|ดีเซลพรีเมียม|พรีเมียม\s*ดีเซล/u', $n)) {
        return 'premium_diesel';
    }
    if (preg_match('/แก๊สโซฮอล์\s*E20/u', $n) || preg_match('/ฟิวเซฟ.*E20/u', $n)) {
        return 'gasohol_e20';
    }
    if (preg_match('/แก๊สโซฮอล์\s*E85/u', $n)) {
        return 'gasohol_e85';
    }
    if (preg_match('/แก๊สโซฮอล์\s*91/u', $n)) {
        return 'gasohol_91';
    }
    if (preg_match('/แก๊สโซฮอล์\s*95/u', $n)) {
        return 'gasohol_95';
    }
    if (preg_match('/เบนซิน\s*95/u', $n)) {
        return 'gasoline_95';
    }
    if (preg_match('/ดีเซล\s*B20/u', $n)) {
        return 'diesel_b20';
    }
    if (preg_match('/ดีเซล\s*B7|ฟิวเซฟ\s*ดีเซล/u', $n)) {
        return 'diesel_b7';
    }
    if (preg_match('/ดีเซล/u', $n)) {
        return 'diesel';
    }
    return null;
}

// ==================== SCRAPE (แยกตามหัวข้อปั๊ม) ====================
function scrapeKapookOilPrice() {
    $url = 'https://gasprice.kapook.com/';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: th-TH,th;q=0.9,en;q=0.8',
        ],
    ]);

    $html = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200 || empty($html)) {
        return ['error' => "ดึงข้อมูลไม่สำเร็จ: {$error} (HTTP {$httpCode})"];
    }

    // ซัสโก้ ดีลเลอร์ ต้องอยู่ก่อน ซัสโก้
    $headerToSid = [
        'ปตท.'            => 'ptt',
        'บางจาก'          => 'bcp',
        'เชลล์'           => 'shell',
        'คาลเท็กซ์'       => 'caltex',
        'ไออาร์พีซี'      => 'irpc',
        'พีที'            => 'pt',
        'ซัสโก้ ดีลเลอร์' => 'susco_dealers',
        'ซัสโก้'          => 'susco',
        'เพียว'           => 'pure',
    ];

    $sections = [];
    if (preg_match_all(
        '/ราคาน้ำมัน\s*(ปตท\.|บางจาก|เชลล์|คาลเท็กซ์|ไออาร์พีซี|พีที|ซัสโก้ ดีลเลอร์|ซัสโก้|เพียว)(?![\w"])/u',
        $html,
        $hm,
        PREG_OFFSET_CAPTURE
    )) {
        foreach ($hm[1] as $item) {
            $name = $item[0];
            $pos  = $item[1];
            $sid  = $headerToSid[$name] ?? null;
            if ($sid === null) {
                continue;
            }
            if (!isset($sections[$sid])) {
                $sections[$sid] = $pos;
            }
        }
    }

    if (empty($sections)) {
        return ['error' => 'ไม่พบหัวข้อสถานีในหน้าเว็บ'];
    }

    asort($sections);
    $sidList = array_keys($sections);
    $posList = array_values($sections);

    $oilPattern = '/((?:เชลล์\s+)?(?:ฟิวเซฟ\s+|วี-เพาเวอร์\s+)?(?:แก๊สโซฮอล์\s*(?:95|91|E20|E85|99)?(?:\s*พรีเมียม)?|เบนซิน\s*95|ดีเซล(?:\s*(?:B7|B20))?|ดีเซลพรีเมียม))\s*<\/p>.*?text-xl[^>]*>([0-9]+\.[0-9]{2})/su';

    $stations = [];
    $count = count($sidList);

    for ($i = 0; $i < $count; $i++) {
        $sid   = $sidList[$i];
        $start = $posList[$i];
        $end   = ($i + 1 < $count) ? $posList[$i + 1] : ($start + 20000);
        $chunk = substr($html, $start, max(0, $end - $start));

        if (!preg_match_all($oilPattern, $chunk, $matches, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($matches as $m) {
            $nameTh = trim(preg_replace('/\s+/u', ' ', $m[1]));
            $price  = $m[2];
            $oilKey = mapOilKey($nameTh);
            if ($oilKey === null) {
                continue;
            }
            if (!isset($stations[$sid])) {
                $stations[$sid] = [];
            }
            if (!isset($stations[$sid][$oilKey])) {
                $stations[$sid][$oilKey] = [
                    'name'  => $nameTh,
                    'price' => $price,
                ];
            }
        }
    }

    foreach ($stations as $sid => &$oils) {
        if (!isset($oils['diesel']) && isset($oils['diesel_b7'])) {
            $oils['diesel'] = [
                'name'  => 'ดีเซล',
                'price' => $oils['diesel_b7']['price'],
            ];
        }
    }
    unset($oils);

    if (empty($stations)) {
        return ['error' => 'ไม่พบข้อมูลราคาในหน้าเว็บ'];
    }

    $updateDate = date('Y-m-d');
    if (preg_match('/อัปเดต.*?(\d{1,2}\s+\S+\s+\d{4})/u', $html, $dm)) {
        $updateDate = $dm[1];
    }

    return [
        'cache_date'  => date('Y-m-d'),
        'update_date' => $updateDate,
        'stations'    => $stations,
        'source'      => 'gasprice.kapook.com',
        'fetched_at'  => date('Y-m-d H:i:s'),
    ];
}

// ==================== โหลดราคา (Cache รายวันใน 14.json) ====================
function loadFromAPI() {
    global $oil_cache_file;
    $today = date('Y-m-d');

    if (file_exists($oil_cache_file)) {
        $cached = json_decode(file_get_contents($oil_cache_file), true);
        if (
            is_array($cached)
            && isset($cached['cache_date'])
            && $cached['cache_date'] === $today
            && !empty($cached['stations'])
        ) {
            $cached['from_cache'] = true;
            return $cached;
        }
    }

    $result = scrapeKapookOilPrice();

    if (isset($result['error'])) {
        if (file_exists($oil_cache_file)) {
            $old = json_decode(file_get_contents($oil_cache_file), true);
            if (is_array($old) && !empty($old['stations'])) {
                $old['from_cache'] = true;
                $old['cache_warning'] = 'Scrape ไม่สำเร็จ ใช้ข้อมูลเก่า';
                return $old;
            }
        }
        return [
            'cache_date'  => $today,
            'update_date' => $today,
            'stations'    => [],
            'error'       => $result['error'],
            'from_cache'  => false,
        ];
    }

    file_put_contents(
        $oil_cache_file,
        json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    $result['from_cache'] = false;
    return $result;
}

// ==================== โหลดข้อมูลหลัก ====================
$api_result    = loadFromAPI();
$stations_data = isset($api_result['stations']) && is_array($api_result['stations'])
    ? $api_result['stations']
    : [];
$last_update   = $api_result['update_date'] ?? date('Y-m-d');
$from_cache    = !empty($api_result['from_cache']);

$station_names = [
    'ptt'           => ['th' => 'ปตท.',            'en' => 'PTT'],
    'bcp'           => ['th' => 'บางจาก',          'en' => 'BCP'],
    'shell'         => ['th' => 'เชลล์',           'en' => 'Shell'],
    'caltex'        => ['th' => 'คาลเท็กซ์',       'en' => 'Caltex'],
    'irpc'          => ['th' => 'IRPC',            'en' => 'IRPC'],
    'pt'            => ['th' => 'PT',              'en' => 'PT'],
    'susco'         => ['th' => 'ซัสโก้',          'en' => 'Susco'],
    'pure'          => ['th' => 'เพียว',           'en' => 'Pure'],
    'susco_dealers' => ['th' => 'ซัสโก้ ดีลเลอร์', 'en' => 'Susco Dealers'],
];

$active_pumps = array_values(array_filter(
    array_keys($station_names),
    function ($id) use ($stations_data) {
        return isset($stations_data[$id]);
    }
));

$groups = [
    'gasohol' => [
        'label'     => ['th' => 'เบนซิน', 'en' => 'Gasoline'],
        'color'     => '#00f2ff',
        'header_bg' => '#004a4d',
        'bg'        => '#05161a',
        'border'    => '#00f2ff',
        'items'     => [
            'gasoline_95'        => ['th' => 'เบนซิน 95',   'en' => 'Benzine 95'],
            'gasohol_95'         => ['th' => 'แก๊สฯ 95',    'en' => 'Gasohol 95'],
            'gasohol_91'         => ['th' => 'แก๊สฯ 91',    'en' => 'Gasohol 91'],
            'gasohol_e20'        => ['th' => 'E20',          'en' => 'E20'],
            'gasohol_e85'        => ['th' => 'E85',          'en' => 'E85'],
            'premium_gasohol_95' => ['th' => 'พรีเมียม 95', 'en' => 'Premium 95'],
            'premium_gasohol_99' => ['th' => 'พรีเมียม 99', 'en' => 'Premium 99'],
        ],
    ],
    'diesel' => [
        'label'     => ['th' => 'ดีเซล', 'en' => 'Diesel'],
        'color'     => '#faff00',
        'header_bg' => '#4d4d00',
        'bg'        => '#1a1a05',
        'border'    => '#faff00',
        'items'     => [
            'diesel'         => ['th' => 'ดีเซล',         'en' => 'Diesel'],
            'diesel_b7'      => ['th' => 'ดีเซล B7',      'en' => 'Diesel B7'],
            'diesel_b20'     => ['th' => 'ดีเซล B20',     'en' => 'Diesel B20'],
            'premium_diesel' => ['th' => 'พรีเมียมดีเซล', 'en' => 'Premium Diesel'],
        ],
    ],
    'gas' => [
        'label'     => ['th' => 'แก๊ส', 'en' => 'Gas'],
        'color'     => '#ff00ff',
        'header_bg' => '#4d004d',
        'bg'        => '#1a051a',
        'border'    => '#ff00ff',
        'items'     => [
            'lpg' => ['th' => 'LPG', 'en' => 'LPG'],
            'ngv' => ['th' => 'NGV', 'en' => 'NGV'],
        ],
    ],
];

$rawJson = file_exists($oil_cache_file)
    ? file_get_contents($oil_cache_file)
    : json_encode($api_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fuel Price Tracker 1400</title>
    <style>
        :root {
            --neon-blue: #00f2ff;
            --neon-green: #39ff14;
            --neon-red: #ff3131;
            --neon-yellow: #faff00;
            --neon-purple: #ff00ff;
            --white: #ffffff;
        }
        * { box-sizing: border-box; font-weight: normal !important; line-height: 1.1; }
        body {
            margin: 0; background: #000; color: #fff;
            font-family: system-ui, sans-serif; font-size: 11pt;
            display: flex; flex-direction: column; align-items: center; min-height: 100vh;
        }
        .container { width: 100%; padding: 0 8px 10px; flex: 1; }
        @media (min-width: 1024px) { .container { width: 50%; } }

        .lang-btn {
            background: #000; border: 1px solid var(--neon-blue); color: #fff;
            padding: 0 8px; border-radius: 20px; font-size: 8pt; cursor: pointer;
            position: absolute; top: -10px; right: 10px; z-index: 10; height: 20px;
        }

        #price1 {
            margin: 15px 0; padding: 18px 6px 10px;
            border: 1px solid var(--neon-blue); border-radius: 8px;
            background: #05161a; position: relative;
        }
        #price2 {
            margin: 0 0 10px 0; padding: 18px 6px 10px;
            border: 1px solid var(--neon-purple); border-radius: 8px;
            background: #1a051a; position: relative;
        }
        #price1::before, #price2::before {
            position: absolute; top: -10px; left: 10px; background: #000;
            padding: 0 5px; font-size: 9pt; color: #fff;
            font-weight: bold !important; white-space: nowrap;
        }
        #price1::before { content: attr(data-title); }
        #price2::before { content: attr(data-title); }

        .sel-item {
            display: flex; flex-direction: column; gap: 4px;
            flex: 1; min-width: 0; border-radius: 6px; padding: 4px;
        }
        .sel-label { font-size: 8pt; color: rgba(255,255,255,0.7); padding-left: 2px; }
        select, input {
            background: rgba(0,0,0,0.3); color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 4px; border-radius: 4px; font-size: 10pt;
            height: 38px; width: 100%; text-align: center; outline: none;
        }
        .box-input { border: 1px solid #555; background: #111 !important; }

        .new-btn {
            background: #333; border: 1px solid #777; color: #fff;
            border-radius: 4px; cursor: pointer; padding: 0 10px;
            height: 38px; font-weight: bold !important;
        }
        .new-btn:active { background: #555; }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        input#ref_price {
            background: #000; color: var(--neon-green);
            font-weight: bold !important; border: 1px solid var(--neon-green); font-size: 12pt;
        }
        input#money {
            color: var(--neon-green); font-weight: bold !important;
            font-size: 14pt; border: 1px solid var(--neon-green) !important;
        }
        input#real_price {
            color: var(--neon-red); font-weight: bold !important;
            font-size: 14pt; border: 1px solid var(--neon-red) !important;
        }
        input#liters {
            color: var(--neon-red); font-weight: bold !important;
            font-size: 14pt; border: 1px solid var(--neon-red) !important;
        }
        input#consume {
            color: var(--neon-yellow); font-weight: bold !important;
            font-size: 14pt; border: 1px solid var(--neon-yellow) !important;
        }
        input#distance {
            color: var(--neon-blue); font-weight: bold !important;
            font-size: 14pt; border: 1px solid var(--neon-blue) !important;
        }
        input#location { color: var(--white); border: 1px solid var(--white) !important; }

        .wrapper {
            width: 100%; overflow: hidden; margin-bottom: 5px;
            border: 1px solid #333; border-radius: 8px;
        }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { text-align: center; border: 1px solid #444; padding: 6px 4px; font-size: 10pt; }
        .group-header-text {
            font-weight: bold !important; font-size: 12pt !important;
            text-shadow: 0 0 8px currentColor;
        }
        .sticky-col {
            position: sticky; left: 0; z-index: 10; width: 120px;
            text-align: center !important; padding: 6px 2px;
            background: #111; border-right: 1px solid #444;
        }
        .active-tag {
            background: #fff !important; color: #000 !important;
            font-weight: bold !important; box-shadow: 0 0 10px #fff;
            border-radius: 4px; padding: 2px 6px; display: inline-block;
        }
        .highlight-row { background: rgba(255,255,255,0.08) !important; }
        .price-tag {
            cursor: pointer; padding: 2px 6px; border-radius: 4px;
            min-width: 50px; display: inline-block;
        }
        .p-min { color: #00ff00; font-weight: bold !important; }
        .p-max { color: #ff3131; font-weight: bold !important; }

        .json-box {
            margin: 20px 0; border: 1px solid var(--neon-blue);
            border-radius: 8px; background: #0a0a0a; overflow: hidden;
        }
        .json-box-header {
            background: #001a1a; color: var(--neon-blue);
            padding: 8px 12px; font-size: 9pt;
            border-bottom: 1px solid var(--neon-blue);
            display: flex; justify-content: space-between; align-items: center;
        }
        .json-box-header span { color: #888; }
        .json-box pre {
            margin: 0; padding: 12px; overflow: auto; max-height: 400px;
            font-family: Consolas, monospace; font-size: 11px; line-height: 1.4;
            color: #b0e0b0; white-space: pre-wrap; word-break: break-all;
        }
        .badge {
            display: inline-block; padding: 1px 6px; border-radius: 4px;
            font-size: 8pt; margin-left: 4px;
        }
        .badge-cache { background: #1a3a1a; color: #39ff14; }
        .badge-live { background: #3a1a1a; color: #ff6b6b; }

        .credit-link {
            text-align: center; margin: 16px 0 8px; padding: 10px;
            border-top: 1px solid #333;
        }
        .credit-link span { color: #888; font-size: 9pt; }
        .credit-link a { color: #00f2ff; text-decoration: none; }
        .credit-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div id="header"></div>
    <div id="user1"></div>

    <div id="price1" data-title="ราคาน้ำมัน (<?= htmlspecialchars($source_type) ?>-<?= htmlspecialchars((string)$last_update) ?>)<?= $from_cache ? ' [Cache]' : ' [Live]' ?>">
        <button class="lang-btn" onclick="toggleLang()" id="btn-lang">Eng</button>
        <div style="display: flex; gap: 6px; margin-top: 10px;">
            <div class="sel-item" style="background:#1a1d2e;">
                <span class="sel-label" data-th="ปั๊ม" data-en="Brand">ปั๊ม</span>
                <select id="station" onchange="updatePrice(true)">
                    <option value="">เลือก</option>
                    <?php foreach ($active_pumps as $id): ?>
                        <option value="<?= htmlspecialchars($id) ?>"
                                data-th="<?= htmlspecialchars($station_names[$id]['th']) ?>"
                                data-en="<?= htmlspecialchars($station_names[$id]['en']) ?>">
                            <?= htmlspecialchars($station_names[$id]['th']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-item" style="background:#2e1a2b;">
                <span class="sel-label" data-th="กลุ่ม" data-en="Group">กลุ่ม</span>
                <select id="group" onchange="updateOilList(true)">
                    <option value="">เลือก</option>
                    <?php foreach ($groups as $k => $v): ?>
                        <option value="<?= htmlspecialchars($k) ?>"
                                data-th="<?= htmlspecialchars($v['label']['th']) ?>"
                                data-en="<?= htmlspecialchars($v['label']['en']) ?>">
                            <?= htmlspecialchars($v['label']['th']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-item" style="background:#1a2e1f;">
                <span class="sel-label" data-th="ชนิด" data-en="Type">ชนิด</span>
                <select id="oil" onchange="updatePrice(true)"><option value="">เลือก</option></select>
            </div>
            <div class="sel-item" style="background:#000;">
                <span class="sel-label" data-th="ราคากลาง" data-en="Ref. Price">ราคากลาง</span>
                <input type="text" id="ref_price" readonly placeholder="0">
            </div>
        </div>
    </div>

    <div id="price2" data-title="เติมน้ำมัน">
        <div style="display: flex; gap: 6px; margin-bottom: 8px;">
            <div class="sel-item box-input">
                <span class="sel-label">Date (yyyy-mm-dd)</span>
                <input type="text" id="date" oninput="autoSave()" placeholder="yyyy-mm-dd">
            </div>
            <div class="sel-item box-input">
                <span class="sel-label">Time (HH:mm)</span>
                <div style="display: flex; gap: 4px;">
                    <input type="text" id="time" oninput="autoSave()" placeholder="HH:mm">
                    <button class="new-btn" onclick="setNew()" title="New Entry">New</button>
                    <button class="new-btn" onclick="savePriceEntry()" id="btn-save" title="Save Entry">💾</button>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 6px; margin-bottom: 8px;">
            <div class="sel-item" style="background:#222; flex: 2;">
                <span class="sel-label" data-th="ปั๊ม/สาขา" data-en="Location">ปั๊ม/สาขา</span>
                <input type="text" id="location" oninput="autoSave()" placeholder="..." style="text-align: left; padding-left: 10px;">
            </div>
            <div class="sel-item box-input">
                <span class="sel-label" data-th="ราคาจริง" data-en="Real Price">ราคาจริง</span>
                <input type="number" step="0.01" id="real_price" oninput="calc('price')" placeholder="0">
            </div>
            <div class="sel-item box-input">
                <span class="sel-label" data-th="เงินเติม" data-en="Amount">เงินเติม</span>
                <input type="text" id="money"
                       onfocus="this.type='number'"
                       onblur="this.type='text'; this.value=formatAmount(this.value)"
                       oninput="calc('money')" placeholder="0">
            </div>
        </div>
        <div style="display: flex; gap: 6px;">
            <div class="sel-item box-input">
                <span class="sel-label" data-th="ลิตร" data-en="Liters">ลิตร</span>
                <input type="number" step="0.001" id="liters" oninput="calc('liters')" placeholder="0">
            </div>
            <div class="sel-item box-input">
                <span class="sel-label">km/l</span>
                <input type="number" step="0.1" id="consume" oninput="calc('consume')" placeholder="0">
            </div>
            <div class="sel-item box-input">
                <span class="sel-label" data-th="ระยะทาง" data-en="Distance">ระยะทาง</span>
                <input type="number" step="0.1" id="distance" oninput="calc('distance')" placeholder="0">
            </div>
        </div>
    </div>

    <?php foreach ($groups as $g_key => $cfg): ?>
    <div class="wrapper" style="border-color: <?= htmlspecialchars($cfg['border']) ?>;">
        <div class="scroll">
            <table>
                <thead>
                    <tr style="background: <?= htmlspecialchars($cfg['header_bg']) ?>;">
                        <th class="sticky-col group-header-text"
                            style="background: <?= htmlspecialchars($cfg['header_bg']) ?>; color: <?= htmlspecialchars($cfg['color']) ?>;"
                            data-th="<?= htmlspecialchars($cfg['label']['th']) ?>"
                            data-en="<?= htmlspecialchars($cfg['label']['en']) ?>">
                            <?= htmlspecialchars($cfg['label']['th']) ?>
                        </th>
                        <?php foreach ($active_pumps as $id): ?>
                            <th data-sid="<?= htmlspecialchars($id) ?>">
                                <span class="pump-name"
                                      data-th="<?= htmlspecialchars($station_names[$id]['th']) ?>"
                                      data-en="<?= htmlspecialchars($station_names[$id]['en']) ?>">
                                    <?= htmlspecialchars($station_names[$id]['th']) ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cfg['items'] as $oil_key => $l_item):
                        $prices = [];
                        foreach ($active_pumps as $sid) {
                            $raw = $stations_data[$sid][$oil_key]['price']
                                ?? $stations_data[$sid][$oil_key]
                                ?? 0;
                            $p = (float) $raw;
                            if ($p > 0) {
                                $prices[] = $p;
                            }
                        }
                        $min = $prices ? min($prices) : 0;
                        $max = $prices ? max($prices) : 0;
                    ?>
                    <tr data-oil="<?= htmlspecialchars($oil_key) ?>"
                        onclick="quickSelect('<?= htmlspecialchars($g_key) ?>', '<?= htmlspecialchars($oil_key) ?>', '')">
                        <td class="sticky-col"
                            style="background: <?= htmlspecialchars($cfg['bg']) ?>; text-align: left !important; padding-left: 10px;">
                            <span class="oil-label"
                                  data-oil-id="<?= htmlspecialchars($oil_key) ?>"
                                  data-th="<?= htmlspecialchars($l_item['th']) ?>"
                                  data-en="<?= htmlspecialchars($l_item['en']) ?>">
                                <?= htmlspecialchars($l_item['th']) ?>
                            </span>
                        </td>
                        <?php foreach ($active_pumps as $id):
                            $raw = $stations_data[$id][$oil_key]['price']
                                ?? $stations_data[$id][$oil_key]
                                ?? 0;
                            $p = (float) $raw;
                            $cls = '';
                            if ($p > 0 && $min != $max) {
                                if ($p == $min) {
                                    $cls = 'p-min';
                                } elseif ($p == $max) {
                                    $cls = 'p-max';
                                }
                            }
                        ?>
                        <td data-sid="<?= htmlspecialchars($id) ?>">
                            <?php if ($p > 0): ?>
                                <span class="price-tag <?= $cls ?>"
                                      data-station="<?= htmlspecialchars($id) ?>"
                                      data-oil-item="<?= htmlspecialchars($oil_key) ?>"
                                      onclick="event.stopPropagation(); quickSelect('<?= htmlspecialchars($g_key) ?>', '<?= htmlspecialchars($oil_key) ?>', '<?= htmlspecialchars($id) ?>')">
                                    <?= number_format($p, 2) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="json-box">
        <div class="json-box-header">
            📄 14.json
            <span>
                <?php if ($from_cache): ?>
                    <span class="badge badge-cache">Cache</span>
                <?php else: ?>
                    <span class="badge badge-live">Live</span>
                <?php endif; ?>
                <?= file_exists($oil_cache_file) ? number_format(filesize($oil_cache_file)) . ' bytes' : 'ไม่มีไฟล์' ?>
            </span>
        </div>
        <pre><?= htmlspecialchars((string)$rawJson) ?></pre>
    </div>

    <div class="credit-link">
        <span>
            ข้อมูลราคาน้ำมันจาก
            <a href="https://gasprice.kapook.com/" target="_blank" rel="noopener noreferrer">
                gasprice.kapook.com
            </a>
        </span>
    </div>

    <div id="footer"></div>
</div>

<script>
const oilData = <?= json_encode($stations_data, JSON_UNESCAPED_UNICODE) ?>;
const groupsInfo = <?= json_encode($groups, JSON_UNESCAPED_UNICODE) ?>;
const currentUser = localStorage.getItem('username') || 'u_guest';
let currentLang = localStorage.getItem('lang') || 'th';
let saveTimeout = null;

function formatAmount(val) {
    let n = parseFloat(val);
    if (isNaN(n) || n === 0) return '0';
    return n % 1 === 0 ? n.toString() : n.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
}

function autoSave() {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(save, 700);
}

function save() {
    const fD = new FormData();
    fD.append('action', 'save_pref');
    fD.append('user', currentUser);
    ['group','oil','station','ref_price','real_price','money','liters','consume','distance','location','date','time'].forEach(id => {
        const el = document.getElementById(id);
        if (el) fD.append(id, el.value || '');
    });
    fetch(window.location.href, { method: 'POST', body: fD }).catch(() => {});
}

async function savePriceEntry() {
    const btn = document.getElementById('btn-save');
    const original = btn.innerHTML;
    btn.innerHTML = '⏳';
    btn.disabled = true;
    const fD = new FormData();
    fD.append('action', 'save_pref');
    fD.append('user', currentUser);
    fD.append('save_type', 'price1');
    ['group','oil','station','ref_price','real_price','money','liters','consume','distance','location','date','time'].forEach(id => {
        const el = document.getElementById(id);
        if (el) fD.append(id, el.value || '');
    });
    try {
        const res = await fetch(window.location.href, { method: 'POST', body: fD });
        const result = await res.json();
        btn.innerHTML = (result.status === 'saved') ? '✅' : '❌';
        setTimeout(() => { btn.innerHTML = original; btn.disabled = false; }, 1200);
    } catch (e) {
        btn.innerHTML = '❌';
        setTimeout(() => { btn.innerHTML = original; btn.disabled = false; }, 1500);
    }
}

function setNew() {
    const now = new Date();
    const localNow = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
    document.getElementById('date').value = localNow.toISOString().split('T')[0];
    document.getElementById('time').value =
        String(localNow.getHours()).padStart(2, '0') + ':' +
        String(localNow.getMinutes()).padStart(2, '0');
    document.getElementById('real_price').value = document.getElementById('ref_price').value || '';
    autoSave();
}

function applyLang() {
    document.getElementById('btn-lang').innerText = (currentLang === 'th') ? 'Eng' : 'ไทย';
    document.querySelectorAll('[data-th]').forEach(el => {
        const val = el.getAttribute('data-' + currentLang);
        if (val) {
            if (el.tagName === 'OPTION') el.text = val;
            else el.innerText = val;
        }
    });
}

function updateOilList(shouldSave = true, targetOil = "") {
    const gK = document.getElementById('group').value;
    const oS = document.getElementById('oil');
    oS.innerHTML = `<option value="">${currentLang === 'th' ? 'เลือก' : 'Select'}</option>`;
    if (gK && groupsInfo[gK]) {
        for (let k in groupsInfo[gK].items) {
            let opt = document.createElement('option');
            opt.value = k;
            opt.text = groupsInfo[gK].items[k][currentLang];
            if (k === targetOil) opt.selected = true;
            oS.add(opt);
        }
    }
    if (shouldSave) updatePrice(true);
}

function updatePrice(shouldSave = true) {
    const o = document.getElementById('oil').value;
    const s = document.getElementById('station').value;
    document.querySelectorAll('.highlight-row, .active-tag').forEach(el => {
        el.classList.remove('highlight-row', 'active-tag');
    });
    if (o) {
        document.querySelectorAll(`tr[data-oil="${o}"]`).forEach(tr => {
            tr.classList.add('highlight-row');
            const label = tr.querySelector('.oil-label');
            if (label) label.classList.add('active-tag');
        });
        if (s) {
            const headPump = document.querySelector(`th[data-sid="${s}"] .pump-name`);
            if (headPump) headPump.classList.add('active-tag');
            const priceTag = document.querySelector(`.price-tag[data-station="${s}"][data-oil-item="${o}"]`);
            if (priceTag) priceTag.classList.add('active-tag');
            if (oilData[s]) {
                let p = (typeof oilData[s][o] === 'object')
                    ? (oilData[s][o].price ?? 0)
                    : (oilData[s][o] ?? 0);
                document.getElementById('ref_price').value = parseFloat(p).toFixed(2);
            }
        }
    }
    if (shouldSave) autoSave();
}

function calc(trigger) {
    const rP = parseFloat(document.getElementById('real_price').value) || 0;
    const money = parseFloat(document.getElementById('money').value) || 0;
    const lIn = parseFloat(document.getElementById('liters').value) || 0;
    const cS = parseFloat(document.getElementById('consume').value) || 0;
    const dIn = parseFloat(document.getElementById('distance').value) || 0;

    if (trigger === 'money' || trigger === 'price') {
        if (money > 0 && rP > 0) {
            const cL = money / rP;
            document.getElementById('liters').value = cL.toFixed(3);
            if (cS > 0) document.getElementById('distance').value = (cL * cS).toFixed(1);
        }
    } else if (trigger === 'liters') {
        if (lIn > 0 && rP > 0) {
            document.getElementById('money').value = (lIn * rP).toFixed(2);
            if (cS > 0) document.getElementById('distance').value = (lIn * cS).toFixed(1);
        }
    } else if (trigger === 'distance') {
        if (dIn > 0 && cS > 0) {
            const cL = dIn / cS;
            document.getElementById('liters').value = cL.toFixed(3);
            if (rP > 0) document.getElementById('money').value = (cL * rP).toFixed(2);
        }
    } else if (trigger === 'consume') {
        const cL = parseFloat(document.getElementById('liters').value) || 0;
        if (cL > 0 && cS > 0) document.getElementById('distance').value = (cL * cS).toFixed(1);
    }
    autoSave();
}

function quickSelect(gK, oK, sI) {
    document.getElementById('group').value = gK;
    updateOilList(false, oK);
    if (sI) document.getElementById('station').value = sI;
    updatePrice(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function toggleLang() {
    currentLang = (currentLang === 'th') ? 'en' : 'th';
    localStorage.setItem('lang', currentLang);
    applyLang();
    updateOilList(false, document.getElementById('oil').value);
}

async function load() {
    const now = new Date();
    const localNow = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
    const todayStr = localNow.toISOString().split('T')[0];
    const timeStr = String(localNow.getHours()).padStart(2, '0') + ':' +
                    String(localNow.getMinutes()).padStart(2, '0');

    document.getElementById('date').value = todayStr;
    document.getElementById('time').value = timeStr;

    try {
        const res = await fetch(`users/${currentUser}/14/14.json?t=${Date.now()}`);
        if (res.ok) {
            const d = await res.json();
            const todayData = d.price1?.[todayStr] || d.price?.[todayStr];
            if (todayData) {
                document.getElementById('group').value = todayData.group || "";
                updateOilList(false, todayData.oil || "");
                document.getElementById('station').value = todayData.station || "";
                document.getElementById('date').value = todayData.date || todayStr;
                document.getElementById('time').value = todayData.time || timeStr;
                ['money','real_price','liters','consume','distance','location'].forEach(k => {
                    const el = document.getElementById(k);
                    if (el) {
                        el.value = (k === 'money')
                            ? formatAmount(todayData[k] || 0)
                            : (todayData[k] || "");
                    }
                });
            }
        }
    } catch (e) {}
    setTimeout(() => updatePrice(true), 800);
}

(function () {
    applyLang();
    load();
})();
</script>
<?php
if (file_exists(__DIR__ . '/jsUser1.php')) {
    include 'jsUser1.php';
}
$programName = "OIL Manager";
if (file_exists(__DIR__ . '/jsHF.php')) {
    include 'jsHF.php';
}
?>
</body>
</html>
