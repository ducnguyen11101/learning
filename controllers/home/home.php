<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $jatbi = new Jatbi($app);
    $setting = $app->getValueData('setting');

$app->router("/", 'GET', function($vars) use ($app) {
    $vars['templates'] = 'grade';
    $vars['grades'] = $app->select("grades","*",["status"=>'A',"deleted"=>0]);
    echo $app->render('templates/frontend/category.html', $vars);
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