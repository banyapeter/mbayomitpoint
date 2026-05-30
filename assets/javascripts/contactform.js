$(function () {
    "use strict";
    
    /*  
        Template name    : MasevTech - IT Solutions & Business Services Multipurpose Responsive Website Template
        Author           : Banya
        Version          : 1.0
        File Description : contact js file of the template
    */
    $('#ajax-contact').validator();
    $('#ajax-contact').on('submit', function (e) {
        if (!e.isDefaultPrevented()) {
            var url = "index.html";
            $.ajax({
                type: "POST",
                url: url,
                data: $(this).serialize(),
                success: function (data)
                {
                    $( "#msgSubmit" ).removeClass( "hidden" );
                    $('#ajax-contact')[0].reset();
                    
                }
            });
            return false;
        }
    })
});