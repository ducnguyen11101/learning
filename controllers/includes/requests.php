<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $requests = [
        "main"=>[
            "name"=>$jatbi->lang("Chính"),
            "item"=>[
                '/'=>[
                    "menu"=>$jatbi->lang("Trang chủ"),
                    "url"=>'/',
                    "icon"=>'<i class="ti ti-dashboard"></i>',
                    "controllers"=>"controllers/core/main.php",
                    "main"=>'true',
                    "permission" => "",
                ],
            ],
        ],
        "lesson"=>[
            "name"=>$jatbi->lang("Bài học"),
            "item"=>[
                '/'=>[
                    "menu"=>$jatbi->lang("Bài học"),
                    "url"=>'/lesson',
                    "icon"=>'<i class="ti ti-dashboard"></i>',
                    "controllers"=>"controllers/lesson/lesson.php",
                    "main"=>'true',
                    "permission" => "",
                ],
            ],
        ],
        "admin"=>[ // Thay đổi key từ "page" thành "admin" để phản ánh rõ hơn
            "name"=>'Admin',
            "item"=>[
                'users'=>[
                    "menu"=>$jatbi->lang("Người dùng"),
                    "url"=>'/admin/users', // Thêm /admin/ vào URL
                    "icon"=>'<i class="ti ti-user "></i>',
                    "sub"=>[
                        'accounts'      =>[
                            "name"  => $jatbi->lang("Tài khoản"),
                            "router"=> '/admin/users/accounts', // Thêm /admin/
                            "icon"  => '<i class="ti ti-user"></i>',
                        ],
                        'permission'    =>[
                            "name"  => $jatbi->lang("Nhóm quyền"),
                            "router"=> '/admin/users/permission', // Thêm /admin/
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
                'settings'=>[
                    "menu"=>$jatbi->lang("Quản trị"),
                    "url"=>'/admin/settings', // Thêm /admin/
                    "icon"=>'<i class="ti ti-settings "></i>',
                    "sub"=>[
                        'blockip'   => [
                            "name"  => $jatbi->lang("Chặn truy cập"),
                            "router"    => '/admin/settings/blockip', // Thêm /admin/
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                        'trash'  => [
                            "name"  => $jatbi->lang("Thùng rác"),
                            "router"    => '/admin/settings/trash', // Thêm /admin/
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'logs'  => [
                            "name"  => $jatbi->lang("Nhật ký"),
                            "router"    => '/admin/settings/logs', // Thêm /admin/
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'config'    => [
                            "name"  => $jatbi->lang("Cấu hình"),
                            "router"    => '/admin/settings/config', // Thêm /admin/
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
                        'trash'         =>$jatbi->lang("Thùng rác"),
                    ]
                ],
                'course'=>[
                    "menu"=>$jatbi->lang("Cấu hình khóa học"),
                    "url"=>'/admin/course', // Thêm /admin/
                    "icon"=> '<i class="fa fa-list-alt"></i>',
                    "sub"=>[
                        'courseCategoryManagement'   => [
                            "name"  => $jatbi->lang("Quản lý danh mục"),
                            "router"    => '/admin/course/subject', // Thêm /admin/
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                    ],
                    "controllers"=>"controllers/core/course.php",
                    "main"=>'false',
                    "permission" => [
                        'courseCategoryManagement' => $jatbi->lang("Quản lý danh mục khóa học"),
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
        "home"=>[
            "controllers"=>"controllers/frontend/home.php",
        ],
        "lessons"=>[
            "controllers"=>"controllers/frontend/lessons.php",
        ],
        "accounts"=>[
            "controllers"=>"controllers/frontend/accounts.php",
        ],
        "affiliate"=>[
            "controllers"=>"controllers/frontend/affiliate.php",
        ],
        "getlink"=>[
            "controllers"=>"controllers/frontend/getlink.php",
        ],
        "payments"=>[
            "controllers"=>"controllers/frontend/payments.php",
        ],
        "news"=>[
            "controllers"=>"controllers/frontend/news.php",
        ],
        "statistics"=>[
            "controllers"=>"controllers/frontend/statistics.php",
        ],
        "content"=>[
            "controllers"=>"controllers/frontend/content.php",
        ],
        "contact"=>[
            "controllers"=>"controllers/frontend/contact.php",
        ],
        "search"=>[
            "controllers"=>"controllers/frontend/search.php",
        ],
        "test"=>[
            "controllers"=>"controllers/frontend/test.php",
        ],
        "error"=>[
            "controllers"=>"controllers/frontend/home.php",
        ],
        "api"=>[
            "controllers"=>"controllers/api/api.php",
        ],
        "exam"=>[
            "controllers"=>"controllers/frontend/exam.php",
        ],
        "details_level"=>[
            "controllers"=>"controllers/frontend/details_level.php",
        ],
        // Thêm route cho trang admin chính
        "admin"=>[
            "controllers"=>"controllers/core/admin.php",
        ],
    ];
?>