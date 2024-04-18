(function($) {

    $(window).on('load', function () {

        
         //Responsive mettre le logo en haut pour les mobiles
        // Récupérer la largeur de l'écran
        let windowWidth = $(window).width();
        // Vérifier si la largeur de l'écran est inférieure à 768 pixels (typiquement la largeur des appareils mobiles)
        if (windowWidth < 992 && windowWidth > 768) {
            if ($('.page-accueil-metier').length > 0 || $('.metier-div-grid-container').length > 0 ) {
                if ($('.page-accueil-metier').length > 0) {console.log(' here')
                    jQuery('<div class="metier-mobile"><a href="/" title="Accueil" rel="home" class="site-logo"><img src="/files/Groupe%202@2x.png" alt="Accueil"></a></div>').insertBefore('article.grid-container');
                }else {
                    jQuery('<div class="metier-mobile"><a href="/" title="Accueil" rel="home" class="site-logo"><img src="/files/Groupe%202@2x.png" alt="Accueil"></a></div>').insertBefore('.title-bar');
                }
            }
        }
        if (windowWidth < 768) {
            // Le code à exécuter pour les appareils mobiles
            if ($('.page-accueil-metier').length > 0 || $('.metier-div-grid-container').length > 0 ) {
                if ($('.page-accueil-metier').length > 0) {console.log(' here')
                jQuery('<div class="metier-mobile"><a href="/" title="Accueil" rel="home" class="site-logo"><img src="/files/Groupe%202@2x.png" alt="Accueil"></a></div>').insertBefore('article.grid-container');
            }else {
                jQuery('<div class="metier-mobile"><a href="/" title="Accueil" rel="home" class="site-logo"><img src="/files/Groupe%202@2x.png" alt="Accueil"></a></div>').insertBefore('.title-bar');
            }            }
            
        }



        if (jQuery('[name="field_gabarit_texte_et_images[value]"]').prop('checked')) {
            jQuery('[id*=field-dossier-values]').next('.clearfix').show();
            console.log('is checked')
        }else {
            jQuery('[id*=field-dossier-values]').next('.clearfix').hide();
        }
        jQuery('body').on('click', '[name="field_gabarit_texte_et_images[value]"]', function() {
            console.log('is checked')
            if (jQuery(this).prop('checked')) {
                jQuery('[id*=field-dossier-values]').next('.clearfix').show();
            }else {
                jQuery('[id*=field-dossier-values]').next('.clearfix').hide();
            }
            // jQuery('#field-dossier-values').next('.clearfix').toggle();
        })
        //lien vers formulaire dans l'onglet activité, vérifier si l'utilisateur clique dessus...
/*         jQuery('body').on('click', '.boosturl', function(e) {
            e.preventDefault();
            location.href = "/civicrm/bulletin-de-cotisation-dirigeants?cs=6863eee1f86c41869fe3eed9747ee9c9_1709707364_192#?id=4467";
            return false;

        }) */
        // Function to parse query parameters from a URL
    function getParameterByName(url, name) {
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    //Formulaire de données économique onglet 'entreprise' desactiver le bouton envoyer si le nom de la personne qui a renseigné le form est vide
    if (!jQuery('.cust-form-person-who-filled').val()) {
        jQuery('.page-civicrm-bulletin-de-cotisation-infomration-contact .af-button.btn-primary').attr('disabled', true);
    }


    //Formulaire de données éco...  ==> après validation, indiquer quel onglet n'est pas visité
    let tab_already_processed = jQuery('.tab-certification').attr('data-set'); 
    if (tab_already_processed) {
        tab_already_processed = JSON.parse(tab_already_processed);
    }
    
    jQuery('.cust-form-person-who-filled').on('keyup', function() {
        if ($(this).val()) {
            jQuery('.af-button.btn-primary').removeAttr('disabled');
        }
    });


    //Vérifier si l'url contient le bon checksum
    let url = window.location.href;
    if (url.includes('civicrm/donnees-economique') || url.includes('civicrm/bulletin-de-cotisation')  || url.includes('civicrm/bulletin-cotisation-') ) {
        let organization1Value = getParameterByName(location.href, 'Organization1') ? getParameterByName(location.href, 'Organization1') : getParameterByName(location.href, 'id');  
        if (!organization1Value) {
            organization1Value = location.href.split('.entreprise=')[1];
        }
        let hasDataOrgId = $('.effectif-menu-class').attr('data-org-id');
        let searchParams = new URLSearchParams(window.location.search);
        //Vérifier d'abord si c'est le bon checksum
        let checksum = searchParams.get('cs');
        console.log('midiiii==>', checksum, searchParams, location.href, organization1Value)
        if (!checksum) {
            location.href = '/'
        }
        $.ajax({
            url: '/formulaire/verify-checksum',
            data: {contact_id: organization1Value,  checksum: checksum},
            success: (successResult, val, ee) => {
                if (!successResult.hasToken) {
                    location.href = "/"
                }
            },
            error: function(error) {
                console.log(error, 'ERROR')
            }
        });  
        
        
        if (checksum) {
            //Reconnect with authx/  Get the value of "Organization1"
            if (!hasDataOrgId && hasDataOrgId.trim() == '') {
                //run ajax to reconnect with authx
                $.ajax({
                url: '/formulaire/donnee-economique',
                data: {contact_id: organization1Value, url: location.href},
                success: (successResult, val, ee) => {
                    //Todo rediriger vers le bon onglet
                    console.log(successResult.url)
                    location.href = successResult.url // + "?cs=" + successResult.checksum + "&_authx=" + successResult.res + "&_authxSes=1#?Organization1=" + organization1Value;
                },
                error: function(error) {
                    console.log(error, 'ERROR')
                }
            });        
            }
        }else {
            location.href = "/"
        }
}
    


        //Bouton suivant
        $('body').on('click', '.af-button.btn-info', function() {
            if (jQuery('#progressbar').length) {
                jQuery('#progressbar .active').next('li').find('a.progress-bar-link').click();
            }
        })

        jQuery('.menu-to-be-showed').parents('ul').show();


        $('body').on('click', '.af-button.btn-warning',function() {
            jQuery('li.active').next('li').find('a').click();
        })

        jQuery('body').on('click', '#progressbar li', function(event) {
            let link = jQuery(this).attr('href-attribute');
            location.href=link;
            // var anchor = jQuery(this).find('a');
        })

       //Ajax permettant de stocker le cid dans la session
       let hashFragment = window.location.hash;
       let idValue = hashFragment.match(/(?:id|Organization1)=(\d+)/);
       
       $.ajax({
            url: '/session/store/cid',
            data: {contact_id: idValue},
            success: (successResult, val, ee) => {
               
            
            },
            error: function(error) {
                console.log(error, 'ERROR')
            }
        });

        // jQuery('#progressbar li a').each(function(id, el) {
        //     let href = jQuery(el).attr('href');
        //     if(href.includes('Organization')) {
        //         let modifiedUrl = href.replace(/(Organization1=)\d+/, '$1' + idValue);
        //         jQuery(el).attr('href', modifiedUrl);
        //     }

        //     if (href.includes('id=')) {
        //         let modifiedUrl = href.replace(/(id=)\d+/, '$1' + idValue);
        //         jQuery(el).attr('href', modifiedUrl);   
        //     }
        // })


        //Modification de l'affichage du rgpd (TODO Deplacer dans l'extension civi "ex: makoa_cultureviande")
        //   jQuery('.group-description').each(function () {
        //     // Créez un nouveau div ouvrant
        //     var openingDiv = jQuery('<div class="conteneur-custom-group"></div>');
        
        //     // Récupérez l'élément .group-description actuel
        //     var currentGroupDescription = jQuery(this);
        
        //     // Récupérez tous les éléments .crm-section suivants jusqu'au prochain .group-description
        //     var followingSections = currentGroupDescription.nextUntil('.group-description', '.crm-section');
        
        //     // Ajoutez l'élément .group-description actuel et les éléments .crm-section suivants au div ouvrant
        //     openingDiv.insertAfter(currentGroupDescription);
        //     currentGroupDescription.add(followingSections).appendTo(openingDiv);
        // });
          
        // Fonction pour vérifier la présence de l'élément
 // Vérifie toutes les secondes (ajustez au besoin)

        // Pour les search kit ça sert à intervenir aux requetes api4
        if (typeof CRM !== 'undefined') {
            CRM.$(document).on('ajaxSuccess', function(event, xhr, settings) {
                if (settings.url && settings.url.indexOf('civicrm/ajax') !== -1) {
                    // Parse the response as JSON
                    var responseData = JSON.parse(xhr.responseText);
                    let url = window.location.href;
                    

                    //Vérifier si c'est le formulaire n°1 form contact info
                    if (url.includes('bulletin-de-cotisation-infomration-contact')) {
                        if (settings.url == '/civicrm/ajax/api4/Afform/submit') {//Vérifier si c'est le form builder qui est submité et non pas juste un champ select qui fait de l'ajax
                            if ( $('.cust-form-person-who-filled').val()) {
                                $('.cust-form-submit').click();
                            }
                        }
                    }

                    //Vérifier si c'est le formulaire n°12 "detail des activités"
                    if (url.includes('donnees-economique-entreprise-formulaire-certification')) {
                        if (settings.url == '/civicrm/ajax/api4/Afform/submit') {//Vérifier si c'est le form builder qui est submité et non pas juste un champ select qui fait de l'ajax
                            console.log(responseData.values[0]['Organization1'][0].id, responseData.values[0]['Organization1'][0]['id']);
                            let currentId = responseData.values ? responseData.values[0]['Organization1'][0].id : '';
                            $.ajax({
                                url: '/formulaire/get_url_to_redirect_to_page_certification',
                                data: {cid: currentId},
                                success: (successResult, val, ee) => {
                                    console.log(successResult, ' pmppp')
                                    location.href = "/civicrm/donnees-economique-entreprise-detail-activity-certification?cs="  + successResult.checksum +  "#?id=" + currentId;
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });

                            // $checksum = get_checksum($cid);
                            // $urlTab = '<a href="/civicrm/donnees-economique-entreprise-detail-activity-certification?cs=' . $checksum . '&_authx=' . get_credential_authx($cid) . '&_authxSes=1#?id=' . $cid . '">url</a>';
                            // let url = "civicrm/donnees-economique-entreprise-detail-activity-certification#?id=" + currentId + "";
                            // location.href = url;
                        }
                    }

                    //Pour le calcul de la total dans le form produits commerciaux
                    //Vérifier si c'est le formulaire n°1 form contact info
                    if (url.includes('donnees-economique-entreprise-produit-commercialises')) {
                        if (settings.url == '/civicrm/ajax/api4') {//Vérifier si c'est le form builder qui est submité et non pas juste un champ select qui fait de l'ajax
                            //recuperer la ligne modifié
                            if (responseData.inPlaceEdit && responseData.inPlaceEdit.values) {
                                let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                                $.ajax({
                                    url: '/formulaire/donnees-economique-produits-commerciaux',
                                    data: {valeur: editedVal},
                                    success: (successResult, val, ee) => {
                                    
                                    
                                    },
                                    error: function(error) {
                                        console.log(error, 'ERROR')
                                    }
                                });
                            }
                        }
                    }
                
                    if (responseData.inPlaceEdit && responseData.inPlaceEdit.values) {

                        //Abattage
                        if (url.includes('economique-entreprise-abattages')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/abattage-activity',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        //achat de viande activity
                        if (url.includes('achat-viande')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/achat-viande-activity',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        //achat de viande activity
                        if (url.includes('cotisation-liste-abonnement')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            let cid = location.href.split('id=')[1];
                            $.ajax({
                                url: '/formulaire/buttetin-cotisation-liste-abonnement-activity',
                                data: {valeur: editedVal, cid: cid},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        //contactes activity
                        if (url.includes('cotisation-contact-entreprise')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            let cid = location.href.split('id=')[1];
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/cotisation-contact-entreprise-activity',
                                data: {valeur: editedVal, cid: cid},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('donnee-generale')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/donnee-generale-activity',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                    console.log('activity created', successResult)
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('donnees-economique-entreprise-effectif-annuel')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/donnees-economique-entreprise-effectif-annuel',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                    console.log('activity created for Effectifs', successResult)
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('bulletin-de-cotisation-dirigeants')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            let cid = location.href.split('.entreprise=')[1] 
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/donnees-economique-entreprise-dirigeants',
                                data: {valeur: editedVal, cid: cid},
                                success: (successResult, val, ee) => {
                                    console.log('activity created for Dirigeants', successResult)
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR ONGLET DIRIGEANT')
                                }
                            });
                        }
                        if (url.includes('detail-activity-certification')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                    
                            $.ajax({
                                url: '/activity/donnees-economique-entreprise/detail-activity-certification',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('entreprise-transformation-decoupe')) {
                            let cid = location.href.split('id=')[1];
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/activity/donnees-economique-entreprise/transformation-decoupe',
                                data: {valeur: editedVal, cid:cid},
                                success: (successResult, val, ee) => {
                                    
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('produit-commercialises')) {
                            ajaxActivity ('/formulaire/donnees-economique-entreprise/produit-commercialises', 'id=') 
                        }

                        //creation activité agrement sanitaire
                        if (url.includes('agrement-sanitaire')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            let cid = location.href.split('id=')[1];
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/agrement-sanitaire',
                                data: {valeur: editedVal, cid:cid},
                                success: (successResult, val, ee) => {
                                
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                    }
                }
            });
            }

        //Info entreprise && Agrément sanitaire
        $('body').on('click', '.af-button.btn-info', function(e) { 
            e.preventDefault();
            let userWhoFilledTheForm = jQuery('.person-who-filled').val()

            if (window.location.href.includes('economique-entreprise-agrement-sanitaire')) {
                
                let userMail = jQuery('.person-who-filled-mail').val();
                if (userMail) {//Form info entreprise
                    let contactName = $('.form-control.ng-pristine.ng-untouched.ng-valid.ng-scope.ng-not-empty').val();
                    let cid = window.location.href.split('#?id=')[1];

                    $.ajax({
                        url: '/formulaire/donnees-economique-entreprise/info',
                        data: { usermail: userMail, cid: cid, Cname: userWhoFilledTheForm},
                        success: (successResult, val, ee) => {
                            
                        
                        },
                        error: function(error) {
                            console.log(error, 'ERROR')
                        }
                    });
                }

               
            }
            
        });

         //Après ajout doc
         let messageAddDoc = jQuery('.page-admin-content-media .messages.messages--status').text().includes('Document');
         let messageAddDocCreate = jQuery('.page-admin-content-media .messages.messages--status').text().includes('a été créé');
 
         if (messageAddDoc && messageAddDocCreate) {
             let previousUrl = $('.page-admin-content-media  [name="name"]').attr('data-session');
             let pre = jQuery('.page-admin-content-media .messages.messages--status').html();
             jQuery('.page-admin-content-media .messages.messages--status').html(pre + ' Pour revenir à la page précedente cliquez ici <a href="' + previousUrl + '" > Retour </a>');
 
         }
 
        //Menu lors du chargement de la page
        //TODO condition si C une page taxo : tip ajout attribut pour permettre d'identifier la page
        //TODO condition s'il y a du paramettre dans l'url (peut etre la condition du dessus suffira)
        let currentURL = window.location.pathname + window.location.search;
        
        if ($('.page-taxonomys').length){

            if (jQuery('[href="' + currentURL + '"]').closest('ul').parent('li').hasClass('premier-niv')) {
                jQuery('#block-menuburgerblock [href="' + currentURL + '"]').closest('ul').show()
                //TODO mettre l'icone - pour le menu deplié (ajout classe)
                jQuery('#block-menuburgerblock [href="' + currentURL + '"]').closest('ul').parent('li').addClass('first-level-click');
            }

            if (jQuery('#block-menuburgerblock [href="' + currentURL + '"]').closest('ul').parent('li').hasClass('second-niv')) {
                jQuery('#block-menuburgerblock [href="' + currentURL + '"]').closest('ul').parent('li').closest('ul').show();
                jQuery('#block-menuburgerblock [href="' + currentURL + '"]').closest('ul').parent('li').closest('ul').parent('li').addClass('first-level-click');
            }
        }
        let zip = jQuery('.first-left-element img').attr('src');
   
        if (zip == '/files/assets/Icon metro-file-zip.png') {
            jQuery('.first-left-element img').css('background-color', '#cc4b4c')
            jQuery('.first-left-element img').css('border', '#cc4b4c solid 1px')
        }
        let zip_right = jQuery('.right-element-doc img').attr('src');
   
        if (zip_right == '/files/assets/Icon metro-file-zip.png') {
            jQuery('.right-element-doc img').css('background-color', '#cc4b4c')
            jQuery('.right-element-doc img').css('border', '#cc4b4c solid 1px')
        }

        
    })

    function ajaxActivity (url, split_by) {
        let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
        let cid = location.href.split(split_by)[1];
        $.ajax({
            url: url,
            data: {valeur: editedVal, cid:cid},
            success: (successResult, val, ee) => {
            
            },
            error: function(error) {
                console.log(error, 'ERROR')
            }
        });
    }
    $(document).ready(function() {

        
        var screenWidth = $(window).width();
    
        // Check if screen width is less than 992px
        if(screenWidth < 992 || screenWidth < 768 ){
            // Do something when screen width is less than 992px
            console.log("Screen width is less than 992px");
            

            $('.page-accueil-metier').on('click',  function() {

                
                if($(window).width() < 992 && $(window).width() > 768 ){
                    if ($('.custom-class-site-metier').is(':hidden')) {
                        console.log('miafna')
                        $('.custom-class-site-metier').show();   
                    }else {
                        console.log('mpotra')
                        $('.custom-class-site-metier').hide();   
                    }
                }
    
                //gestion de l'affichage du menu
                if ($('.custom-class-site-metier').is(':hidden')) {
                    $('.custom-class-site-metier').show();   
                }else {
                    $('.custom-class-site-metier').hide();   
                }
            });

            jQuery('.custom-class-site-metier').hide();
            jQuery('.title-bar').show();
            
            $('.page-accueil-metier').on('click', function() {
                if ($('.custom-class-site-metier').is(':hidden')) {
                    $('.custom-class-site-metier').show();   
                }else {
                    $('.custom-class-site-metier').hide();   
                }
            });
        }

        //Permet de bien replacer le texte de description d'un detail metier en responsive
        $('body.section-taxonomy').on('click', '.menu-icon', function() {

            console.log('click fired menu icon')
            //gestion de l'affichage du menu
            if ($('.custom-class-site-metier').is(':hidden')) {
                $('.custom-class-site-metier').show();   
            }else {
                $('.custom-class-site-metier').hide();   
            }

            if($(window).width() < 680 ){
                $('.custom-class-site-metier').toggle();
            }
            if ($("#main-menu").is(":hidden")) {
                console.log('caché', )
                // $('.metier-m-description').css("top", "200px");
            }else {
                // $('.metier-m-description').css("top", "794px");
                console.log('show', $('.custom-class-site-metier').is(':hidden'))
                console.log('affiché')
            }
        })



        //Page detail site metier

        let curr = jQuery('.metier-div-grid-container .paragraph--type--videos > div:nth-child(2)').text();
        let two = curr.split(',');
        let metier = two[1] ? '<p><p class="m-metier uuuu">' + two[1] +'</p>' : '';
        if ((typeof two[1]) == 'string') {
            jQuery('.metier-div-grid-container .paragraph--type--videos > div:nth-child(2)').html('<p>' + two[0] + '</p>' + metier);
        }else {
            jQuery('.metier-div-grid-container .paragraph--type--videos > div:nth-child(2)').hide();
        }
        //end page detail


        $('.nav_custom_class_metier').show();

        if ($('.right-element-doc').length < 1) {
            $('.custom-icon-first-element').css('width', '15%')
        }

        jQuery('.group-description').each(function() {
            var $groupDescription = jQuery(this);
            var $closestCrmSection = $groupDescription.closest('.crm-section');
          
            // Insert the $groupDescription element before the $closestCrmSection element
            $groupDescription.insertBefore($closestCrmSection);
          });
        // jQuery('.page-civicrm-doonee-economique-entreprise form').append(jQuery('.page-civicrm-doonee-economique-entreprise form .btn-primary'))
        $('tr:has(td span.tohide)').remove();
        if (!jQuery('.page-recherche table tbody tr').length) {
            // $('nav.pager').hide();
        }
        let elem = $('.page-social-rh-formations p a:contains("S\'inscrire")');
        elem.css({
            'margin-top': '10px',
            'padding': '15px 42px',
            'background-color': '#3e7269',
            'color': 'white',
            'width': 'fit-content',
            'border-radius': '15px 0 15px',
            'text-transform': 'uppercase',
            'font-size': '0.8em',
            'font-weight': '600',
            'margin-left': '46%'
        });
        


        let illustrationImgMyAccount = jQuery('.section-user .user-form').attr('data-img-illustration')
        jQuery('.section-user .grid-container.hero.communication').css('background-image', 'url( ' + illustrationImgMyAccount + ')')
       
        //Page de confirmation poser question 
        const urlParams = new URLSearchParams(window.location.search);
            // Get a specific parameter by name.
            let  getCid = urlParams.get('cid2');
            if (window.location.href.includes('/poser-une-question/confirmation')) {
                if (window.location.href.includes('poser-une-question')) {
                    $.ajax({
                        url: '/form/poser-une-question/confirmation/back_link',
                        data: {cid: getCid},
                        success: (successResult, val, ee) => {
                            
                            $('.webform-confirmation__back a').attr('href', successResult.back_link)
                        },
                        error: function(error) {
                            console.log(error, 'ERROR')
                        }
                    });
                    
                }
            }



        //page recherche
        // jQuery('.page-recherche .views-element-container table > tbody > tr').has('p.row-to-hide').hide();
        
        //Page ajout document 
        if (jQuery('.field--name-field-tag').length &&  jQuery('.field--name-field-tag').attr('data-default-value')) {

            let allDefaultValue = jQuery('.field--name-field-tag').attr('data-default-value').split(',');
            allDefaultValue.forEach(function (el, index) {
                jQuery('[data-current-id="' + el + '"]').parents('ul').show();
            })
        }

        jQuery('.taxo-image tr:has(a:contains("PDF"))').each(function() {
            var $link = jQuery(this).find('a:contains("PDF")');
            $link.html('<img class="txt-img-custom-pdf" src="/files/assets/pdf-3.png" alt="PDF">');
        });


        $('body .grid-container').on('click','.btn-ask-question-home', function () {
            let questions = $('#textarea-ask-question-some-word').val();
            let category = $('.category-ask-question select').val();
            localStorage.setItem("poser_question_question", questions);
            localStorage.setItem("category", category);

        });
        const storedVariable = sessionStorage.getItem('poser_question_question');

        $('body #main-menu').on('click', '.dropdown-burger-ask-question', () => {
            localStorage.removeItem('poser_question_question');
            localStorage.removeItem('category');
        })
          
        $('[name="civicrm_1_activity_1_cg30_custom_166"]').val(localStorage.getItem("category"))

        setDefaultQuestion ();

        //Ajout document -> tags -> simulation click sur le dropdown ul li
        $('.term-don-t-have-child .fancytree-checkbox').on('click', function () {
            let curr_val = $(this).closest('li').attr('data-current-id');

            // Get the checkbox element using its ID
            var checkbox = $('[name="field_tag[' + curr_val + ']"]');

            // Toggle the checked state of the checkbox using prop()
            checkbox.prop('checked', !checkbox.prop('checked'));
        });

   
        // Attacher un gestionnaire d'événement click à tous les éléments <li> qui sont enfants de 'ul.custom-tag-dropdown'
        $('body').on('click', 'ul.custom-tag-dropdown li', function (event) {
            event.stopPropagation();
            const $submenu = $(this).find('> ul');

            if ($submenu.length > 0) {
                // Masquer tous les sous-menus sauf celui sur lequel vous avez cliqué
                $submenu.slideToggle();
                $submenu.find('ul').slideUp();

                // Ajouter ou supprimer la classe 'fancytree-expanded' au span 'fancytree-expander'
                $(this).find('.fancytree-expander').toggleClass('fancytree-expanded');
            }
        });

        // Parcourir chaque élément <li> qui sont enfants de 'ul.custom-tag-dropdown'
        $('ul.custom-tag-dropdown li').each(function () {
            // Vérifier s'il y a un élément <ul> à l'intérieur de l'élément <li>
            if ($(this).find('ul').length === 0) {
                // Si aucun élément <ul> n'est trouvé, supprimer la classe 'fancytree-expander'
                $(this).find('.fancytree-expander').removeClass('fancytree-expander');

                // Ajouter une marge gauche à l'élément avec la classe 'fancytree-checkbox'
                $(this).find('.fancytree-checkbox').css('margin-left', '19px');
            }
        });
      
       // Utiliser la délégation d'événements pour améliorer les performances
        $('ul.custom-tag-dropdown').on('click', 'li span.fancytree-checkbox', function (event) {
            event.stopPropagation();
            $(this).toggleClass('checked');
        });

        //Form ajout term taxonomie
        $('.taxonomy-term-rubrique-form [name="tvi_enable_override"]').on('change', function () {
            let value = $(this).prop('checked');
            $('[name="field_taxonomy_views_integrator_[0][value]"]').val(value)
        });
    }); 

    // Fixer les boutton enregitrement et suppression de document quand il est en dehors du section parent'
    var $buttonSelector = $('.custom-add-and-edit-form #edit-actions, #block-adminimal-theme-content');
    var $sectionCible = $('section#main, #block-adminimal-theme-content');
    var sectionOffsetTop = $sectionCible.offset().top;
    var sectionHeight = $sectionCible.outerHeight();

    $(window).scroll(function() {
        var scrollTop = $(window).scrollTop();
        var isInSection = (scrollTop >= sectionOffsetTop && scrollTop <= sectionOffsetTop + sectionHeight);

        $buttonSelector.toggleClass('fixed-button', !isInSection);
    });
    
})(jQuery);

function setDefaultQuestion () {
        if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.replace('edit-civicrm-1-activity-1-activity-details-value', {
            // Add any CKEditor configuration options here if needed
        });
    }
    
    // Function to set value to the CKEditor field
    function setValueToCKEditorField() {
        if (typeof CKEDITOR !== 'undefined') {
        const editorInstance = CKEDITOR.instances['edit-civicrm-1-activity-1-activity-details-value'];
        if (editorInstance) {
            // Set the value of the CKEditor instance
            editorInstance.setData(localStorage.getItem("poser_question_question"));
            }
        }
    }

    // Call the function to set the value (you can trigger this event on any action)
    setValueToCKEditorField();
}

function verifierElement() {
    var element = jQuery('[class*="page-civicrm page-civicrm-donnee-eoconomique"] .crm-form-date-wrapper [type="number"]');
    if (element.val()) {

        var currentYear = new Date().getFullYear();
        // Calculate the last year
        var lastYear = currentYear - 1;

        // Parcourir les elements 
        jQuery('[class*="page-civicrm page-civicrm-donnee-eoconomique"] .crm-form-date-wrapper [type="number"]').each(function(id, el){
            if (lastYear != jQuery(el).val()) {
                //jQuery(el).closest('[ng-repeat="item in getItems()"]').remove()
                jQuery(el).closest('[ng-repeat="item in getItems()"]').hide();

            }
        });
        
        // Arrêtez de vérifier l'élément après l'avoir trouvé
        clearInterval(verifierElementInterval);
    }
}

var verifieraddOverlayToVideo= setInterval(addOverlayToVideo, 50);
function addOverlayToVideo() {
    let iframe = jQuery('body').find('.video-block-cus > div > div').length;
    
    
    if (iframe) {
        
        let iframe = jQuery('body').find('.video-block-cus > div').addClass('div-parent-overlay');
        jQuery('body').find('.video-block-cus > div > div').prepend('<div class="custom-overlay-hide-logo"></div>');
        console.log(jQuery('body').find('.video-block-cus > div > div').length, ' atos')
        jQuery('.page-accueil-metier iframe').each(function(id, el) {
            let top = jQuery(el).offset().top
            let left = jQuery(el).offset().left
            jQuery(el).prev().css('top', '0px');
            jQuery(el).prev().css('left', '0px');
        })

        // Arrêtez de vérifier l'élément après l'avoir trouvé
        console.log('test')
        clearInterval(verifieraddOverlayToVideo);
        
    }
}
var VerifaddOverlayToVideoDetail= setInterval(addOverlayToVideoDetail, 50);
function addOverlayToVideoDetail () {
    let iframePageDetail = jQuery('body').find('.term-metier-viandes .paragraph.paragraph--type--videos').length;
    
    if (iframePageDetail) {
        jQuery('body').find('.term-metier-viandes .paragraph.paragraph--type--videos > div > div > div ').addClass('div-parent-overlay');
        jQuery('body').find('.term-metier-viandes .paragraph.paragraph--type--videos > div > div > div ').prepend('<div class="custom-overlay-hide-logo"></div>');
    }
    clearInterval(VerifaddOverlayToVideoDetail);
}

// Vérifiez périodiquement la présence de l'élément
var verifierElementInterval = setInterval(verifierElement, 50);