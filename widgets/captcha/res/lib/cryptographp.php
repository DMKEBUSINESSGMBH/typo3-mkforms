<?php

// -----------------------------------------------
// Cryptographp v1.3
// (c) 2006 Sylvain BRISON 
//
// www.cryptographp.com 
// cryptographp@alphpa.com 
//
// Licence CeCILL (Voir Licence_CeCILL_V2-fr.txt)
// -----------------------------------------------

session_start();
error_reporting(E_ALL ^ E_NOTICE);
SetCookie("cryptcookietest", "1");
$iSID = session_id();
Header("Location: cryptographp.inc.php?cfg=".$_GET['cfg']."&sn=".session_name()."&".$iSID);
?>
