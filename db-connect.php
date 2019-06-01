<?php
  include __DIR__ . '/parse_env.php';

  if ($env_arr) {
    try {
      $dbusername = $env_arr['db_username'];
      $dbpassword = $env_arr['db_password'];
      $dbh = new PDO('mysql:host=localhost;dbname=' . $env_arr['db'] ,$dbusername,$dbpassword);
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
      echo 'Connection failed: ' . $e->getMessage();
    }
  } else {
    die('Connection failed');
  }
?>