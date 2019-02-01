<?php

error_reporting(0);

require 'dumper.class.php';

$username = $_COOKIE['username'];
$password = $_COOKIE['password'];
$database = $_COOKIE['database'];

$dumper = new Dumper($username, $password, $database);
$dumper->generate();

$database = $dumper->database;
$sql = $dumper->sql;
$rows = $dumper->rows_summary;

$tables = $dumper->table_names;
$tables = array_map(function($item){ return "'$item'"; }, $tables);
$tables = implode(", ", $tables);

$error = $dumper->error;

if($_POST['action'] == "change")
{
    setcookie("username", $_POST['username']);
    setcookie("password", $_POST['password']);
    setcookie("database", $_POST['database']);
    header('location: /');
}

if($_POST['action'] == "save")
{
    $dumper->save();
}

if($_POST['action'] == "upload"){
    if( $_FILES['dump'] )
    {
        $dumper->upload($_FILES['dump']);
    }
    $return = $_SERVER['REQUEST_URI'];

    header("location: $return");
}

require 'index.html';