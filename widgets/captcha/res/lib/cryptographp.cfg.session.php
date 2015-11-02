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


// -------------------------------------
// Configuration du fond du cryptogramme
// -------------------------------------


$cryptwidth  = $_SESSION["rdt_captcha"]["config"]["width"];  // Largeur du cryptogramme (en pixels)
$cryptheight = $_SESSION["rdt_captcha"]["config"]["height"];   // Hauteur du cryptogramme (en pixels)

$bgR  = $_SESSION["rdt_captcha"]["config"]["bgR"];         // Couleur du fond au format RGB: Red (0->255)
$bgG  = $_SESSION["rdt_captcha"]["config"]["bgG"];         // Couleur du fond au format RGB: Green (0->255)
$bgB  = $_SESSION["rdt_captcha"]["config"]["bgB"];         // Couleur du fond au format RGB: Blue (0->255)

$bgclear = $_SESSION["rdt_captcha"]["config"]["bgClear"];     // Fond transparent (true/false)
                     // Uniquement valable pour le format PNG

$bgimg = '';          // Le fond du cryptogramme peut-�tre une image
                             // PNG, GIF ou JPG. Indiquer le fichier image
                             // Exemple: $fondimage = 'photo.gif';
				                     // L'image sera redimensionn�e si n�cessaire
                             // pour tenir dans le cryptogramme

$bgframe = $_SESSION["rdt_captcha"]["config"]["bgFrame"];    // Ajoute un cadre de l'image (true/false)


// ----------------------------
// Configuration des caract�res
// ----------------------------

// Couleur de base des caract�res

$charR = $_SESSION["rdt_captcha"]["config"]["charR"];     // Couleur des caract�res au format RGB: Red (0->255)
$charG = $_SESSION["rdt_captcha"]["config"]["charG"];     // Couleur des caract�res au format RGB: Green (0->255)
$charB = $_SESSION["rdt_captcha"]["config"]["charB"];     // Couleur des caract�res au format RGB: Blue (0->255)

$charcolorrnd = $_SESSION["rdt_captcha"]["config"]["charcolorrnd"];      // Choix al�atoire de la couleur.
$charcolorrndlevel = $_SESSION["rdt_captcha"]["config"]["charcolorrndlevel"];    // Niveau de clart� des caract�res si choix al�atoire (0->4)
                           // 0: Aucune s�lection
                           // 1: Couleurs tr�s sombres (surtout pour les fonds clairs)
                           // 2: Couleurs sombres
                           // 3: Couleurs claires
                           // 4: Couleurs tr�s claires (surtout pour fonds sombres)

$charclear = $_SESSION["rdt_captcha"]["config"]["charclear"];   // Intensit� de la transparence des caract�res (0->127)
                  // 0=opaques; 127=invisibles
	                // interessant si vous utilisez une image $bgimg
	                // Uniquement si PHP >=3.2.1

// Polices de caract�res

$tfont = $_SESSION["rdt_captcha"]["config"]["tfont"];



// Caracteres autoris�s
// Attention, certaines polices ne distinguent pas (ou difficilement) les majuscules
// et les minuscules. Certains caract�res sont faciles � confondre, il est donc
// conseill� de bien choisir les caract�res utilis�s.

$charel = $_SESSION["rdt_captcha"]["config"]["charel"];       // Caract�res autoris�s

$crypteasy = $_SESSION["rdt_captcha"]["config"]["crypteasy"];       // Cr�ation de cryptogrammes "faciles � lire" (true/false)
                         // compos�s alternativement de consonnes et de voyelles.

$charelc = $_SESSION["rdt_captcha"]["config"]["charelc"];   // Consonnes utilis�es si $crypteasy = true
$charelv = $_SESSION["rdt_captcha"]["config"]["charelv"];              // Voyelles utilis�es si $crypteasy = true

$difuplow = $_SESSION["rdt_captcha"]["config"]["difuplow"];          // Diff�rencie les Maj/Min lors de la saisie du code (true, false)

$charnbmin = $_SESSION["rdt_captcha"]["config"]["charnbmin"];         // Nb minimum de caracteres dans le cryptogramme
$charnbmax = $_SESSION["rdt_captcha"]["config"]["charnbmax"];         // Nb maximum de caracteres dans le cryptogramme

$charspace = $_SESSION["rdt_captcha"]["config"]["charspace"];        // Espace entre les caracteres (en pixels)
$charsizemin = $_SESSION["rdt_captcha"]["config"]["charsizemin"];      // Taille minimum des caract�res
$charsizemax = $_SESSION["rdt_captcha"]["config"]["charsizemax"];      // Taille maximum des caract�res

$charanglemax  = $_SESSION["rdt_captcha"]["config"]["charanglemax"];     // Angle maximum de rotation des caracteres (0-360)
$charup   = $_SESSION["rdt_captcha"]["config"]["charup"];      // D�placement vertical al�atoire des caract�res (true/false)

// Effets suppl�mentaires

$cryptgaussianblur = false; // Transforme l'image finale en brouillant: m�thode Gauss (true/false)
                            // uniquement si PHP >= 5.0.0
$cryptgrayscal = false;     // Transforme l'image finale en d�grad� de gris (true/false)
                            // uniquement si PHP >= 5.0.0

// ----------------------
// Configuration du bruit
// ----------------------

$noisepxmin = $_SESSION["rdt_captcha"]["config"]["noisepxmin"];       // Bruit: Nb minimum de pixels al�atoires
$noisepxmax = $_SESSION["rdt_captcha"]["config"]["noisepxmax"];       // Bruit: Nb maximum de pixels al�atoires

$noiselinemin = $_SESSION["rdt_captcha"]["config"]["noiselinemin"];     // Bruit: Nb minimum de lignes al�atoires
$noiselinemax = $_SESSION["rdt_captcha"]["config"]["noiselinemax"];     // Bruit: Nb maximum de lignes al�atoires

$nbcirclemin = $_SESSION["rdt_captcha"]["config"]["nbcirclemin"];      // Bruit: Nb minimum de cercles al�atoires
$nbcirclemax = $_SESSION["rdt_captcha"]["config"]["nbcirclemax"];      // Bruit: Nb maximim de cercles al�atoires

$noisecolorchar  = $_SESSION["rdt_captcha"]["config"]["noisecolorchar"];  // Bruit: La couleur est celle du caract�re (true) sinon celle du fond (false)


// --------------------------------
// Configuration syst�me & s�curit�
// --------------------------------

$cryptformat = $_SESSION["rdt_captcha"]["config"]["cryptformat"];   // Format du fichier image g�n�r� "GIF", "PNG" ou "JPG"
				                // Si vous souhaitez un fond transparent, utilisez "PNG" (et non "GIF")
				                // Attention certaines versions de la bibliotheque GD ne gerent pas GIF !!!

$cryptsecure = $_SESSION["rdt_captcha"]["config"]["cryptsecure"];    // M�thode de crytpage utilis�e: "md5", "sha1" ou "" (aucune)
                      // "sha1" seulement si PHP>=4.2.0
                         // Si aucune m�thode n'est indiqu�e, le code du cyptogramme est stock�
                         // en clair dans la session.

$cryptusetimer = $_SESSION["rdt_captcha"]["config"]["cryptusetimer"];        // Temps (en seconde) avant d'avoir le droit de reg�n�rer un cryptogramme
$cryptusertimererror = $_SESSION["rdt_captcha"]["config"]["cryptusertimererror"];  // Action � r�aliser si le temps minimum n'est pas respect�:
                           // 1: Ne rien faire, ne pas renvoyer d'image.
                           // 2: L'image renvoy�e est "images/erreur2.png" (vous pouvez la modifier)
                           // 3: Le script se met en pause le temps correspondant (attention au timeout
                           //    par d�faut qui coupe les scripts PHP au bout de 30 secondes)
                           //    voir la variable "max_execution_time" de votre configuration PHP

$cryptusemax = $_SESSION["rdt_captcha"]["config"]["cryptusemax"];  // Nb maximum de fois que l'utilisateur peut g�n�rer le cryptogramme
                      // Si d�passement, l'image renvoy�e est "images/erreur1.png"
                      // PS: Par d�faut, la dur�e d'une session PHP est de 180 mn, sauf si
                      // l'hebergeur ou le d�veloppeur du site en on d�cid� autrement...
                      // Cette limite est effective pour toute la dur�e de la session.

