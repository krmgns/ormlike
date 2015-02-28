<?php
header('Content-Type: text/plain; charset=gbk');

include('inc.php');

/******************************************/

// $query = new ORMLikeQuery('users u');
// $query->select('id,name');
// $query->select('id,name')->where('id=?', 1);
// $query->select('id,name')->where('id=?', 1)->whereLike('AND name=?', 'Kerem');
// $query->select('id,name')->whereLike('id=? AND name=?', ['1%', '%Ke_rem%']);
// $query->select('id,name')->where('id=?', 1)->whereLike('(id LIKE ? OR name LIKE ?)', ['2%', '%Ke_rem%'], 'OR');

// $query->select('id,name')
//     ->where('id=?', 1)
//     ->where('(name=? OR name=? OR old BETWEEN %d AND %d)', ['Kerem', 'Murat', 30, 40], $query::OP_AND)
// ;

// $query->select('u.*, up.point')
//     ->aggregate('sum', 'up.point', 'sum_point')
//     ->joinLeft('users_point up', 'up.user_id=u.id')
//     ->groupBy('u.id')
//     ->orderBy('old')
//     ->having('sum_point > 30')
//     ->limit(0,10)
// ;

// pre($query->toString());

// pre($query->execute());
// pre($query->get());
// pre($query->getAll());

// pre($query->execute(function($result) {
//     $data = array();
//     while ($row = mysqli_fetch_object($result)) {
//         $data[] = $row;
//     }
//     return $data;
// }));

// pre($query->getAll(function($result) {
//     $result = array_filter($result, function($row) {
//         return ($row->id > 1);
//     });
//     return $result;
// }));

// pre('...');
// pre($query);

$db = ORMLikeDatabase::init();

$a = "\xbf\x27 OR 1=1 /*";
$s = $db->prepare("SELECT * FROM test WHERE name = ? LIMIT 1", $a);
pre($s);
pre($db->get($s));

/*
burda tablo adi yanlis ama try/catch hatayi gormuyor
SELECT * FROM test WHERE name = '\縗' OR 1=1 /*' LIMIT 1
Fatal error:   in /var/www/.dev/ormlike/.old/ORMLikeDatabase.php on line 76

buraya bakarken deneme yapiyordum
http://stackoverflow.com/a/12202218/362780 @ircmaxell
http://stackoverflow.com/questions/134099/are-pdo-prepared-statements-sufficient-to-prevent-sql-injection
 */
