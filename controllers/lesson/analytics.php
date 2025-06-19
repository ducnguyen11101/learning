<?php
$app->router("/analytics", 'GET', function($vars) use ($app) {
    require_once __DIR__ . '/../home/headerhome.php';
    $thirtyDaysAgo = date('Y-m-d', strtotime('-29 days'));
    $tests = $app->select("test", "*", [
        "id_account" => 16,
        "date[>=]" => $thirtyDaysAgo
    ]);
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
    $start = new DateTime('-29 days');
    $end = new DateTime('today');
    // Đảm bảo lấy cả hôm nay
    for ($d = $start; $d <= $end; $d->modify('+1 days')) {
        $dates[] = $d->format('d');
    }
    // Nếu ngày cuối cùng chưa phải hôm nay, thêm hôm nay vào
    if (end($dates) !== $end->format('d')) {
        $dates[] = $end->format('d');
    }
    // Khởi tạo mảng datas với key là ngày, value là tổng answer của ngày đó
    $datas = array_fill_keys($dates, 0);
    foreach ($tests as $test) {
        if (isset($test['date']) && isset($test['answer'])) {
            $day = date('d', strtotime($test['date']));
            if (array_key_exists($day, $datas)) {
                $datas[$day] += $test['answer'];
            }
        }
    }
    // Đảm bảo datas là mảng tuần tự theo $dates
    $datas = array_values($datas);

    $lessons = [];
    foreach ($tests as $test) {
        if (isset($test['id_lesson'])) {
            $id = $test['id_lesson'];
            // Nếu id_lesson chưa có trong $lessons thì thêm vào
            if (!isset($lessons[$id])) {
                $lessons[$id] = [
                    'id' => $id,
                    'number' => 0
                ];
            }
            // Tăng biến number lên 1
            $lessons[$id]['number'] += 1;
        }
    }
    // Lấy 5 lesson có number lớn nhất
    usort($lessons, function($a, $b) {
        return $b['number'] <=> $a['number'];
    });
    $top5 = array_slice($lessons, 0, 5);
    $topLessons = array_column($top5, 'number');
    $topLessonIds = [];
    foreach ($top5 as $topLesson) {
        $unit = $app->get("lessons", "name", ["id" => $topLesson['id']]);
        $topLessonIds[] = $unit;
    }
    $topLessonIds[5] ="Khác";

    // Tính tổng các number còn lại
    $otherLessons = array_slice($lessons, 5);
    $otherSum = 0;
    foreach ($otherLessons as $lesson) {
        $otherSum += $lesson['number'];
    }
    $topLessons[] = $otherSum; // Thêm giá trị thứ 6

    $vars['topLessonIds'] = $topLessonIds;
    $vars['lessons'] = $topLessons;
    $vars['datas'] = $datas;
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