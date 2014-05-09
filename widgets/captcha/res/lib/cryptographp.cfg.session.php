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

$bgimg = '';          // Le fond du cryptogramme peut-être une image  
                             // PNG, GIF ou JPG. Indiquer le fichier image
                             // Exemple: $fondimage = 'photo.gif';
				                     // L'image sera redimensionnée si nécessaire
                             // pour tenir dans le cryptogramme

$bgframe = $_SESSION["rdt_captcha"]["config"]["bgFrame"];    // Ajoute un cadre de l'image (true/false)


// ----------------------------
// Configuration des caractères
// ----------------------------

// Couleur de base des caractères

$charR = $_SESSION["rdt_captcha"]["config"]["charR"];     // Couleur des caractères au format RGB: Red (0->255)
$charG = $_SESSION["rdt_captcha"]["config"]["charG"];     // Couleur des caractères au format RGB: Green (0->255)
$charB = $_SESSION["rdt_captcha"]["config"]["charB"];     // Couleur des caractères au format RGB: Blue (0->255)

$charcolorrnd = $_SESSION["rdt_captcha"]["config"]["charcolorrnd"];      // Choix aléatoire de la couleur.
$charcolorrndlevel = $_SESSION["rdt_captcha"]["config"]["charcolorrndlevel"];    // Niveau de clarté des caractères si choix aléatoire (0->4)
                           // 0: Aucune sélection
                           // 1: Couleurs très sombres (surtout pour les fonds clairs)
                           // 2: Couleurs sombres
                           // 3: Couleurs claires
                           // 4: Couleurs très claires (surtout pour fonds sombres)

$charclear = $_SESSION["rdt_captcha"]["config"]["charclear"];   // Intensité de la transparence des caractères (0->127)
                  // 0=opaques; 127=invisibles
	                // interessant si vous utilisez une image $bgimg
	                // Uniquement si PHP >=3.2.1

// Polices de caractères

//$tfont[] = 'Alanden_';       // Les polices seront aléatoirement utilisées.
//$tfont[] = 'bsurp___';       // Vous devez copier les fichiers correspondants
//$tfont[] = 'ELECHA__.TTF';       // sur le serveur.
//$tfont[] = 'luggerbu.ttf';     // Ajoutez autant de lignes que vous voulez   
//$tfont[] = 'RASCAL__';     
//$tfont[] = 'SCRAWL.ttf';  
//$tfont[] = 'WAVY.ttf';

$tfont = $_SESSION["rdt_captcha"]["config"]["tfont"];



// Caracteres autorisés
// Attention, certaines polices ne distinguent pas (ou difficilement) les majuscules 
// et les minuscules. Certains caractères sont faciles à confondre, il est donc
// conseillé de bien choisir les caractères utilisés.

$charel = $_SESSION["rdt_captcha"]["config"]["charel"];       // Caractères autorisés

$crypteasy = $_SESSION["rdt_captcha"]["config"]["crypteasy"];       // Création de cryptogrammes "faciles à lire" (true/false)
                         // composés alternativement de consonnes et de voyelles.

$charelc = $_SESSION["rdt_captcha"]["config"]["charelc"];   // Consonnes utilisées si $crypteasy = true
$charelv = $_SESSION["rdt_captcha"]["config"]["charelv"];              // Voyelles utilisées si $crypteasy = true

$difuplow = $_SESSION["rdt_captcha"]["config"]["difuplow"];          // Différencie les Maj/Min lors de la saisie du code (true, false)

$charnbmin = $_SESSION["rdt_captcha"]["config"]["charnbmin"];         // Nb minimum de caracteres dans le cryptogramme
$charnbmax = $_SESSION["rdt_captcha"]["config"]["charnbmax"];         // Nb maximum de caracteres dans le cryptogramme

$charspace = $_SESSION["rdt_captcha"]["config"]["charspace"];        // Espace entre les caracteres (en pixels)
$charsizemin = $_SESSION["rdt_captcha"]["config"]["charsizemin"];      // Taille minimum des caractères
$charsizemax = $_SESSION["rdt_captcha"]["config"]["charsizemax"];      // Taille maximum des caractères

$charanglemax  = $_SESSION["rdt_captcha"]["config"]["charanglemax"];     // Angle maximum de rotation des caracteres (0-360)
$charup   = $_SESSION["rdt_captcha"]["config"]["charup"];      // Déplacement vertical aléatoire des caractères (true/false)

// Effets supplémentaires

$cryptgaussianblur = false; // Transforme l'image finale en brouillant: méthode Gauss (true/false)
                            // uniquement si PHP >= 5.0.0
$cryptgrayscal = false;     // Transforme l'image finale en dégradé de gris (true/false)
                            // uniquement si PHP >= 5.0.0

// ----------------------
// Configuration du bruit
// ----------------------

$noisepxmin = $_SESSION["rdt_captcha"]["config"]["noisepxmin"];       // Bruit: Nb minimum de pixels aléatoires
$noisepxmax = $_SESSION["rdt_captcha"]["config"]["noisepxmax"];       // Bruit: Nb maximum de pixels aléatoires

$noiselinemin = $_SESSION["rdt_captcha"]["config"]["noiselinemin"];     // Bruit: Nb minimum de lignes aléatoires
$noiselinemax = $_SESSION["rdt_captcha"]["config"]["noiselinemax"];     // Bruit: Nb maximum de lignes aléatoires

$noisecolorchar  = $_SESSION["rdt_captcha"]["config"]["noisecolorchar"];  // Bruit: La couleur est celle du caractère (true) sinon celle du fond (false)


// --------------------------------
// Configuration système & sécurité
// --------------------------------

$cryptformat = $_SESSION["rdt_captcha"]["config"]["cryptformat"];   // Format du fichier image généré "GIF", "PNG" ou "JPG"
				                // Si vous souhaitez un fond transparent, utilisez "PNG" (et non "GIF")
				                // Attention certaines versions de la bibliotheque GD ne gerent pas GIF !!!

$cryptsecure = $_SESSION["rdt_captcha"]["config"]["cryptsecure"];    // Méthode de crytpage utilisée: "md5", "sha1" ou "" (aucune)
                      // "sha1" seulement si PHP>=4.2.0
                         // Si aucune méthode n'est indiquée, le code du cyptogramme est stocké 
                         // en clair dans la session.
                       
$cryptusetimer = $_SESSION["rdt_captcha"]["config"]["cryptusetimer"];        // Temps (en seconde) avant d'avoir le droit de regénérer un cryptogramme
$cryptusertimererror = $_SESSION["rdt_captcha"]["config"]["cryptusertimererror"];  // Action à réaliser si le temps minimum n'est pas respecté:
                           // 1: Ne rien faire, ne pas renvoyer d'image.
                           // 2: L'image renvoyée est "images/erreur2.png" (vous pouvez la modifier)
                           // 3: Le script se met en pause le temps correspondant (attention au timeout
                           //    par défaut qui coupe les scripts PHP au bout de 30 secondes)
                           //    voir la variable "max_execution_time" de votre configuration PHP

$cryptusemax = $_SESSION["rdt_captcha"]["config"]["cryptusemax"];  // Nb maximum de fois que l'utilisateur peut générer le cryptogramme
                      // Si dépassement, l'image renvoyée est "images/erreur1.png"
                      // PS: Par défaut, la durée d'une session PHP est de 180 mn, sauf si 
                      // l'hebergeur ou le développeur du site en on décidé autrement... 
                      // Cette limite est effective pour toute la durée de la session. 
?>
