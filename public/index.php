<?php
include_once dirname(__DIR__) . '/bootstrap/autoload.php';

if (get('p') !== false) {
    switch (get('p')) {
        case 'uf':
            echo \App\Controller\UFApi::index();
            break;
        case 'help':
        	echo \App\Controller\UFApi::help();
        	break;
        default:
            echo api(['test' => 'p not valid.'], 404);
            break;
    }
} else {
    echo api(['test' => 'no p'], 404);
}
?>
