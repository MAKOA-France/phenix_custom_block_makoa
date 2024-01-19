(function($) {
    $(window).on('load', function () {

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
                
                    if (responseData.inPlaceEdit && responseData.inPlaceEdit.values) {

                        //Données générales
                        if (url.includes('achat-viandes')) {
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
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/activity/donnees-economique-entreprise/transformation-decoupe',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                    
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('produit-commercialises')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/produit-commercialises',
                                data: {valeur: editedVal},
                                success: (successResult, val, ee) => {
                                    console.log('activity produit commercialises created', successResult)
                                
                                },
                                error: function(error) {
                                    console.log(error, 'ERROR')
                                }
                            });
                        }
                        if (url.includes('agrement-sanitaire')) {
                            let editedVal = JSON.stringify(responseData.inPlaceEdit.values[0]);
                            $.ajax({
                                url: '/formulaire/donnees-economique-entreprise/agrement-sanitaire',
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
        let zip = jQuery('.first-element-doc a img').attr('src');
   
        if (zip == '/files/assets/Icon metro-file-zip.png') {
            jQuery('.first-element-doc a img').css('background-color', '#cc4b4c')
            jQuery('.first-element-doc a img').css('border', '#cc4b4c solid 1px')
        }
    })
    $(document).ready(function() {

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
    CKEDITOR.replace('edit-civicrm-1-activity-1-activity-details-value', {
        // Add any CKEditor configuration options here if needed
    });
    
    // Function to set value to the CKEditor field
    function setValueToCKEditorField() {
    const editorInstance = CKEDITOR.instances['edit-civicrm-1-activity-1-activity-details-value'];
    if (editorInstance) {
        // Set the value of the CKEditor instance
        editorInstance.setData(localStorage.getItem("poser_question_question"));
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

// Vérifiez périodiquement la présence de l'élément
var verifierElementInterval = setInterval(verifierElement, 50);