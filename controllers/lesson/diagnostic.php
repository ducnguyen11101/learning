<?php
$app->router("/diagnostic", 'GET', function($vars) use ($app) {
    require_once __DIR__ . '/../home/headerhome.php';
    $users = $app->get('users',"*", [
        'account_id' => '16',
    ]);
    if (!is_array($users)) {
        header("Location: /login");
        exit;
    }
    $a = 480-(165*(1-($users['fractions']/1000)));
    $b = 397.5-((397.5-232.5)/2*(1-($users['algebra']/1000)));
    $b2 = 83.7+((366.3-83.7)/2*(1-($users['algebra']/1000)));
    $c = 232.5+((397.5-232.5)/2*(1-($users['numbers']/1000)));
    $c2 = 83.7+((366.3-83.7)/2*(1-($users['numbers']/1000)));
    $d = 150+(165*(1-($users['geometry']/1000)));
    $e = 232.5+((397.5-232.5)/2*(1-($users['measurement']/1000)));
    $e2 = 366.3-((366.3-83.7)/2*(1-($users['measurement']/1000)));
    $f = 397.5-(165*(1-($users['data']/1000)))/2;
    $f2 = 366.3-((366.3-83.7)/2*(1-($users['data']/1000)));
    $point = "
        $a,225
        $b,$b2
        $c,$c2
        $d,225
        $e,$e2
        $f,$f2
    " ;
    $vars['points'] = $point;
    $vars['users'] = $users;
    echo $app->render('templates/lessons/diagnostic.html',$vars);
});

$app->router("/diagnostic", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    
});
?>