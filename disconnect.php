<?php
  include "ressources/php/include.php";

  if (!isset($_SESSION['email'])) {
    header('Location: ./');
    exit;
  }
  elseif (isset($_SESSION['login']) != NULL) {
    $_SESSION = array();
    session_destroy();

    include "ressources/php/class/cas.php";
    CAS::logout();
    exit;
  }

  $_SESSION = array();
  session_destroy();
  header('Location: ./');
  exit;
?>
