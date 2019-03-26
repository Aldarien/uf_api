<?php
include_once dirname(__DIR__) . '/bootstrap/public.php';

$routes = new UF\API\Routes($app);
$routes->register();
$app->run();
?>
