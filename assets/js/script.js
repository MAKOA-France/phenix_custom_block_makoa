(function($) {
    $(window).on('load', function () {

         //Après ajout doc
         let messageAddDoc = jQuery('.page-admin-content-media .messages.messages--status').text().includes('Document');
         let messageAddDocCreate = jQuery('.page-admin-content-media .messages.messages--status').text().includes('a été créé');
 console.log('has m', messageAddDoc)
 
         if (messageAddDoc && messageAddDocCreate) {
             let previousUrl = $('.page-admin-content-media  [name="name"]').attr('data-session');
             let pre = jQuery('.page-admin-content-media .messages.messages--status').html();
             jQuery('.page-admin-content-media .messages.messages--status').html(pre + ' Pour revenir à la page précedente cliquez ici <a href="' + previousUrl + '" > Retour </a>');
 
             console.log('zz m', pre)
         }
 
        //Menu lors du chargement de la page
        //TODO condition si C une page taxo : tip ajout attribut pour permettre d'identifier la page
        //TODO condition s'il y a du paramettre dans l'url (peut etre la condition du dessus suffira)
        let currentURL = window.location.pathname + window.location.search;
        console.log($('.this-is-taxo-page .content-menu-burger').length, 'll',$('body').find('.content-menu-burger').length)
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
        console.log(zip, 'ii')
        if (zip == '/files/assets/Icon metro-file-zip.png') {
            jQuery('.first-element-doc a img').css('background-color', '#cc4b4c')
            jQuery('.first-element-doc a img').css('border', '#cc4b4c solid 1px')
        }
    })
    $(document).ready(function() {



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
                            console.log('back liank ', successResult)
                            $('.webform-confirmation__back a').attr('href', successResult.back_link)
                        },
                        error: function(error) {
                            console.log(error, 'ERROR')
                        }
                    });
                    
                }
            }



        //page recherche
        jQuery('.page-recherche .views-element-container table > tbody > tr').has('p.row-to-hide').hide();
        
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
            console.log()
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