<?php
$cryptinstall = './cryptographp.fct.php';
include $cryptinstall;
?>


<html>
<div align="center">
<b>Exemple d'utilisation de Cryptographp v1.4</b><br>
(Cet exemple fonctionne m�me si les cookies sont d�sactiv�es)<br><br>

<form action="verifier.php?<?php echo SID; ?>" method="post">
<table cellpadding=1>
  <tr><td align="center"><?php dsp_crypt(0, 1); ?></td></tr>
  <tr><td align="center">Recopier le code:<br><input type="text" name="code"></td></tr>
  <tr><td align="center"><input type="submit" name="submit" value="Envoyer"></td></tr>
</table>
<br><br><br>
Cryptographp (c) 2006-2007 Sylvain BRISON<br>
http://www.cryptographp.com
</form>

</div>
</html>


