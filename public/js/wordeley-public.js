/**
 * @file Provides global functions for the Wordeley public interface.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */


var $ = jQuery;

/**
 *
 * Display an alert container relative to the referenced element.
 *
 * @param {string} selector The DOM selector of the element that will be alerted about.
 * @param {string} message The message that will be displayed in the alert.
 * @param {string} type The type of alert (either an `'error'` or `'notice'`)/
 * @param {Boolean} placeBefore Whether the alert is placed before the selected element.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function showAlert(
    selector,
    message,
    type = "error",
    placeBefore = false,
    disappearing = true
) {
    if (placeBefore) {
        $(selector).before(
            '<div class="wordeley-' +
            type +
            ' animated"><span>' +
            message +
            "</span></div>"
        );
    } else {
        $(selector).after(
            '<div class="wordeley-' +
            type +
            ' animated"><span>' +
            message +
            "</span></div>"
        );
    }
    if (disappearing) {
        setTimeout(() => {
            $(selector).next().addClass("seen");
        }, 3500);
        setTimeout(() => {
            $(selector).next().remove();
        }, 3700);
    }
}

/**
 * Make a WP request.
 *
 * This function handles success data using the `completion` and appends errors automatically.
 *
 * @param {any} trigger The selector of the DOM element triggering the action.
 * @param {string} action The action as registered in {@link ../../includes/class-wordeley.php}
 * @param {string} nonce The single nonce appointed to the action.
 * @param {Object} data The array of data to be included in the request.
 * @param {Function} completion The actions to perform when the response was successful.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */
function makeWPRequest(trigger, action, nonce, data, completion) {
    function completionHandler(response) {
        if (response.status === 200) {
            try {
                // Parse the data.
                if (response.responseText == '') {
                    completion();
                }
                else {
                    completion(response.responseJSON ?? JSON.parse(response.responseText));
                }
            } catch (objError) {
                completion();
            }
        } else if (response.status === 400 || response.status === 500) {
            showAlert(trigger, response.responseText, "error");
        } else {
            showAlert(
                trigger,
                "There was an unknown connection error, please try again later.",
                "error"
            );

            // Log additional information into the console.
            console.error("[Wordeley]" + response.responseText);
        }

        // Remove the loading class.
        $(trigger).removeClass("loading");
        $(trigger).closest('form').removeClass("loading");
        if (typeof trigger === 'string') {
            if (trigger.includes("button")) {
                $(trigger).prop("disabled", false);
            }
        }
    }

    // Add the loading class.
    $(trigger).addClass("loading");
    $(trigger).closest('form').addClass("loading");
    if (typeof trigger === 'string') {
        if (trigger.includes("button")) {
            $(trigger).prop("disabled", true);
        }
    }

    if (data instanceof FormData) {
        data.append("action", action);
        data.append("nonce", nonce);

        // Perform AJAX request.
        $.ajax({
            url: PublicProperties.AJAXEndpoint,
            type: "post",
            data: data,
            contentType: false,
            processData: false,
            complete: completionHandler,
        });
    } else if (typeof data === "object") {
        // Prepare data fields for WordPress.
        data.action = action;
        data.nonce = nonce;

        // Perform AJAX request.
        $.ajax({
            url: PublicProperties.AJAXEndpoint,
            type: "post",
            data: data,
            dataType: "json",
            complete: completionHandler,
        });
    }
}

$(document).ready(() => {
    function handleFilterSubmission(e) {

        e.preventDefault();
        var formData = new FormData(e.target);

        // Read page value and use intact.
        var page = $('.wordeley-pagination .active').html();
        formData.append('article-page', page);

        makeWPRequest(
            '.wordeley-catalogue',
            'wordeley_get_articles',
            PublicProperties.GetArticlesNonce,
            formData,
            (articlesHTML) => {
                $('.wordeley-catalogue').replaceWith(articlesHTML);
            }
        );
    }

    $('body').on(
        "submit",
        ".wordeley-catalogue-filters form",
        (e) => handleFilterSubmission(e));

    $("input").keypress((e) => {
        if ($('.wordeley-catalogue').length) {
            $(".wordeley-catalogue-filters form").submit();
            return false;
        }
    });

    $("body").on(
        'click',
        '.wordeley-pagination a:not(.active)',
        (e) => {
            e.preventDefault();
            // Read form data and use intact.
            var formData = new FormData($(".wordeley-catalogue-filters form")[0]);
            formData.append('article-page', $(e.target).html());

            makeWPRequest(
                '.wordeley-catalogue',
                'wordeley_get_articles',
                PublicProperties.GetArticlesNonce,
                formData,
                (articlesHTML) => {
                    $('.wordeley-catalogue').replaceWith(articlesHTML);
                }
            );
        }
    )
});
