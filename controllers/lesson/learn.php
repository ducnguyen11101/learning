<?php
$app->router("/learn", 'GET', function($vars) use ($app) {
    
    echo $app->render('templates/lessons/learn.html',$vars);
});

$app->router("/learn", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    
});
?>