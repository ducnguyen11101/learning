<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $requests = [
        "main"=>[
            "name"=>$jatbi->lang("Chính"),
            "item"=>[
                '/admin'=>[
                    "menu"=>$jatbi->lang("Trang chủ"),
                    "url"=>'/admin',
                    "icon"=>'<i class="ti ti-dashboard"></i>',
                    "controllers"=>"controllers/core/main.php",
                    "main"=>'true',
                    "permission" => "",
                ],
            ],
        ],
        "page"=>[
            "name"=>'Admin',
            "item"=>[
                'users'=>[
                    "menu"=>$jatbi->lang("Người dùng"),
                    "url"=>'/users',
                    "icon"=>'<i class="ti ti-user "></i>',
                    "sub"=>[
                        'accounts'      =>[
                            "name"  => $jatbi->lang("Tài khoản"),
                            "router"=> '/users/accounts',
                            "icon"  => '<i class="ti ti-user"></i>',
                        ],
                        'permission'    =>[
                            "name"  => $jatbi->lang("Nhóm quyền"),
                            "router"=> '/users/permission',
                            "icon"  => '<i class="fas fa-universal-access"></i>',
                        ],
                    ],
                    "controllers"=>"controllers/core/users.php",
                    "main"=>'false',
                    "permission"=>[
                        'accounts'=> $jatbi->lang("Tài khoản"),
                        'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                        'permission'=> $jatbi->lang("Nhóm quyền"),
                        'permission.add' => $jatbi->lang("Thêm Nhóm quyền"),
                        'permission.edit' => $jatbi->lang("Sửa Nhóm quyền"),
                        'permission.deleted' => $jatbi->lang("Xóa Nhóm quyền"),
                    ]
                ],
                'admin'=>[
                    "menu"=>$jatbi->lang("Quản trị"),
                    "url"=>'/admin',
                    "icon"=>'<i class="ti ti-settings "></i>',
                    "sub"=>[
                        'blockip'   => [
                            "name"  => $jatbi->lang("Chặn truy cập"),
                            "router"    => '/admin/blockip',
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                        'trash'  => [
                            "name"  => $jatbi->lang("Thùng rác"),
                            "router"    => '/admin/trash',
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'logs'  => [
                            "name"  => $jatbi->lang("Nhật ký"),
                            "router"    => '/admin/logs',
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'config'    => [
                            "name"  => $jatbi->lang("Cấu hình"),
                            "router"    => '/admin/config',
                            "icon"  => '<i class="fa fa-cog"></i>',
                            "req"   => 'modal-url',
                        ],
                    ],
                    "controllers"=>"controllers/core/admin.php",
                    "main"=>'false',
                    "permission"=>[
                        'blockip'       =>$jatbi->lang("Chặn truy cập"),
                        'blockip.add'   =>$jatbi->lang("Thêm Chặn truy cập"),
                        'blockip.edit'  =>$jatbi->lang("Sửa Chặn truy cập"),
                        'blockip.deleted'=>$jatbi->lang("Xóa Chặn truy cập"),
                        'config'        =>$jatbi->lang("Cấu hình"),
                        'logs'          =>$jatbi->lang("Nhật ký"),
                        'trash'          =>$jatbi->lang("Thùng rác"),
                    ]
                ],
                'course'=>[
                    "menu"=>$jatbi->lang("Cấu hình khóa học"),
                    "url"=>'/course',
                    "icon"=> '<i class="fa fa-list-alt"></i>',
                    "sub"=>[
                        'courseCategoryManagement'   => [
                            "name"  => $jatbi->lang("Quản lý danh mục"),
                            "router"    => '/learning/grades',
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                        'userAccount'   => [
                            "name"  => $jatbi->lang("Tài khoản người dùng"),
                            "router"    => '/course/users',
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                        'image'   => [
                            "name"  => $jatbi->lang("Quản lý hình ảnh"),
                            "router"    => '/course/image',
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                    ],
                    "controllers" => "controllers/core/course.php",
                    "main"=>'false',
                    "permission" => [
                        'courseCategoryManagement'      =>$jatbi->lang("Quản lý danh mục khóa học"),
                        'userAccount'                   =>$jatbi->lang("Quản lý tài khoản người dùng"),
                        'image'                         =>$jatbi->lang("Quản lý hình ảnh"),
                        'image.add'                     =>$jatbi->lang("Thêm hình ảnh"),
                        'image.edit'                    =>$jatbi->lang("Sửa hình ảnh"),
                        'image.deleted'                 =>$jatbi->lang("Xóa hình ảnh"),
                    ],
                ],
            ],
        ],
    ];
    foreach($requests as $request){
        foreach($request['item'] as $key_item =>  $items){
            $setRequest[] = [
                "key" => $key_item,
                "controllers" =>  $items['controllers'],
            ];
            if($items['main']!='true'){
                $SelectPermission[$items['menu']] = $items['permission'];
            }
            if (isset($items['permission']) && is_array($items['permission'])) {
                foreach($items['permission'] as $key_per => $per) {
                    $userPermissions[] = $key_per; 
                }
            }
        }
    }
    
    $mains_frontend = [
		""=>[
			"controllers"=>"controllers/home/home.php",
		],
		"analytics"=>[
			"controllers"=>"controllers/lesson/analytics.php",
		],
		"lesson"=>[
			"controllers"=>"controllers/lesson/lesson.php",
		],
        "learn"=>[
			"controllers"=>"controllers/lesson/learn.php",
		],
        "diagnostic"=>[
			"controllers"=>"controllers/lesson/diagnostic.php",
		],
	];
    foreach($mains_frontend as $key => $item) {
        $setRequest[] = [
            "key" => $key,
            "controllers" => $item['controllers'],
        ];
    }
?>