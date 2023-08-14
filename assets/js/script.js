(function($) {
    $(document).ready(function() {

        jQuery('.section-civicrm-group  .layout__region.layout__region--content > .block-layout-builder > div').html('<p><i class="icon-custom-calendar"></i>Réunions</p>');//page detail commission

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
        $('.fancytree-checkbox').on('click', function () {
            let curr_val = $(this).closest('li').attr('data-current-id');

            // Get the checkbox element using its ID
            var checkbox = $('[name="field_tags[' + curr_val + ']"]');

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

        /* $('body').on('click', '[name="field_media_document_0_remove_button"]', function (){
            console.log('trest')
        }) */
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