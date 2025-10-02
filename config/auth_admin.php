<!-- <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$admin_type_id = 1;
if (!isset($_SESSION['Type_id']) || $_SESSION['Type_id'] != $admin_type_id) {
    header('Location: mainadmin.php?error=access_denied'); 
    exit;
}
?> -->