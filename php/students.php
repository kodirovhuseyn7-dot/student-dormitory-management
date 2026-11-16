<?php
require_once __DIR__.'/db.php';require_once __DIR__.'/includes/functions.php';require_role(['admin','manager']);
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)($_POST['id']??0);$st=$pdo->prepare("DELETE FROM students WHERE id=?");$st->execute([$id]);flash('Донишҷӯ нест карда шуд.');redirect('students.php');}
$rows=$pdo->query("SELECT s.*,u.full_name,u.email,u.phone FROM students s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC")->fetchAll();
$pageTitle='Донишҷӯён';require __DIR__.'/includes/header.php';?>
<h1>Донишҷӯён</h1><div class="actions"><a class="btn" href="add_student.php">+ Иловаи донишҷӯ</a></div>
<div class="table-wrap"><table><tr><th>ID</th><th>Ному насаб</th><th>Рақами донишҷӯӣ</th><th>Факултет</th><th>Гурӯҳ</th><th>Курс</th><th>Телефон</th><th>Амалиёт</th></tr>
<?php foreach($rows as $r):?><tr><td><?=$r['id']?></td><td><?=e($r['full_name'])?></td><td><?=e($r['student_number'])?></td><td><?=e($r['faculty'])?></td><td><?=e($r['study_group'])?></td><td><?=$r['course']?></td><td><?=e($r['phone'])?></td><td><form method="post" onsubmit="return confirm('Нест карда шавад?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="danger">Нест кардан</button></form></td></tr><?php endforeach;?></table></div>
<?php require __DIR__.'/includes/footer.php';?>
