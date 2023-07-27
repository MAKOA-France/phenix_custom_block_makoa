(function($) {
    $(document).ready(function() {
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