<?php
use App\Controller\UFApi;

include_once dirname(__DIR__) . '/bootstrap/autoload.php';

if (get('p') !== false) {
    switch (get('p')) {
        case 'uf':
            echo UFApi::index();
            break;
        default:
            echo api(['test' => 'p not valid.'], 404);
            break;
    }
} else {
    echo api(['test' => 'no p'], 404);
}
?>
