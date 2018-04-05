<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" title="CSS Accueil" href="ressources/css/general.css">
    <link rel="stylesheet" type="text/css" title="CSS Accueil" href="ressources/css/accueil.css">
    <link rel="stylesheet" type="text/css" title="CSS Accueil" href="ressources/css/billetterie.css">
    <link href='https://fonts.googleapis.com/css?family=Product+Sans' rel='stylesheet' type='text/css'>
    <link rel="icon" href="ressources/img/Silhouette.png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pour la toute première fois l'association étudiante Light Up City organise 'Compiègne en Lumiere', l'évènement ayant pour but de rassembler Compiègnois et Étudiants le temps d'une soirée avec un parcours illuminant et animant la ville de Compiègne !">

    <title>Light Up City</title>

</head>

<body>
    <div id="fb-root"></div>
    <script>
        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s);
            js.id = id;
            js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.11';
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

    </script>
    <div id="bande_couleurs_haut">
        <div class="couleur_jaune">&nbsp;</div>
        <div class="couleur_orange">&nbsp;</div>
        <div class="couleur_rose">&nbsp;</div>
        <div class="couleur_bleu">&nbsp;</div>
        <div class="couleur_vert">&nbsp;</div>
    </div>

    <div id="menu_fixe">
        <table>
            <tr>
                <span id="menu_fixe_presentation">
                <a href="./#presentationREF">Présentation</a>
            </span>
                <span id="menu_fixe_parcours">
                <a href="./#parcoursREF">Parcours</a>
            </span>
                <span id="menu_fixe_animations">
                <a href="./#animationsREF">Animations</a>
            </span>
                <span>
                    <a href="./#menuREF">
                    <img id="menu_fixe_logo" src="ressources/img/Silhouette.png">
                    </a>
            </span>
                <span id="menu_fixe_billetterie">
               <a href="./billetterie.php">Billetterie</a>
            </span>
                <span id="menu_fixe_organisation">
               <a href="./#organisationREF">Organisation</a>
           </span>
                <span id="menu_fixe_contact">
               <a href="./#contactREF">Contacts &nbsp;&nbsp;</a>
           </span>
            </tr>
        </table>
    </div>

    <a name="menuREF"></a>
    <div class="container" id="menu">
        <span>
   <ul class="nav nav-tabs nav-justified" role="tablist">
     <li>
         <span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </span>
        </li>
        <li>
            <span id="presentation">
                <a href="./#presentationREF">Présentation</a>
            </span>
        </li>
        <li>
            <span id="parcours">
                <a href="./#parcoursREF">Parcours</a>
            </span>
        </li>
        <li>
            <span id="animations">
                <a href="./#animationsREF">Animations</a>
            </span>
        </li>
        <li>
            <span>
                <a href="./">
        <img style="height: 10em" src="img/Logo_avec_fond_noir_rond.png">
                </a>
            </span>
        </li>
        <li>
            <span id="billetterie">
           <a href="./billetterie.php">Billetterie</a>
       </span>
        </li>
        <li>
            <span id="organisation">
           <a href="./#organisationREF">Organisation</a>
       </span>
        </li>
        <li>
            <span id="contact">
           <a href="./#contactREF">Contacts</a>
       </span>
        </li>
        <li>
            <span id="facebook">
            <a href="https://www.facebook.com/CompiegneEnLumiere/">&nbsp;&nbsp;f&nbsp;&nbsp;</a>
        </span>
        </li>
        </ul>
        </span>
    </div>
    <div id="date">
        24.02.18
    </div>

    <div class="bandeau_noir_bas">
        <div class="couleur_noir">&nbsp;</div>
    </div>

    <div class="bandeau_separation">
        <div class="couleur_noir">&nbsp;</div>
        <div class="bande_couleurs_separation">
            <div class="couleur_jaune">&nbsp;</div>
            <div class="couleur_orange">&nbsp;</div>
            <div class="couleur_rose">&nbsp;</div>
            <div class="couleur_bleu">&nbsp;</div>
            <div class="couleur_vert">&nbsp;</div>
        </div>
        <div class="couleur_noir">&nbsp;</div>
    </div>

    <div id="bandeau_connexion" class="align-items-center">
        <ul class="nav nav-tabs" role="tablist">
          <?php
            if (isset($_SESSION['id'])) {
              ?>
                <li><span>
                     <a href=""><?php echo $_SESSION['firstname'], ' ', $_SESSION['lastname']; ?></a>
                </span></li>
              <?php
                $query = $db->request(
                  "SELECT * FROM tickets WHERE idUser = ?",
                  array($_SESSION['id'])
                );

                if ($query->rowCount() != 0) {
                  ?>
                    <li><span>
                         <a href="./account.php">Mes billets</a>
                    </span></li>
                  <?php
                }
              ?>
            <?php
              $query = $db->request(
                "SELECT * FROM tickets WHERE idUser = ? AND status = 1",
                array($_SESSION['id'])
              );

              if ($query->rowCount() != 0) {
                ?>
                  <li><span>
                       <a href="./consos.php">Mes consos</a>
                  </span></li>
                <?php
              }
            ?>
                <li><span>
                     <a href="./disconnect.php">Se déconnecter</a>
                </span></li>
              <?php
            }
            else {
              ?>
                <li><span>
                   <a href="./register.php">S'inscrire</a>
                </span></li>
                <li><span>
                  <a href="./connect.php">Se connecter</a>
                </span></li>
              <?php
            }
          ?>
        </ul>
    </div>

    <div class="bandeau_separation">
        <div class="couleur_noir">&nbsp;</div>
        <div class="bande_couleurs_separation">
            <div class="couleur_jaune">&nbsp;</div>
            <div class="couleur_orange">&nbsp;</div>
            <div class="couleur_rose">&nbsp;</div>
            <div class="couleur_bleu">&nbsp;</div>
            <div class="couleur_vert">&nbsp;</div>
        </div>
        <div class="couleur_noir">&nbsp;</div>

    </div>

<?php
  ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);
?>
