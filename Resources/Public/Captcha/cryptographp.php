<?php

// -----------------------------------------------
// Cryptographp v1.4
// (c) 2006-2007 Sylvain BRISON
//
// www.cryptographp.com
// cryptographp@alphpa.com
//
// Licence CeCILL modifi�e
// => Voir fichier Licence_CeCILL_V2-fr.txt)
// -----------------------------------------------

session_start();
error_reporting(E_ALL ^ E_NOTICE);
setcookie('cryptcookietest', '1');
header('Location: cryptographp.inc.php?cfg='.$_GET['cfg'].'&sn='.session_name().'&'.SID);
