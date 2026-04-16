<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/hangin');
define('DB_HOST',  'localhost');
define('DB_NAME',  'u442411629_hangin');
define('DB_USER',  'u442411629_dev_hangin');
define('DB_PASS',  'Q61pZ08|VYpV');
define('DB_CHAR',  'utf8mb4');
define('APP_NAME', 'AirVent Monitor');
define('APP_VER',  '2.0');

date_default_timezone_set('Asia/Manila');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:monospace;padding:20px;color:#b91c1c"><strong>DB Connection Failed:</strong> '
        .htmlspecialchars($conn->connect_error).'</div>');
}
$conn->set_charset(DB_CHAR);

function db_q(string $sql,string $t='',array $p=[]): array {
    global $conn; $st=$conn->prepare($sql); if(!$st)return[];
    if($t&&$p)$st->bind_param($t,...$p); $st->execute();
    $r=$st->get_result(); $rows=$r?$r->fetch_all(MYSQLI_ASSOC):[]; $st->close(); return $rows;
}
function db_one(string $sql,string $t='',array $p=[]): ?array {
    $r=db_q($sql,$t,$p); return $r[0]??null;
}
function db_run(string $sql,string $t='',array $p=[]): int {
    global $conn; $st=$conn->prepare($sql); if(!$st)return 0;
    if($t&&$p)$st->bind_param($t,...$p); $st->execute();
    $id=$st->insert_id?:$st->affected_rows; $st->close(); return(int)$id;
}
function log_act(int $uid,string $type,string $desc): void {
    db_run("INSERT INTO activity_logs(user_id,action_type,description)VALUES(?,?,?)","iss",[$uid,$type,$desc]);
}
function auth_guard(string ...$roles): void {
    if(!isset($_SESSION['user_id'])){header('Location:'.BASE_URL.'/auth/login.php');exit;}
    if($roles&&!in_array($_SESSION['role'],$roles)){header('Location:'.BASE_URL.'/auth/login.php');exit;}
}
function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
