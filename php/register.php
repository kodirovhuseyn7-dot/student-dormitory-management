<?php
require_once __DIR__.'/db.php'; require_once __DIR__.'/includes/functions.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim($_POST['full_name']??''); $username=trim($_POST['username']??'');
 $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??'');
 $password=$_POST['password']??''; $number=trim($_POST['student_number']??'');
 $faculty=trim($_POST['faculty']??''); $group=trim($_POST['study_group']??'');
 $course=(int)($_POST['course']??1);
 if(!$name||!$username||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<6||!$number||!$faculty||!$group||$course<1||$course>6) $error='Ҳамаи майдонҳоро дуруст пур кунед. Парол бояд на кам аз 6 аломат бошад.';
 else {
  try{
   $pdo->beginTransaction();
   $st=$pdo->prepare("INSERT INTO users(full_name,username,email,password_hash,role,phone) VALUES(?,?,?,?, 'student',?)");
   $st->execute([$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$phone]);
   $uid=(int)$pdo->lastInsertId();
   $st=$pdo->prepare("INSERT INTO students(user_id,student_number,faculty,study_group,course) VALUES(?,?,?,?,?)");
   $st->execute([$uid,$number,$faculty,$group,$course]);
   $pdo->commit(); flash('Бақайдгирӣ анҷом ёфт. Акнун ворид шавед.'); redirect('login.php');
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Ин username, email ё рақами донишҷӯӣ аллакай истифода шудааст.';}
 }
}
$pageTitle='Бақайдгирӣ'; require __DIR__.'/includes/header.php';
?>
<div class="panel auth"><h1>Бақайдгирии донишҷӯ</h1>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<?php foreach(['full_name'=>'Ному насаб','username'=>'Номи корбар','email'=>'Email','phone'=>'Телефон','student_number'=>'Рақами донишҷӯӣ','faculty'=>'Факултет','study_group'=>'Гурӯҳ'] as $k=>$label):?>
<div class="field"><label><?=$label?></label><input name="<?=$k?>" <?= $k==='email'?'type="email"':'' ?> required></div>
<?php endforeach;?>
<div class="field"><label>Курс</label><select name="course"><?php for($i=1;$i<=6;$i++):?><option><?=$i?></option><?php endfor;?></select></div>
<div class="field"><label>Парол</label><input type="password" name="password" minlength="6" required></div>
<button>Бақайдгирӣ</button></form></div>
<?php require __DIR__.'/includes/footer.php';?>
