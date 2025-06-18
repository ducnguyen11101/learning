<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $jatbi = new Jatbi($app);
    $setting = $app->getValueData('setting');

    $app->router("/learning/grades", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Quản lý Danh mục");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/grades.html', $vars);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'position';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'ASC';
        $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "grades.label[~]" => $searchValue,
                    "grades.name[~]" => $searchValue,
                ],
                "grades.status[<>]" => $status,
                "grades.deleted" => 0,
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];

        $count = $app->count("grades",[
            "AND" => $where['AND'],
        ]);
        $app->select("grades", [
            'grades.id',
            'grades.label',
            'grades.name',
            'grades.status',
            'grades.position',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox"  => $app->component("box",["data"=>$data['id']]),
                "label"     => $data['label'],
                "name"      => '<a class="text-primary" href="/learning/units/'.$data['id'].'" data-pjax>' . $data['name'] .'</a>',
                "status" => $app->component("status",["url"=>"/learning/grades-status/".$data['id'],"data"=>$data['status'],"permission"=>['courseCategoryManagement']]),
                "action" => $app->component("action",[
                    "button" => [
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/grades-edit/'.$data['id'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/grades-deleted?box='.$data['id'], 'data-action' => 'modal']
                        ],
                    ]
                ]),
            ];
        });
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $datas ?? []
        ]);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("grades","*",["id"=>$vars['id'],"deleted"=>0]);
        if($data>1){
            if($data>1){
                if($data['status']==='A'){
                    $status = "D";
                } 
                elseif($data['status']==='D'){
                    $status = "A";
                }
                $app->update("grades",["status"=>$status],["id"=>$data['id']]);
                // $jatbi->logs('accounts','accounts-status',$data);
                echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
            }
            else {
                echo json_encode(['status'=>'error','content'=>$jatbi->lang("Cập nhật thất bại"),]);
            }
        }
        else {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Không tìm thấy dữ liệu")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Thêm Lớp học");
        $vars['data'] = [
            "status" => 'A',
        ];
        echo $app->render('templates/learning/grades-post.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);

        $label = $app->xss($_POST['label']);
        $name = $app->xss($_POST['name']);
        if($label =='' || $name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("grades", ["label" => $label,"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Nhãn đã tồn tại")]);
            exit;
        }
        if($app->has("grades", ["name" => $name,"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        
        $position = $app->max("grades","position")??0;
        $insert = [
            "label"     => $app->xss($_POST['label']),
            "name"      => $app->xss($_POST['name']),
            "status"    => $app->xss($_POST['status']),
            "position"  => ($position > 0) ? $position + 1 : 0,
        ];
        $app->insert("grades",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Thêm thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa Lớp học");
        $vars['data'] = $app->get("grades","*",["id"=>$vars['id'],"deleted"=>0]);
        if($vars['data']>1){
            echo $app->render('templates/learning/grades-post.html', $vars, 'global');
        }
        else {
            echo $app->render('templates/common/error-modal.html', $vars, 'global');
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("grades","*",["id"=>$vars['id'],"deleted"=>0]);
        $label = $app->xss($_POST['label']);
        $name = $app->xss($_POST['name']);
        if($label =='' || $name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("grades", ["label" => $label,"id[!]" => $vars['id'],"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Nhãn đã tồn tại")]);
            exit;
        }
        if($app->has("grades", ["name" => $name,"id[!]" => $vars['id'],"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        $insert = [
            "label"     => $app->xss($_POST['label']),
            "name"      => $app->xss($_POST['name']),
            "status"    => $app->xss($_POST['status']),
        ];
        $app->update("grades",$insert,["id"=>$data['id']]);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-reorder", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sắp xếp vị trí Lớp học");
        $vars['datas'] = $app->select("grades","*",["deleted"=>0,"ORDER" => ["position" => "ASC"]]);
        echo $app->render('templates/learning/category-reorder.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-reorder", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);

        $ids = explode(',', $app->xss($_POST['order']));


        foreach ($ids as $position => $id) {
            $app->update('grades', ['position' => $position], ['id' => $id]);
        }
        // $app->insert("grades",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa Lớp học");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/grades-deleted", 'POST', function($vars) use ($app,$jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $boxid = explode(',', $app->xss($_GET['box']));
        $datas = $app->select("grades","*",["id"=>$boxid,"deleted"=>0]);
        if(count($datas)>0){
            foreach($datas as $data){
                $app->update("grades",["deleted"=> 1],["id"=>$data['id']]);
                $name[] = $data['name'];
            }
            // $jatbi->logs('accounts','accounts-deleted',$datas);
            // $jatbi->trash('/users/accounts-restore',"Tài khoản: ".implode(', ',$name),["database"=>'accounts',"data"=>$boxid]);
            echo json_encode(['status'=>'success',"content"=>$jatbi->lang("Cập nhật thành công")]);
        }
        else {
            echo json_encode(['status'=>'error','content'=>$jatbi->lang("Có lỗi xẩy ra")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    //--------------------------------------------------------------------
    $app->router("/learning/topics", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Quản lý Danh mục");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/topics.html', $vars);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'id';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'DESC';
        $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "topics.name[~]" => $searchValue,
                ],
                "topics.status[<>]" => $status,
                "topics.deleted" => 0,
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];
        // if (!empty($permission)) {
        //     $where["AND"]["accounts.permission"] = $permission;
        // }
        $count = $app->count("topics",[
            "AND" => $where['AND'],
        ]);
        $app->select("topics", [
            //     "[>]permissions" => ["permission" => "id"]
            // ], 
            // [
            'topics.id',
            'topics.name',
            'topics.status',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox" => $app->component("box",["data"=>$data['id']]),
                "name" => $data['name'],
                "totalTopics" => 4,
                "totalSkills" => 2,
                "status" => $app->component("status",["url"=>"/learning/topics-status/".$data['id'],"data"=>$data['status'],"permission"=>['courseCategoryManagement']]),
                "action" => $app->component("action",[
                    "button" => [
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/skills-edit/'.$data['id'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/skills-deleted?box='.$data['id'], 'data-action' => 'modal']
                        ],
                    ]
                ]),
            ];
        });
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $datas ?? []
        ]);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("topics","*",["id"=>$vars['id'],"deleted"=>0]);
        if($data>1){
            if($data>1){
                if($data['status']==='A'){
                    $status = "D";
                } 
                elseif($data['status']==='D'){
                    $status = "A";
                }
                $app->update("topics",["status"=>$status],["id"=>$data['id']]);
                // $jatbi->logs('accounts','accounts-status',$data);
                echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
            }
            else {
                echo json_encode(['status'=>'error','content'=>$jatbi->lang("Cập nhật thất bại"),]);
            }
        }
        else {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Không tìm thấy dữ liệu")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Thêm Chủ đề");
        $vars['data'] = [
            "status" => 'A',
        ];
        echo $app->render('templates/learning/topics-post.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $name = $app->xss($_POST['name']);
        if($app->xss($_POST['name'])==''){
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        }
        if($app->has("topics", ["name" => $name])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        $insert = [
            "name"          => $app->xss($_POST['name']),
            "status"        => $app->xss($_POST['status']),
        ];
        $app->insert("topics",$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Thêm thành công")]);
        // $jatbi->logs('permission','permission-add',$insert);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa Chủ đề");
        $vars['data'] = $app->get("topics","*",["id"=>$vars['id'],"deleted"=>0]);
        if($vars['data']>1){
            echo $app->render('templates/learning/topics-post.html', $vars, 'global');
        }
        else {
            echo $app->render('templates/common/error-modal.html', $vars, 'global');
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("topics","*",["id"=>$vars['id'],"deleted"=>0]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("topics", ["name" => $name]) && $data["name"]!=$name) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "status"    => $app->xss($_POST['status']),
        ];
        $app->update("topics",$insert,["id"=>$vars['id']]);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa Chủ đề");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/topics-deleted", 'POST', function($vars) use ($app,$jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $boxid = explode(',', $app->xss($_GET['box']));
        $datas = $app->select("topics","*",["id"=>$boxid,"deleted"=>0]);
        if(count($datas)>0){
            foreach($datas as $data){
                $app->update("topics",["deleted"=> 1],["id"=>$data['id']]);
                $name[] = $data['name'];
            }
            // $jatbi->logs('accounts','accounts-deleted',$datas);
            // $jatbi->trash('/users/accounts-restore',"Tài khoản: ".implode(', ',$name),["database"=>'accounts',"data"=>$boxid]);
            echo json_encode(['status'=>'success',"content"=>$jatbi->lang("Cập nhật thành công")]);
        }
        else {
            echo json_encode(['status'=>'error','content'=>$jatbi->lang("Có lỗi xẩy ra")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Quản lý Danh mục");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/skills.html', $vars);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'id';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'DESC';
        $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "skills.name[~]" => $searchValue,
                ],
                "skills.status[<>]" => $status,
                "skills.deleted" => 0,
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];
        $count = $app->count("skills",[
            "AND" => $where['AND'],
        ]);
        $app->select("skills", [
            'skills.id',
            'skills.name',
            'skills.status',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox" => $app->component("box",["data"=>$data['id']]),
                "name" => $data['name'],
                "status" => $app->component("status",["url"=>"/learning/skills-status/".$data['id'],"data"=>$data['status'],"permission"=>['courseCategoryManagement']]),
                "action" => $app->component("action",[
                    "button" => [
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/skills-edit/'.$data['id'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/skills-deleted?box='.$data['id'], 'data-action' => 'modal']
                        ],
                    ]
                ]),
            ];
        });
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $datas ?? []
        ]);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("skills","*",["id"=>$vars['id'],"deleted"=>0]);
        if($data>1){
            if($data>1){
                if($data['status']==='A'){
                    $status = "D";
                } 
                elseif($data['status']==='D'){
                    $status = "A";
                }
                $app->update("skills",["status"=>$status],["id"=>$data['id']]);
                // $jatbi->logs('accounts','accounts-status',$data);
                echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
            }
            else {
                echo json_encode(['status'=>'error','content'=>$jatbi->lang("Cập nhật thất bại"),]);
            }
        }
        else {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Không tìm thấy dữ liệu")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Thêm Kỹ năng");
        $vars['data'] = [
            "status" => 'A',
        ];
        echo $app->render('templates/learning/skills-post.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $name = $app->xss($_POST['name']);
        if($app->xss($_POST['name'])==''){
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        }
        if($app->has("skills", ["name" => $name])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        $insert = [
            "name"          => $app->xss($_POST['name']),
            "status"        => $app->xss($_POST['status']),
        ];
        $app->insert("skills",$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Thêm thành công")]);
        // $jatbi->logs('permission','permission-add',$insert);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa Kĩ năng");
        $vars['data'] = $app->get("skills","*",["id"=>$vars['id'],"deleted"=>0]);
        if($vars['data']>1){
            echo $app->render('templates/learning/skills-post.html', $vars, 'global');
        }
        else {
            echo $app->render('templates/common/error-modal.html', $vars, 'global');
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("topics","*",["id"=>$vars['id'],"deleted"=>0]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("topics", ["name" => $name]) && $data["name"]!=$name) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
            exit;
        }
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "status"    => $app->xss($_POST['status']),
        ];
        $app->update("skills",$insert,["id"=>$vars['id']]);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);
    
    $app->router("/learning/skills-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa Kỹ năng");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/skills-deleted", 'POST', function($vars) use ($app,$jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $boxid = explode(',', $app->xss($_GET['box']));
        $datas = $app->select("skills","*",["id"=>$boxid,"deleted"=>0]);
        if(count($datas)>0){
            foreach($datas as $data){
                $app->update("skills",["deleted"=> 1],["id"=>$data['id']]);
                $name[] = $data['name'];
            }
            // $jatbi->logs('accounts','accounts-deleted',$datas);
            // $jatbi->trash('/users/accounts-restore',"Tài khoản: ".implode(', ',$name),["database"=>'accounts',"data"=>$boxid]);
            echo json_encode(['status'=>'success',"content"=>$jatbi->lang("Cập nhật thành công")]);
        }
        else {
            echo json_encode(['status'=>'error','content'=>$jatbi->lang("Có lỗi xẩy ra")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/reorderCategories", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sắp xếp Danh mục");
        $units = $app->select("units","*",["grade"=>'1']);
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
            // if($count + $countLessons > $groupSize && count($group[$i])>1 && $i < 3) {
            //     $i++;
            //     $count = 0;
            // }
            // if(($totalGroup + $count > $groupSize) && count($group[$i]) > 0 && $i < 3 ) {
            //     $totalGroup = 0;
            //     $i++;
            // }
            if(($totalGroup + $count > $groupSize) && !empty($group[$i]) && $i < 3 ) {
                $totalGroup = 0;
                $i++;
            }
            // $group[1][] = $unit["id"];
            $group[$i][] = $unit["id"];
            $totalGroup += $count;
            // // $count += $countLessons;
        }


        $vars['units'] = $units;
        $vars['countLessons'] = $countLessons;
        $vars['groupSize'] = $groupSize;
        $vars['group'] = $group;
        // $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/reorderCategories.html', $vars);
    })->setPermissions(['courseCategoryManagement']);

    // $app->router("/sua/skills-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
    //     $vars['title'] = $jatbi->lang("Sửa");
    //     $vars['data'] = $app->get("skills","*",["id"=>$vars['id'],"deleted"=>0]);
    //     if($vars['data']>1){
    //         echo $app->render('templates/learning/reorder.html', $vars);
    //     }
    //     else {
    //         echo $app->render('templates/common/error-modal.html', $vars);
    //     }
    // })->setPermissions(['courseCategoryManagement']);

    $app->router("/sua/skills-edit", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa");
        $grade = $app->xss($_GET['grade']??'1');
        $unit = $app->xss($_GET['unit']??"");
        // $unit = explode(',', $app->xss($_GET['unit']));
        $vars['units'] = $app->select("units","*",["grade"=>$grade]);
        // if($unit) {
            $vars['lessons'] = $app->select("lessons","*",["unit"=>$unit]);
        // }
        // $vars['lessons'] = [];
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/reorder.html', $vars);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/test-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sắp xếp");
        $vars['data'] = [
            "status" => 'A',
        ];
        // echo $app->render('templates/learning/test-post.html', $vars, 'global');
        echo $app->render('templates/learning/test-post.html', $vars, 'global');

    })->setPermissions(['courseCategoryManagement']);


    // $app->router("/learning/grades-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
    //     $vars['title'] = $jatbi->lang("Sửa Lớp học");
    //     $vars['data'] = $app->get("grades","*",["id"=>$vars['id'],"deleted"=>0]);
    //     if($vars['data']>1){
    //         echo $app->render('templates/learning/grades-post.html', $vars, 'global');
    //     }
    //     else {
    //         echo $app->render('templates/common/error-modal.html', $vars, 'global');
    //     }
    // })->setPermissions(['courseCategoryManagement']);

    // $app->router("/learning/grades-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
    //     $app->header([
    //         'Content-Type' => 'application/json',
    //     ]);
    //     $data = $app->get("grades","*",["id"=>$vars['id'],"deleted"=>0]);
    //     $label = $app->xss($_POST['label']);
    //     $name = $app->xss($_POST['name']);
    //     if($label =='' || $name =='') {
    //         echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
    //         exit;
    //     } 
    //     if($app->has("grades", ["label" => $label]) && $data["label"]!=$label) {
    //         echo json_encode(["status"=>"error","content"=>$jatbi->lang("Nhãn đã tồn tại")]);
    //         exit;
    //     }
    //     if($app->has("grades", ["name" => $name]) && $data["name"]!=$name) {
    //         echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên đã tồn tại")]);
    //         exit;
    //     }
    //     $insert = [
    //         "label"     => $app->xss($_POST['label']),
    //         "name"      => $app->xss($_POST['name']),
    //         "status"    => $app->xss($_POST['status']),
    //     ];
    //     $app->update("grades",$insert,["id"=>$vars['id']]);
    //     // $jatbi->logs('learning','grades-add',$insert);
    //     echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
    //     exit;
    // })->setPermissions(['courseCategoryManagement']);

//--------------------------------------------------------------------------------------------


//--------------------------------------------------------------------------------------------
    $app->router("/learning/units/{grade}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['grade'] = $app->get("grades","*",["id"=>$vars['grade'],"deleted"=>0]);
        $vars['title'] = $jatbi->lang("Quản lý Danh mục");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        if($vars['grade']>0){
            echo $app->render('templates/learning/units.html', $vars);
        }
        else {
            echo $app->render('templates/common/error.html', $vars);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units/{grade}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'position';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'ASC';
        $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "units.name[~]" => $searchValue,
                ],
                "units.status[<>]" => $status,
                "units.deleted" => 0,
                "units.grade" => $vars["grade"],
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];

        $count = $app->count("units",[
            "AND" => $where['AND'],
        ]);
        $app->select("units", [
            'units.id',
            'units.name',
            'units.status',
            'units.position',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox"  => $app->component("box",["data"=>$data['id']]),
                "name"      => '<a class="text-primary" href="/learning/lessons/'.$data['id'].'">' . $data['name'] .'</a>',
                "status" => $app->component("status",["url"=>"/learning/units-status/".$data['id'],"data"=>$data['status'],"permission"=>['courseCategoryManagement']]),
                "action" => $app->component("action",[
                    "button" => [
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/units-edit/'.$data['id'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/units-deleted?box='.$data['id'], 'data-action' => 'modal']
                        ],
                    ]
                ]),
            ];
        });
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $datas ?? []
        ]);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("units","*",["id"=>$vars['id'],"deleted"=>0]);
        if($data>1){
            if($data>1){
                if($data['status']==='A'){
                    $status = "D";
                } 
                elseif($data['status']==='D'){
                    $status = "A";
                }
                $app->update("units",["status"=>$status],["id"=>$data['id']]);
                // $jatbi->logs('accounts','accounts-status',$data);
                echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
            }
            else {
                echo json_encode(['status'=>'error','content'=>$jatbi->lang("Cập nhật thất bại"),]);
            }
        }
        else {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Không tìm thấy dữ liệu")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Thêm Chủ đề");
        $vars['grades'] = $app->select("grades","*",["deleted"=>'0',"ORDER" => ["position" => "ASC"]]);
        $vars['data'] = [
            "grade" => $app->xss($_GET['ingrade']),
            "status" => 'A',
        ];
        echo $app->render('templates/learning/units-post.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $grade = $app->get("grades","*",["id"=>$app->xss($_POST['grade']),"deleted"=>0]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("units", ["name" => $name,"grade" => $grade['id'],"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên trong lớp ". $grade['name'] ." đã tồn tại.")]);
            exit;
        }
        $position = $app->max("units","position",["grade"=>$app->xss($_POST['grade'])])??0;
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "grade"     => $app->xss($_POST['grade']),
            "status"    => $app->xss($_POST['status']),
            "position"  => ($position > 0) ? $position + 1 : 0,
        ];
        $app->insert("units",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Thêm thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa Chủ đề");
        $vars['grades'] = $app->select("grades","*",["deleted"=>'0',"ORDER" => ["position" => "ASC"]]);
        $vars['data'] = $app->get("units","*",["id"=>$vars['id'],"deleted"=>0]);
        if($vars['data']>1){
            echo $app->render('templates/learning/units-post.html', $vars, 'global');
        }
        else {
            echo $app->render('templates/common/error-modal.html', $vars, 'global');
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("units","*",["id"=>$vars['id'],"deleted"=>0]);
        $grade = $app->get("grades","*",["id"=>$app->xss($_POST['grade']),"deleted"=>0]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("units", ["name" => $name,"grade" => $data['grade'],"id[!]" => $vars['id'],"deleted" => '0'])) {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên trong lớp ". $grade['name'] ." đã tồn tại.")]);
            exit;
        }
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "grade"     => $app->xss($_POST['grade']),
            "status"    => $app->xss($_POST['status']),
        ];
        $app->update("units",$insert,["id"=>$data['id']]);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-reorder", 'GET', function($vars) use ($app, $jatbi) {
        $ingrade = $app->xss($_GET['ingrade']);
        $vars['title'] = $jatbi->lang("Sắp xếp vị trí Chủ đề");
        $vars['datas'] = $app->select("units","*",["deleted"=>0,"grade"=>$ingrade,"ORDER" => ["position" => "ASC"]]);
        
        echo $app->render('templates/learning/category-reorder.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-reorder", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);

        $ids = explode(',', $app->xss($_POST['order']));

        foreach ($ids as $position => $id) {
            $app->update('units', ['position' => $position], ['id' => $id]);
        }
        // $app->insert("grades",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa Chủ đề");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/units-deleted", 'POST', function($vars) use ($app,$jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $boxid = explode(',', $app->xss($_GET['box']));
        $datas = $app->select("units","*",["id"=>$boxid,"deleted"=>0]);
        if(count($datas)>0){
            foreach($datas as $data){
                $app->update("units",["deleted"=> 1],["id"=>$data['id']]);
                $name[] = $data['name'];
            }
            // $jatbi->logs('accounts','accounts-deleted',$datas);
            // $jatbi->trash('/users/accounts-restore',"Tài khoản: ".implode(', ',$name),["database"=>'accounts',"data"=>$boxid]);
            echo json_encode(['status'=>'success',"content"=>$jatbi->lang("Cập nhật thành công")]);
        }
        else {
            echo json_encode(['status'=>'error','content'=>$jatbi->lang("Có lỗi xẩy ra")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons/{unit}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['unit'] = $app->get("units","*",["id"=>$vars['unit'],"deleted"=>'0']);
        $vars['title'] = "Quản lý Danh mục";
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        if($vars['unit']>0){
            echo $app->render('templates/learning/lessons.html', $vars);
        }
        else {
            echo $app->render('templates/common/error.html', $vars);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons/{unit}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'position';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'ASC';
        $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "lessons.name[~]" => $searchValue,
                ],
                "lessons.status[<>]" => $status,
                "lessons.deleted" => 0,
                "lessons.unit" => $vars['unit'],
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];

        $count = $app->count("units",[
            "AND" => $where['AND'],
        ]);
        $app->select("lessons", [
            'lessons.id',
            'lessons.name',
            'lessons.status',
            'lessons.position',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox"  => $app->component("box",["data"=>$data['id']]),
                "name"      => '<a class="text-primary" href="/learning/unit/'.$data['id'].'">' . $data['name'] .'</a>',
                "status" => $app->component("status",["url"=>"/learning/lessons-status/".$data['id'],"data"=>$data['status'],"permission"=>['courseCategoryManagement']]),
                "action" => $app->component("action",[
                    "button" => [
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/lessons-edit/'.$data['id'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            'permission' => ['courseCategoryManagement'],
                            'action' => ['data-url' => '/learning/lessons-deleted?box='.$data['id'], 'data-action' => 'modal']
                        ],
                    ]
                ]),
            ];
        });
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $datas ?? []
        ]);
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("lessons","*",["id"=>$vars['id'],"deleted"=>0]);
        if($data>1){
            if($data>1){
                if($data['status']==='A'){
                    $status = "D";
                } 
                elseif($data['status']==='D'){
                    $status = "A";
                }
                $app->update("lessons",["status"=>$status],["id"=>$data['id']]);
                // $jatbi->logs('accounts','accounts-status',$data);
                echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
            }
            else {
                echo json_encode(['status'=>'error','content'=>$jatbi->lang("Cập nhật thất bại"),]);
            }
        }
        else {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Không tìm thấy dữ liệu")]);
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-add", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Thêm Bài học");
        $vars['units'] = $app->select("units","*",["deleted"=>'0',"ORDER" => ["position" => "ASC"]]);
        $vars['data'] = [
            "unit"   => $app->xss($_GET['inunit']),
            "status" => 'A',
        ];
        echo $app->render('templates/learning/lessons-post.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("lessons", ["name" => $name,"unit" =>$app->xss($_GET['inunit']),"deleted"=>0])) {
            $unit = $app->get("units","*",["id"=>$app->xss($_GET['inunit']),"deleted"=>0]);
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên trong chủ đề ". $unit['name'] ." đã tồn tại.")]);
            exit;
        }
        $position = $app->max("lessons","position",["unit"=>$app->xss($_POST['unit'])])??0;
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "unit"      => $app->xss($_POST['unit']),
            "status"    => $app->xss($_POST['status']),
            "position"  => ($position > 0) ? $position + 1 : 0,
        ];
        $app->insert("lessons",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Thêm thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-edit/{id}", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Sửa Bài học");
        $vars['units'] = $app->select("units","*",["deleted"=>'0',"ORDER" => ["position" => "ASC"]]);
        $vars['data'] = $app->get("lessons","*",["id"=>$vars['id'],"deleted"=>0]);
        if($vars['data']>1){
            echo $app->render('templates/learning/lessons-post.html', $vars, 'global');
        }
        else {
            echo $app->render('templates/common/error-modal.html', $vars, 'global');
        }
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $data = $app->get("lessons","*",["id"=>$vars['id'],"deleted"=>0]);
        $name = $app->xss($_POST['name']);
        if($name =='') {
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Vui lòng không để trống.")]);
            exit;
        } 
        if($app->has("lessons", ["name" => $name,"unit" => $data['unit'],"id[!]" => $data['id'],"deleted" => '0'])) {
            $unit = $app->get("grades","*",["id"=>$app->xss($_POST['grade']),"deleted"=>0]);
            echo json_encode(["status"=>"error","content"=>$jatbi->lang("Tên trong Chủ đề ". $unit['name'] ." đã tồn tại.")]);
            exit;
        }
        $insert = [
            "name"      => $app->xss($_POST['name']),
            "unit"      => $app->xss($_POST['unit']),
            "status"    => $app->xss($_POST['status']),
        ];
        $app->update("lessons",$insert,["id"=>$data['id']]);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-reorder", 'GET', function($vars) use ($app, $jatbi) {
        $inunit = $app->xss($_GET['inunit']);
        $vars['title'] = $jatbi->lang("Sắp xếp vị trí Bài học");
        $vars['datas'] = $app->select("lessons","*",["deleted"=>0,"unit"=>$inunit,"ORDER" => ["position" => "ASC"]]);
        
        echo $app->render('templates/learning/category-reorder.html', $vars, 'global');
    })->setPermissions(['courseCategoryManagement']);

    $app->router("/learning/lessons-reorder", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);

        $ids = explode(',', $app->xss($_POST['order']));

        foreach ($ids as $position => $id) {
            $app->update('lessons', ['position' => $position], ['id' => $id]);
        }
        // $app->insert("grades",$insert);
        // $jatbi->logs('learning','grades-add',$insert);
        echo json_encode(['status'=>'success','content'=>$jatbi->lang("Cập nhật thành công")]);
        exit;
    })->setPermissions(['courseCategoryManagement']);
?>