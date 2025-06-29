<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $jatbi = new Jatbi($app);
    $setting = $app->getValueData('setting');

$app->router("/math", 'GET', function($vars) use ($app) {
    $vars['templates'] = 'grade';
    $vars['grades'] = $app->select("grades","*",["status"=>'A',"deleted"=>0]);
    echo $app->render('templates/frontend/category.html', $vars);
});

$app->router("/", 'GET', function($vars) use ($app) {
    if($app->getSession("accounts")){
        $name = $app->get("accounts", "name", ["id" => $app->getSession("accounts")['id']]);
        // Lấy danh sách 6 lessons có id trong ganday
        $ganday = $app->select("test", "id_lesson", [
            "id_account" => $app->getSession("accounts")['id'],
            "GROUP" => "id_lesson",
            "ORDER" => ["date" => "DESC"],
            "LIMIT" => 6
        ]);
        $lessons = [];
        if (!empty($ganday)) {
            foreach ($ganday as $item) {
                // If $item is an int, treat it as id_lesson directly
                $id_lesson = is_array($item) && isset($item['id_lesson']) ? $item['id_lesson'] : $item;
                $lesson = $app->get("lessons", "*", ["id" => $id_lesson]);
                if ($lesson) {
                    $lessons[] = $lesson;
                }
            }
        }
        $units = $app->select("units", "*", [
            "grade" => 1,
            "status" => 'A',
            "deleted" => 0,
            "ORDER" => ["id" => "ASC"]
        ]);
        // Lấy 4 id_lesson có số lần xuất hiện nhiều nhất từ bảng test, chỉ lấy các lesson có unit thuộc $units
        $unitIds = array_column($units, 'id');
        $unitIdsStr = implode(',', array_map('intval', $unitIds));
        $ganday2 = $app->query("
            SELECT id_lesson
            FROM test
            WHERE id_lesson IN (
            SELECT id FROM lessons WHERE unit IN ($unitIdsStr)
            )
            GROUP BY id_lesson
            ORDER BY COUNT(*) DESC
            LIMIT 4
        ")->fetchAll(PDO::FETCH_COLUMN);
        $lessons2 = [];
        if (!empty($ganday2)) {
            foreach ($ganday2 as $item) {
                // If $item is an int, treat it as id_lesson directly
                $id_lesson = is_array($item) && isset($item['id_lesson']) ? $item['id_lesson'] : $item;
                $lesson = $app->get("lessons", "*", ["id" => $id_lesson]);
                if ($lesson) {
                    $lessons2[] = $lesson;
                }
            }
        }
        $grades = $app->select("grades", "*", ["status" => 'A', "deleted" => 0]);

        $vars['grades'] = $grades;
        $vars['lessons2'] = $lessons2;
        $vars['lessons'] = $lessons;
        $vars['name'] = $name;
        echo $app->render('templates/frontend/homeuser.html', $vars);
    }
    else {
        $app->redirect('/math');
    }
});

$app->router("/", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    
    // Get grade_id from POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $gradeId = isset($input['grade_id']) ? intval($input['grade_id']) : 1;
    
    $units = $app->select("units", "*", [
        "grade" => $gradeId,
        "status" => 'A',
        "deleted" => 0,
        "ORDER" => ["id" => "ASC"]
    ]);
    
    // Get 4 most popular lessons from selected grade
    $unitIds = array_column($units, 'id');
    $lessons2 = [];
    
    if (!empty($unitIds)) {
        $unitIdsStr = implode(',', array_map('intval', $unitIds));
        $ganday2 = $app->query("
            SELECT id_lesson
            FROM test
            WHERE id_lesson IN (
                SELECT id FROM lessons WHERE unit IN ($unitIdsStr)
            )
            GROUP BY id_lesson
            ORDER BY COUNT(*) DESC
            LIMIT 4
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($ganday2)) {
            foreach ($ganday2 as $item) {
                $id_lesson = is_array($item) && isset($item['id_lesson']) ? $item['id_lesson'] : $item;
                $lesson = $app->get("lessons", "*", ["id" => $id_lesson]);
                if ($lesson) {
                    $lessons2[] = $lesson;
                }
            }
        }
    }
    
    echo json_encode(['status' => 'success', 'lessons' => $lessons2]);
});

$app->router("/profile", 'GET', function($vars) use ($app) {
    if($app->getSession("accounts")) {
        $vars['account'] = $app->get("accounts","*",["id"=>$app->getSession("accounts")['id']]);
        echo $app->render('templates/frontend/frofile.html', $vars);
    } else echo $app->render('templates/error.html', $vars);
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
            $count = $app->count("lessons",["unit"=>$unit["id"],"status"=>'A',"deleted"=>0]);
            if(($totalGroup + $count > $groupSize) && !empty($group[$i]) && $i < 3 ) {
                $totalGroup = 0;
                $i++;
            }
            $group[$i][] = $unit["id"];
            $totalGroup += $count;
        }
        $vars['grade'] = $grade;
        $vars['countLessons'] = $countLessons;
        $vars['groupSize'] = $groupSize;
        $vars['group'] = $group;
        echo $app->render('templates/frontend/category.html', $vars);
    } else echo $app->render('templates/error.html', $vars);
});

$app->router("/information-edit/", 'GET', function($vars) use ($app, $jatbi) {
    $data = $app->get("accounts","*",["id"=>$app->getSession("accounts")['id'],"status"=>'A',"deleted"=>0]);
    if($data) {
        $vars['title'] = "Sửa thông tin";
        $vars['data'] = $data;
        echo $app->render('templates/frontend/information-edit.html', $vars, 'global');
    } else echo $app->render('templates/common/error-modal.html', $vars, 'global');
});

$app->router("/information-edit/", 'POST', function($vars) use ($app, $jatbi) {
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
    $app->update("accounts",$insert,["id"=>$app->getSession("accounts")['id']]);
    echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
});

$app->router("/change-password", 'GET', function($vars) use ($app, $jatbi) {
    $data = $app->get("accounts","*",["id"=>$app->getSession("accounts")['id'],"status"=>'A',"deleted"=>0]);
    if($data) {
        $vars['title'] = "Đổi mật khẩu";
        echo $app->render('templates/frontend/change-password.html', $vars, 'global');
    } else echo $app->render('templates/common/error-modal.html', $vars, 'global');
});

$app->router("/change-password", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    $data = $app->get("accounts","*",["id"=>$app->getSession("accounts")['id'],"status"=>'A',"deleted"=>0]);
    if($app->xss($_POST['password_old'])=='' || $app->xss($_POST['password_new'])=='' || $app->xss($_POST['password_confirm'])==''){
        echo json_encode(['status'=>'error','content'=>$jatbi->lang("Vui lòng không để trống")]);
        exit;
    }
    if(!password_verify($app->xss($_POST['password_old']), $data['password'])){
        echo json_encode(['status'=>'error','content'=>$jatbi->lang("Mật khẩu cũ không chính xác")]);
        exit;
    }
    if($app->xss($_POST['password_new'])!=$app->xss($_POST['password_confirm'])){
        echo json_encode(['status'=>'error','content'=>$jatbi->lang("Mật khẩu mới không chính xác")]);
        exit;
    }
    $insert = [
        "password"      => password_hash($app->xss($_POST['password_confirm']), PASSWORD_DEFAULT),
    ];
    $app->update("accounts",$insert,["id"=>$data['id']]);
    echo json_encode(['status' => 'success','content' => $jatbi->lang('Cập nhật thành công')]);
});

?>