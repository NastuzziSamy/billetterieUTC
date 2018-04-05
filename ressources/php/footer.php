
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


        <div id="bandeau_partenaire">
            <div id="texte_partenaire">
                <h3>Partenaires
                </h3>
            </div>
            <div class=".flex-column">
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/logosUTC_SU.png" id="logo_utc">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/BDE.png" id="logo_bde">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/compiegne.png" id="logo_mairie">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/pae.png" id="logo_pae">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/integ_fev.png" id="logo_integ_fev">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/logo-soge.png" id="logo_soge">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/hauts_de_france.png" id="logo_hauts_de_france">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/crous.png" id="logo_crous">
            </span>
                <span class="logo_partenaire">
                <img src="images_et_icones/partenaires/oise.png" id="logo_oise">
            </span>

            </div>
            <a name="partenaireREF"></a>
        </div>

        <div class="bandeau_noir_bas">
            <div class="couleur_noir">&nbsp;</div>
        </div>

        <script>
            $(document).ready(function() {
                $('[data-toggle="tooltip"]').tooltip();
            });

        </script>


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

        <!--   Permet de figer le carousel artiste en hover  -->
        <script>
            $('.carousel').carousel({
                pause: "hover"
            })

        </script>

        <!--   Permet de faire apparaitre popup (cf artistes sur mobiles)  -->
        <script>
            $(document).ready(function() {
                $('[data-toggle="popover"]').popover();
            });

        </script>
        <script src="./JS/index.js"></script>
    </body>


    </html>
