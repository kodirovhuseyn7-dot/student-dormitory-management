<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin','manager','student'], true)) {
    http_response_code(403); exit('Дастрасӣ иҷозат дода нашудааст.');
}
$name = $_SESSION['user']['name'] ?? 'Корбар';
$isStudent = $role === 'student';
$roleLabels = ['admin'=>'Администратор','manager'=>'Менеҷер','student'=>'Донишҷӯ'];
$studentId = 0;

if ($isStudent) {
    $q = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
    $q->execute([current_user_id()]);
    $studentId = (int)$q->fetchColumn();
    if (!$studentId) { http_response_code(403); exit('Профили донишҷӯ ёфт нашуд. Ба маъмур муроҷиат кунед.'); }
}

function scalar(PDO $pdo, string $sql, array $params=[]): mixed {
    $q=$pdo->prepare($sql); $q->execute($params); return $q->fetchColumn();
}
function moneyFmt($v): string { return number_format((float)$v, 2, '.', ' ') . ' сомонӣ'; }
function statusLabel($s): string {
    return ['paid'=>'Пардохтшуда','pending'=>'Интизорӣ','overdue'=>'Қарздорӣ','active'=>'Фаъол','pending_review'=>'Дар баррасӣ'][$s] ?? (string)$s;
}

if ($isStudent) {
    $cards = [
        ['Дархостҳои ман', (int)scalar($pdo,'SELECT COUNT(*) FROM applications WHERE student_id=?',[$studentId]), 'Дархостҳои фиристодашуда','▤','blue'],
        ['Дар интизорӣ', (int)scalar($pdo,"SELECT COUNT(*) FROM applications WHERE student_id=? AND status='pending'",[$studentId]), 'Интизори ҷавоби маъмурият','◷','amber'],
        ['Ҷойгиркунии фаъол', (int)scalar($pdo,"SELECT COUNT(*) FROM allocations WHERE student_id=? AND status='active'",[$studentId]), 'Ҷойи истиқомати шумо','⌂','green'],
        ['Пардохтшуда', moneyFmt(scalar($pdo,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id=? AND status='paid'",[$studentId])), 'Маблағи пардохтшуда','✓','green'],
        ['Қарз / интизорӣ', moneyFmt(scalar($pdo,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id=? AND status IN ('pending','overdue')",[$studentId])), 'Пардохтҳои анҷомнаёфта','!','rose']
    ];
    $recentQ=$pdo->prepare('SELECT payment_date,payment_period,amount,status FROM payments WHERE student_id=? ORDER BY payment_date DESC,id DESC LIMIT 6');
    $recentQ->execute([$studentId]); $recentRows=$recentQ->fetchAll();
    $chartRows=$pdo->prepare("SELECT DATE_FORMAT(payment_date,'%m.%Y') label,SUM(amount) total FROM payments WHERE student_id=? AND status='paid' AND payment_date>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY YEAR(payment_date),MONTH(payment_date),label ORDER BY YEAR(payment_date),MONTH(payment_date)");
    $chartRows->execute([$studentId]); $chartRows=$chartRows->fetchAll();
} else {
    $cards = [
        ['Донишҷӯён',(int)scalar($pdo,'SELECT COUNT(*) FROM students'),'Ҳамаи донишҷӯёни сабтшуда','♙','blue'],
        ['Хобгоҳҳо',(int)scalar($pdo,'SELECT COUNT(*) FROM dormitories'),'Хобгоҳҳои низом','⌂','violet'],
        ['Ҳуҷраҳо',(int)scalar($pdo,'SELECT COUNT(*) FROM rooms'),'Ҳамаи ҳуҷраҳо','▦','cyan'],
        ['Дархостҳои нав',(int)scalar($pdo,"SELECT COUNT(*) FROM applications WHERE status='pending'"),'Интизори баррасӣ','◷','amber'],
        ['Ҷойгиркунии фаъол',(int)scalar($pdo,"SELECT COUNT(*) FROM allocations WHERE status='active'"),'Донишҷӯёни ҷойгиршуда','✓','green'],
        ['Пардохтшуда',moneyFmt(scalar($pdo,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")),'Ҳаҷми пардохтҳои қабулшуда','₮','green'],
        ['Қарздорӣ',moneyFmt(scalar($pdo,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='overdue'")),'Пардохтҳои муҳлатгузашта','!','rose']
    ];
    $recentRows=$pdo->query("SELECT p.payment_date,p.payment_period,p.amount,p.status,u.full_name FROM payments p LEFT JOIN students s ON s.id=p.student_id LEFT JOIN users u ON u.id=s.user_id ORDER BY p.payment_date DESC,p.id DESC LIMIT 7")->fetchAll();
    $chartRows=$pdo->query("SELECT DATE_FORMAT(payment_date,'%m.%Y') label,SUM(amount) total FROM payments WHERE status='paid' AND payment_date>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY YEAR(payment_date),MONTH(payment_date),label ORDER BY YEAR(payment_date),MONTH(payment_date)")->fetchAll();
}
$labels=[];$values=[];
for($i=5;$i>=0;$i--){$d=new DateTime('first day of this month');$d->modify("-$i months");$labels[]=$d->format('m.Y');$values[]=0;}
foreach($chartRows as $r){$idx=array_search($r['label'],$labels,true);if($idx!==false)$values[$idx]=(float)$r['total'];}
$links=[['dashboard.php','Панели асосӣ','⌂'],['dormitories.php','Хобгоҳҳо','▦'],['payments.php','Пардохтҳо','₮']];
if(!$isStudent){$links=array_merge($links,[['students.php','Донишҷӯён','♙'],['rooms.php','Ҳуҷраҳо','▤'],['applications.php','Дархостҳо','▣'],['allocations.php','Ҷойгиркунӣ','⌂'],['reports.php','Ҳисоботҳо','▥']]);}
else {$links[]=['my_applications.php','Дархостҳои ман','▣'];}
$pendingApps=(int)scalar($pdo,"SELECT COUNT(*) FROM applications WHERE status='pending'");
$today=date('d.m.Y');
?>
<!doctype html><html lang="tg"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Панели асосӣ | SDMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--navy:#14213d;--blue:#c58b45;--muted:#75819a;--line:#e8edf5}
*{box-sizing:border-box}body{margin:0;background:#f5f7fc;color:#18243b;font-family:Inter,Arial,sans-serif}
.layout{min-height:100vh;display:flex}.sidebar{width:258px;flex:0 0 258px;background:linear-gradient(180deg,#14213d,#1b2e52);color:#dce6fa;padding:24px 16px;display:flex;flex-direction:column}
.brand{padding:3px 12px 28px;font-size:25px;font-weight:800;letter-spacing:.5px;color:#fff}.brand small{display:block;margin-top:5px;font-size:10px;line-height:1.5;font-weight:500;color:#a9bad9;letter-spacing:.2px}
.nav-title{padding:12px 13px 8px;color:#8296bb;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1.2px}
.nav{display:grid;gap:5px}.nav a{display:flex;align-items:center;gap:12px;padding:12px 13px;color:#c5d1e7;text-decoration:none;border-radius:11px;font-size:13px;font-weight:600;transition:.2s}.nav a:hover,.nav a.active{background:#c58b45;color:#fff;box-shadow:0 7px 18px #c58b4535}.nav .ico{width:22px;text-align:center;font-size:17px}
.side-bottom{margin-top:auto;padding:16px 12px 0;border-top:1px solid #ffffff18}.side-bottom a{color:#ffbfc5;text-decoration:none;font-size:13px;font-weight:600}
.main{flex:1;min-width:0;padding:30px clamp(18px,3vw,42px) 40px}.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:25px}.eyebrow{font-size:11px;color:var(--blue);font-weight:800;text-transform:uppercase;letter-spacing:1.3px}.topbar h1{font-size:27px;margin:6px 0 5px;letter-spacing:-.8px}.sub{color:var(--muted);font-size:13px;margin:0}.userbox{display:flex;align-items:center;gap:11px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px 14px;box-shadow:0 5px 20px #20365a08}.avatar{width:39px;height:39px;border-radius:12px;background:#eaf0ff;color:var(--blue);display:grid;place-items:center;font-weight:800}.userbox b{display:block;font-size:12px}.userbox small{display:block;color:var(--muted);font-size:10px;margin-top:4px}
.banner{background:linear-gradient(110deg,#244bd1,#4777ff);color:#fff;padding:21px 24px;border-radius:17px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:14px;box-shadow:0 12px 28px #c58b4525}.banner h2{margin:0 0 6px;font-size:18px}.banner p{margin:0;color:#e1e9ff;font-size:12px}.date{font-size:12px;font-weight:700;background:#ffffff20;border:1px solid #ffffff35;padding:9px 12px;border-radius:10px;white-space:nowrap}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:14px}.stat{background:#fff;border:1px solid var(--line);border-radius:15px;padding:17px 18px;box-shadow:0 5px 20px #1d315008;min-width:0}.stat-top{display:flex;justify-content:space-between;align-items:center;gap:8px}.stat small{color:var(--muted);font-size:11px;font-weight:700}.stat-icon{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-weight:800;font-size:16px}.stat strong{display:block;font-size:24px;margin:13px 0 5px;letter-spacing:-.6px;overflow-wrap:anywhere}.stat em{font-style:normal;color:#8994a8;font-size:10px}.blue .stat-icon{background:#eaf0ff;color:#c58b45}.violet .stat-icon{background:#f1eaff;color:#8155d9}.cyan .stat-icon{background:#e4f8fb;color:#168ca1}.amber .stat-icon{background:#fff4dc;color:#b47b12}.green .stat-icon{background:#e5f7ef;color:#16835a}.rose .stat-icon{background:#ffeaed;color:#c44355}
.grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(260px,.85fr);gap:17px;margin-top:19px}.panel{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;min-width:0;box-shadow:0 5px 20px #1d315008}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:15px}.panel h2{font-size:14px;margin:0}.muted{font-size:10px;color:var(--muted)}.chart{height:245px}
.shortcuts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.shortcut{display:flex;align-items:center;gap:9px;padding:13px 11px;background:#f5f7ff;border:1px solid #edf0ff;border-radius:12px;color:#284fc9;text-decoration:none;font-size:11px;font-weight:700;transition:.2s}.shortcut:hover{background:#eaf0ff;transform:translateY(-2px)}.shortcut span{font-size:16px}
.notice{margin-top:13px;background:#fff8e8;border:1px solid #f8ebc8;border-radius:12px;padding:12px;color:#87641b;font-size:11px;line-height:1.6}.notice a{color:#c58b45;font-weight:700;text-decoration:none}
.table-wrap{overflow:auto}.data{width:100%;border-collapse:collapse;white-space:nowrap}.data th,.data td{text-align:left;padding:12px 9px;border-bottom:1px solid #eef1f6;font-size:11px}.data th{color:#8792a6;font-size:10px;font-weight:700}.data tr:last-child td{border-bottom:0}.pill{display:inline-block;padding:5px 9px;border-radius:20px;background:#f0f2f6;color:#68748a;font-size:10px;font-weight:700}.pill.paid{background:#e5f7ef;color:#16835a}.pill.pending{background:#fff4dc;color:#a57512}.pill.overdue{background:#ffeaed;color:#bd4051}.empty{text-align:center;color:#8a95a8;padding:25px!important}
@media(max-width:900px){.sidebar{width:220px;flex-basis:220px}.grid{grid-template-columns:1fr}}
@media(max-width:620px){.layout{display:block}.sidebar{width:100%;padding:12px}.brand{padding:2px 8px 12px}.nav{display:flex;overflow-x:auto}.nav-title,.side-bottom{display:none}.nav a{white-space:nowrap;padding:9px 11px}.main{padding:20px 13px}.topbar{align-items:flex-start}.topbar h1{font-size:22px}.userbox{padding:7px}.userbox small{display:none}.avatar{width:32px;height:32px}.banner{padding:17px;align-items:flex-start}.date{font-size:10px}.stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.stat{padding:13px}.stat strong{font-size:18px}.grid{grid-template-columns:1fr}.panel{padding:15px}.chart{height:210px}}

/* Premium charcoal + warm bronze palette: no blue, reduced white surfaces */
:root{--navy:#eee6d8;--blue:#c58b45;--muted:#aaa99f;--line:#3a403a}
body{background:#171b18!important;color:#eee6d8!important}
.sidebar{background:linear-gradient(180deg,#202722,#171d19)!important;color:#e8e0d2}
.brand{color:#f4ead9!important}.brand small,.nav-title{color:#a9a18f!important}
.nav a{color:#d0c8b9!important}.nav a:hover,.nav a.active{background:#8b6030!important;color:#fff!important;box-shadow:0 7px 18px #0005!important}
.main{background:#171b18}.eyebrow{color:#d6a45e!important}
.userbox,.stat,.panel{background:#252c27!important;border-color:#3b443c!important;color:#eee6d8!important;box-shadow:0 8px 22px #0003!important}
.userbox b,.stat strong,.panel h2,.topbar h1{color:#f1e8d9!important}
.avatar,.stat-icon{background:#493b29!important;color:#e3b46f!important}
.banner{background:linear-gradient(110deg,#5b4329,#876039)!important;box-shadow:0 12px 28px #0004!important}
.banner p{color:#f1dfc5!important}.date{background:#ffffff18!important}
.blue .stat-icon,.violet .stat-icon,.cyan .stat-icon,.amber .stat-icon,.green .stat-icon,.rose .stat-icon{background:#493b29!important;color:#e3b46f!important}
.shortcut{background:#30372f!important;border-color:#465044!important;color:#e3b46f!important}.shortcut:hover{background:#3c4439!important}
.notice{background:#3a3022!important;border-color:#685132!important;color:#e8c994!important}.notice a,.panel-head a{color:#e0ad68!important}
.data th,.data td{border-color:#3b433c!important;color:#ddd5c8!important}.data th{background:#303831!important;color:#c1b69f!important}.data tr:hover{background:#303831!important}
.table-wrap{background:#252c27!important}.pill{background:#3a403a!important;color:#ddd5c8!important}
.pill.paid{background:#294638!important;color:#9bd3ad!important}.pill.pending{background:#514126!important;color:#e8c477!important}.pill.overdue{background:#512f30!important;color:#efaaaa!important}
.sub,.muted,.stat small,.stat em{color:#aaa99f!important}
@media(max-width:620px){.sidebar{background:#202722!important}}

</style></head><body>
<div class="layout"><aside class="sidebar"><div class="brand">SDMS</div>
<div class="nav-title">Менюи асосӣ</div><nav class="nav"><?php foreach($links as [$url,$label,$ico]):?><a class="<?=basename($_SERVER['PHP_SELF'])===$url?'active':''?>" href="<?=e($url)?>"><span class="ico"><?=e($ico)?></span><?=e($label)?></a><?php endforeach;?></nav>
<div class="side-bottom"><a href="logout.php">↪ &nbsp;Баромадан аз система</a></div></aside>
<main class="main"><header class="topbar"><div><div class="eyebrow">Student Dormitory Management System</div><h1>Панели асосӣ</h1><p class="sub">Идоракунӣ ва назорати фаъолияти хобгоҳ дар як ҷой.</p></div><div class="userbox"><div class="avatar"><?=e(mb_strtoupper(mb_substr($name,0,1,'UTF-8'),'UTF-8'))?></div><div><b><?=e($name)?></b><small><?=e($roleLabels[$role]??$role)?></small></div></div></header>
<section class="banner"><div><h2><?= $isStudent?'Хуш омадед ба кабинети донишҷӯ!':'Хуш омадед ба низоми идоракунии хобгоҳ!'?></h2><p><?= $isStudent?'Дар ин ҷо дархостҳо, ҷойгиршавӣ ва пардохтҳои худро назорат кунед.':'Вазъи донишҷӯён, ҳуҷраҳо, дархостҳо ва пардохтҳоро пайгирӣ намоед.'?></p></div><div class="date"><?=e($today)?></div></section>
<section class="stats"><?php foreach($cards as [$label,$value,$desc,$ico,$tone]):?><article class="stat <?=e($tone)?>"><div class="stat-top"><small><?=e($label)?></small><span class="stat-icon"><?=e($ico)?></span></div><strong><?=e((string)$value)?></strong><em><?=e($desc)?></em></article><?php endforeach;?></section>
<div class="grid"><section class="panel"><div class="panel-head"><h2><?= $isStudent?'Таърихи пардохтҳои ман':'Динамикаи пардохтҳои 6 моҳи охир'?></h2><span class="muted">Сомонӣ</span></div><div class="chart"><canvas id="paymentChart"></canvas></div></section>
<section class="panel"><div class="panel-head"><h2>Амалиётҳои зуд</h2><span class="muted">Дастрасии осон</span></div><div class="shortcuts"><?php foreach($links as [$url,$label,$ico]):if($url==='dashboard.php')continue;?><a class="shortcut" href="<?=e($url)?>"><span><?=e($ico)?></span><?=e($label)?></a><?php endforeach;?></div>
<?php if(!$isStudent && $pendingApps>0):?><div class="notice"><b><?=number_format($pendingApps)?> дархости нав</b> интизори баррасӣ аст. <a href="applications.php">Баррасӣ кардан →</a></div><?php elseif($isStudent):?><div class="notice">Барои дидани ҳолати дархостҳои худ ба бахши <a href="my_applications.php">«Дархостҳои ман»</a> гузаред.</div><?php else:?><div class="notice">Ҳоло дархости интизори баррасӣ мавҷуд нест.</div><?php endif;?></section></div>
<section class="panel" style="margin-top:18px"><div class="panel-head"><h2><?= $isStudent?'Пардохтҳои охирини ман':'Пардохтҳои охирин'?></h2><a class="muted" href="payments.php" style="text-decoration:none;color:#c58b45;font-weight:700">Ҳамаи пардохтҳо →</a></div><div class="table-wrap"><table class="data"><thead><tr><?php if(!$isStudent):?><th>Донишҷӯ</th><?php endif;?><th>Сана</th><th>Давра</th><th>Маблағ</th><th>Ҳолат</th></tr></thead><tbody><?php if(!$recentRows):?><tr><td class="empty" colspan="<?= $isStudent?4:5?>">Ҳоло маълумоти пардохт мавҷуд нест.</td></tr><?php else:foreach($recentRows as $r):?><tr><?php if(!$isStudent):?><td><?=e($r['full_name']??'—')?></td><?php endif;?><td><?=e($r['payment_date'])?></td><td><?=e($r['payment_period'])?></td><td><b><?=moneyFmt($r['amount'])?></b></td><td><span class="pill <?=e($r['status'])?>"><?=e(statusLabel($r['status']))?></span></td></tr><?php endforeach;endif;?></tbody></table></div></section>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>
const chartEl=document.getElementById('paymentChart');
if(window.Chart&&chartEl){new Chart(chartEl,{type:'line',data:{labels:<?=json_encode($labels,JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Пардохт',data:<?=json_encode($values)?>,borderColor:'#c58b45',backgroundColor:'rgba(197,139,69,.18)',fill:true,tension:.38,pointRadius:4,pointBackgroundColor:'#c58b45'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#eef1f6'}},x:{grid:{display:false}}}}});}
</script></body></html>
