<?php
$app->router("/learn", 'GET', function($vars) use ($app) {
    if(!$app->getSession("accounts")){
        $vars['templates'] = 'login';
        echo $app->render('templates/login.html', $vars);
        return;
    }
    echo $app->render('templates/lessons/learn.html',$vars);
});

$app->router("/learn", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    
});
?>