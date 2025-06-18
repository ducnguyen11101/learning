<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $jatbi = new Jatbi($app);
    $setting = $app->getValueData('setting');

$app->router("/", 'GET', function($vars) use ($app) {
    $vars['math'] = '1';
    $vars['templates'] = '123';
    // Lấy các trường cần thiết từ bảng grades, chỉ lấy các bản ghi chưa xóa và đang hoạt động
    $gradesRaw = $app->select('grades', '*', [
        'deleted' => 0,
        'status' => 0,
        'ORDER' => ['location' => 'ASC']
    ]);
    $gradeColors = [
        0 => '#FF9E40', // Lớp 1 - cam
        1 => '#FF6B6B', // Lớp 2 - đỏ
        2 => '#4ECDC4', // Lớp 3 - xanh ngọc
        3 => '#45B7D1', // Lớp 4 - xanh dương nhạt
        4 => '#A37EBA', // Lớp 5 - tím
        5 => '#FF8A65', // Lớp 6 - cam nhạt
        6 => '#7986CB', // Lớp 7 - xanh tím
        7 => '#4DB6AC', // Lớp 8 - xanh ngọc đậm
        8 => '#9575CD', // Lớp 9 - tím nhạt
        9 => '#FFD600', // Lớp 10 - vàng tươi (đổi từ cam sang vàng)
        10 => '#81C784', // Lớp 11 - xanh lá
        11 => '#E57373', // Lớp 12 - đỏ nhạt
    ];
    $gradeSlugs = [
        0 => '0',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10',
        11 => '11',
    ];
    // Nếu có trường skill_count thì lấy, không thì hardcode hoặc để 0
    $skillCounts = [
        0 => 169,
        1 => 359,
        2 => 345,
        3 => 332,
        4 => 368,
        5 => 381,
        6 => 384,
        7 => 377,
        8 => 351,
        9 => 342,
        10 => 335,
        11 => 328
    ];
    $grades = [];
    foreach ($gradesRaw as $g) {
        $idx = $g['location'];
        $grades[] = [
            'id' => $g['id'],
            'name' => $g['name'],
            'desc' => $g['description'],
            'color' => $gradeColors[$idx] ?? '#888',
            'slug' => $gradeSlugs[$idx] ?? 'lop' . ($idx + 1),
            'skill_count' => $skillCounts[$idx] ?? 0
        ];
    }
    $htmlContent = $app->get("saved_pages", "*", ["id" => 1]);
    echo $app->render('templates/home/home.html', ['grades' => $grades, 'html' => $htmlContent]);
    require_once 'footerhome.php'; 
});

// Thêm router cho trang grade
$app->router("/grade", 'GET', function($vars) use ($app) {
    require_once 'headerhome.php';
    $grade = strtolower($_GET['grade'] ?? '1');

    // Nếu grade là chuỗi (first, second...), chuyển về số nếu cần
    $gradeMap = [
        'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5,
        'sixth' => 6, 'seventh' => 7, 'eighth' => 8, 'ninth' => 9, 'tenth' => 10,
        'prek' => 0, 'kindergarten' => 0
    ];
    $gradeId = is_numeric($grade) ? intval($grade) : ($gradeMap[$grade] ?? 1);

    $units = [];
    $unitRows = $app->select('units', '*', ['grade' => $gradeId, 'ORDER' => ['id' => 'ASC']]);
    foreach ($unitRows as $unit) {
        $lessons = $app->select('lessons', '*', ['unit' => $unit['id'], 'ORDER' => ['id' => 'ASC']]);
        $units[] = [
            'id' => $unit['id'],
            'name' => $unit['name'],
            'lessons' => $lessons
        ];
    }

    echo $app->render('templates/home/grades.html', [
        'grade' => $grade,
        'units' => $units
    ]);
    require_once 'footerhome.php';
});

$app->router("/profile", 'GET', function($vars) use ($app) {
    if($app->getSession("accounts")) {
        $vars['account'] = $app->get("accounts","*",["id"=>$app->getSession("accounts")['id']]);
        echo $app->render('templates/frontend/frofile.html', $vars);
    } else echo $app->render('templates/error.html', $vars);
});

$app->router("/math", 'GET', function($vars) use ($app) {
    $vars['templates'] = 'grade';
    $vars['grades'] = $app->select("grades","*",["status"=>'A',"deleted"=>0]);
    echo $app->render('templates/frontend/category.html', $vars);
});

$app->router("/math/units/{grade}", 'GET', function($vars) use ($app) {
    $vars['templates'] = 'unit';
    $grade = $app->get("grades","*",["id"=>$vars['grade'],"status"=>'A',"deleted"=>0]);
    $units = $app->select("units","*",["grade"=>$vars['grade'],"status"=>'A',"deleted"=>0]);
    if($grade) {
        $countLessons = $app->count("lessons",[
             "[>]units" => ["unit" => "id"],
        ],"*",[
            "units.grade"=>'1'
        ]);
        $groupSize = ceil($countLessons / 3);
        $group[1] = [];
        $group[2] = [];
        $group[3] = [];
        $i = 1;

        $totalGroup = 0;
        foreach ($units as $unit) {
            $count = $app->count("lessons",["unit"=>$unit["id"]]);
            if(($totalGroup + $count > $groupSize) && !empty($group[$i]) && $i < 3 ) {
                $totalGroup = 0;
                $i++;
            }
            $group[$i][] = $unit["id"];
            $totalGroup += $count;
        }
        $vars['title'] = $grade['name'];
        $vars['countLessons'] = $countLessons;
        $vars['groupSize'] = $groupSize;
        $vars['group'] = $group;
        echo $app->render('templates/frontend/category.html', $vars);
    } else echo $app->render('templates/error.html', $vars);
});

$app->router("/information-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
    $data = $app->get("accounts","*",["id"=>$vars['id'],"status"=>'A',"deleted"=>0]);
    if($data) {
        $vars['title'] = "Sửa thông tin";
        $vars['data'] = $data;
        echo $app->render('templates/frontend/information-edit.html', $vars, 'global');
    } else echo $app->render('templates/common/error-modal.html', $vars, 'global');
});

$app->router("/information-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
  
    if($app->xss($_POST['name'])=='' || $app->xss($_POST['birthday'])=='' || $app->xss($_POST['email'])=='' || $app->xss($_POST['phone'])==''){
        echo json_encode(['status'=>'error','content'=>$jatbi->lang("Vui lòng không để trống")]);
        exit;
    }
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status'=>'error','content'=>$jatbi->lang('Email không đúng')]);
        exit;
    }
    $insert = [
        "name"          => $app->xss($_POST['name']),
        "birthday"      => $app->xss($_POST['birthday']),
        "gender"        => $app->xss($_POST['gender']),
        "email"         => $app->xss($_POST['email']),
        "phone"         => $app->xss($_POST['phone']),
    ];
    $app->update("accounts",$insert,["id"=>$vars['id']]);
    echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
});


// $app->router("/math", 'GET', function($vars) use ($app) {
//     $vars['templates'] = 'grade';
//     $vars['grades'] = $app->select("grades","*",["status"=>'A',"deleted"=>0]);
//     echo $app->render('templates/frontend/category.html', $vars);
// });

?>