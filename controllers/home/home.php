<?php
$app->router("/", 'GET', function($vars) use ($app) {
    require_once 'headerhome.php';
    echo $app->render('templates/home/home.html', $vars);
    require_once 'footerhome.php'; 
});
?>