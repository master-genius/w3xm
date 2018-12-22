<?php
require('vendor/autoload.php');

use \Core\DB;

/*
    命令：
        dbsync

*/

/*
$pdo = DB::instance()->query("show tables");

$tables = $pdo->fetchAll(PDO::FETCH_COLUMN);

walk_arr($tables);

foreach($tables as $t) {
    $pdo = DB::instance()->query("show columns from $t");
    $cols = $pdo->fetchAll();
    echo json_encode($cols);
}
 */

if ($argc <= 1) {
    exit("usage : $argv[0] [COMMAND] [OPTIONS]\n");
}

$command = $argv[1];

if ($command === 'help') {
} elseif ($command === 'clearimg') {
    $pagesize = 1000;
    $page = 1;
    $cond = [
        'media_type[~]' => 'image',
        'LIMIT' => [
            0, $pagesize
        ]
    ];

    while(true) {
        $imglist = DB::instance()->select('media', [
            'id', 'media_path', 'media_name'
        ], $cond);

        if (empty($imglist)) {
            exit("");
        }
        $page += 1;
        $cond['LIMIT'] = [($page-1)*$pagesize, $pagesize];
        $rmlist = [];

        foreach($imglist as $img) {
            if (!file_exists($img['media_path'] . $img['media_name'])) {
                echo "Removing : " . $img['media_path'] . $img['media_name'] . "\n";
                $rmlist[] = $img['id'];
            }

        }

        if (!empty($rmlist)) {
            DB::instance()->delete('media', ['id' => $rmlist]);
        }

    }

} else {
    exit("unknow command\n");
}

