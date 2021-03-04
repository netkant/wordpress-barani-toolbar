(function($) {
    // ...
    var toggleSpinner = function() {
        var menuItem = $("li#wp-admin-bar-barani-toolbar");
        menuItem.hasClass("spinner") ? menuItem.removeClass("spinner") : menuItem.addClass("spinner") ;
    }

    // ...
    var clearCache = function(btn, msg, action) {
        // ...
        if ($(btn).hasClass("disabled") || !confirm(msg)) { 
            return;
        }

        // ...
        $(btn).blur();
        $(btn).addClass("disabled");
        toggleSpinner();

        // ...
        $.ajax({
            url: barani_toolbar.ajaxurl, 
            method: "POST",
            data: { "action": action },
        }).always(function(jqXHR) {
            toggleSpinner();
            $(btn).removeClass("disabled");
        });
    }

    // ...
    $(document).on("click", "li#wp-admin-bar-barani-toolbar > a.ab-item", function(e) {
        e.preventDefault();
    });

    // ...
    $(document).on("click", "li#wp-admin-bar-barani-clear-page-cache > a.ab-item", function(e) { 
        e.preventDefault();
        clearCache(this, "This could potentially slow down your website, until cache has been rebuild.\n\nAre you sure that you want to CLEAR PAGE CACHE?", "barani_clear_page_cache");
    });

    // ...
    $(document).on("click", "li#wp-admin-bar-barani-clear-all-cache > a.ab-item", function(e) { 
        e.preventDefault();
        clearCache(this, "This could potentially slow down your website, until cache has been rebuild.\n\nAre you sure that you want to CLEAR ALL CACHE?", "barani_clear_all_cache");
    });
})(jQuery);
