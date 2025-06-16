<?php
$app->router("/analytics", 'GET', function($vars) use ($app) {
    require_once __DIR__ . '/../home/headerhome.php';
    $tests = $app->select("test", "*", ["id_account" => 16]);
    // Tính tổng của trường 'answer' trong bảng 'test'
    $totalAnswers = 0;
    $totaltime = 0;
    $lessonIds = [];
    foreach ($tests as $t) {
        if (isset($t['answer'])) {
            $totalAnswers += $t['answer'];
        }
        if (isset($t['time'])) {
            $totaltime += $t['time'];
        }
        if (isset($t['id_lesson'])) {
            $lessonIds[$t['id_lesson']] = true;
        }
    }
    $totalLessons = count($lessonIds);
    $hours = intdiv($totaltime, 3600);
    $remainder = $totaltime % 3600;
    $minutes = intdiv($remainder, 60);
    $time = [
        'hours' => $hours,
        'minutes' => $minutes,
    ];
    // Tạo mảng các ngày từ 30 ngày trước đến hôm nay, cách nhau 2 ngày
    $dates = [];
    $start = new DateTime('-28 days');
    $end = new DateTime('today');
    // Đảm bảo lấy cả hôm nay
    for ($d = $start; $d <= $end; $d->modify('+2 days')) {
        $dates[] = $d->format('d');
    }
    // Nếu ngày cuối cùng chưa phải hôm nay, thêm hôm nay vào
    if (end($dates) !== $end->format('d')) {
        $dates[] = $end->format('d');
    }
    $vars['date'] = $dates;
    $vars['totalLessons'] = $totalLessons;
    $vars['totalAnswers'] = $totalAnswers;
    $vars['time'] = $time;
    echo $app->render('templates/lessons/analytics.html',$vars);
});

$app->router("/analytics", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    
});
?>