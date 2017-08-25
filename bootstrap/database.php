<?php
$db = 'databases.' . config('app.database');
$port = config($db . '.port');
if ($port == null) {
	$port = ';port=3306';
} else {
	$port = ';port=' . $port;
}
ORM::configure('mysql:host=' . config($db . '.host') . $port . ';dbname=' . config($db . '.database'));
ORM::configure('username', config($db . '.username'));
ORM::configure('password', config($db . '.password'));
Model::$short_table_names = true;
?>