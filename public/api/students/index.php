<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$students = [
 ['id'=>1,'full_name'=>'Алишер Раҳимов','student_code'=>'STU-001','course'=>2,'faculty'=>'Информатика','room'=>'101','status'=>'active'],
 ['id'=>2,'full_name'=>'Фаридун Саидов','student_code'=>'STU-002','course'=>3,'faculty'=>'Иқтисод','room'=>'205','status'=>'active'],
 ['id'=>3,'full_name'=>'Манижа Каримова','student_code'=>'STU-003','course'=>1,'faculty'=>'Ҳуқуқшиносӣ','room'=>'302','status'=>'active'],
 ['id'=>4,'full_name'=>'Сомон Набиев','student_code'=>'STU-004','course'=>4,'faculty'=>'Информатика','room'=>'102','status'=>'inactive'],
 ['id'=>5,'full_name'=>'Меҳрона Шарифова','student_code'=>'STU-005','course'=>2,'faculty'=>'Иқтисод','room'=>'201','status'=>'active'],
 ['id'=>6,'full_name'=>'Далер Ҳусейнов','student_code'=>'STU-006','course'=>1,'faculty'=>'Технология','room'=>null,'status'=>'no_room'],
 // Ин сабт барои санҷиши XSS аст. Он бояд танҳо ҳамчун матн намоиш дода шавад.
 ['id'=>7,'full_name'=>'<img src=x onerror=alert(1)> XSS TEST','student_code'=>'STU-XSS','course'=>1,'faculty'=>'Санҷишӣ','room'=>null,'status'=>'active']
];

$q = mb_strtolower(trim((string)($_GET['q'] ?? '')), 'UTF-8');
if ($q !== '') {
 $students = array_values(array_filter($students, static function($s) use ($q) {
  return str_contains(mb_strtolower($s['full_name'], 'UTF-8'), $q)
      || str_contains(mb_strtolower($s['student_code'], 'UTF-8'), $q);
 }));
}
echo json_encode($students, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
