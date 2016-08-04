<?php
require_once '_db.php';

header('Content-Type: application/json');
echo json_encode(GetAllItems());

?>
