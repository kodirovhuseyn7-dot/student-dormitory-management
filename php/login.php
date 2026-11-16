<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/includes/functions.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $username=trim($_POST['username']??'');
    $password=$_POST['password']??'';
    $st=$pdo->prepare("SELECT id,full_name,username,email,password_hash,role FROM users WHERE username=? OR email=? LIMIT 1");
    $st->execute([$username,$username]); $u=$st->fetch();
    if ($u && password_verify($password,$u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['full_name'],'role'=>$u['role']];
        redirect('dashboard.php');
    }
    $error='Номи корбар ё парол нодуруст аст.';
}
$pageTitle='Воридшавӣ'; require __DIR__.'/includes/header.php';
?>
<div class="panel auth"><h1>Воридшавӣ</h1>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="field"><label>Номи корбар ё Email</label><input name="username" required></div>
<div class="field"><label>Парол</label><input type="password" name="password" required></div>
<button>Ворид шудан</button>
<p class="muted">Ҳисоб надоред? <a href="register.php">Бақайдгирӣ</a></p>
</form></div>
<?php require __DIR__.'/includes/footer.php'; ?>
