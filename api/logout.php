<?php
include "headers.php";
session_start();
include "db_conn.php";

session_unset();
session_destroy();

echo json_encode(["success" => true]);