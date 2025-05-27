<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $jatbi = new Jatbi($app);
    $setting = $app->getValueData('setting');
    $common = $app->getValueData('common');

    $app->router("/course/subject", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Cấu hình khóa học");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/classes.html', $vars);
    })->setPermissions(['course']);

    $app->router("/course/subject", 'POST', function($vars) use ($app, $jatbi) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        $orderName = isset($_POST['order'][0]['name']) ? $_POST['order'][0]['name'] : 'id';
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'DESC';
        // $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        // $permission = isset($_POST['permission']) ? $_POST['permission'] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "grades.name[~]" => $searchValue,
                ],
                // "grades.status[<>]" => $status,
                // "grades.deleted" => 0,
            ],
            "LIMIT" => [$start, $length],
            "ORDER" => [$orderName => strtoupper($orderDir)]
        ];
        // if (!empty($permission)) {
        //     $where["AND"]["accounts.permission"] = $permission;
        // }
        $count = $app->count("grades",[
            "AND" => $where['AND'],
        ]);
        $app->select("grades", [
            //     "[>]permissions" => ["permission" => "id"]
            // ], 
            // [
            'grades.id',
            'grades.name',
            'grades.status',
            ], $where, function ($data) use (&$datas,$jatbi,$app) {
            $datas[] = [
                "checkbox" => $app->component("box",["data"=>$data['id']]),
                "name" => $data['name'],
                "totalTopics" => 1,
                "totalSkills" => 2,
                "status" => $app->component("status",["url"=>"/course-status/".$data['id'],"data"=>$data['status'],"permission"=>['course']]),
                "action" => $app->component("action",[
                    "button" => [
                        [//thêm
                            'type' => 'link',
                            'name' => $jatbi->lang("Xem chi tiết"),
                            // 'permission' => ['accounts.edit'],
                            // 'action' => ['href' => '/users/accounts-detail/'.$data['active']]
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            // 'permission' => ['accounts.edit'],
                            // 'action' => ['data-url' => '/users/accounts-edit/'.$data['active'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            // 'permission' => ['accounts.deleted'],
                            // 'action' => ['data-url' => '/users/accounts-deleted?box='.$data['active'], 'data-action' => 'modal']
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
    })->setPermissions(['course']);

    $app->router("/learning/topics", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Cấu hình khóa học");
        $vars['datatable'] = $app->component('datatable',["datas"=>[],"search"=>[]]);
        echo $app->render('templates/learning/topics.html', $vars);
    })->setPermissions(['course']);

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
        // $status = isset($_POST['status']) ? [$_POST['status'],$_POST['status']] : '';
        // $permission = isset($_POST['permission']) ? $_POST['permission'] : '';
        
        $where = [
            "AND" => [
                "OR" => [
                    "topics.name[~]" => $searchValue,
                ],
                // "grades.status[<>]" => $status,
                // "grades.deleted" => 0,
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
                "totalTopics" => 1,
                "totalSkills" => 2,
                "status" => $app->component("status",["url"=>"/course-status/".$data['id'],"data"=>$data['status'],"permission"=>['course']]),
                "action" => $app->component("action",[
                    "button" => [
                        [//thêm
                            'type' => 'link',
                            'name' => $jatbi->lang("Xem chi tiết"),
                            // 'permission' => ['accounts.edit'],
                            // 'action' => ['href' => '/users/accounts-detail/'.$data['active']]
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Sửa"),
                            // 'permission' => ['accounts.edit'],
                            // 'action' => ['data-url' => '/users/accounts-edit/'.$data['active'], 'data-action' => 'modal']
                        ],
                        [
                            'type' => 'button',
                            'name' => $jatbi->lang("Xóa"),
                            // 'permission' => ['accounts.deleted'],
                            // 'action' => ['data-url' => '/users/accounts-deleted?box='.$data['active'], 'data-action' => 'modal']
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
    })->setPermissions(['course']);

?>