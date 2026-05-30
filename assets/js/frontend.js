jQuery(document).ready(function ($) {
    const toggleButton = document.getElementsByClassName("wppr-previous-button")[0];
    const historyBox = document.getElementsByClassName("wppr-history-box")[0];
    let fetched = false;
    const resultContainer = $('.wppr-history-box');
    const postID = $(toggleButton).data('post-id');
    const dateFormat = $(toggleButton).data('date-format');
    const {__} = wp.i18n;

    if (!!toggleButton) {
        toggleButton.addEventListener("click", (e) => {
            const style = getComputedStyle(historyBox);
            let isClosed = style.display === 'none';
            historyBox.style.display = isClosed ? "block" : "none";
            $(toggleButton).text(isClosed ? __("Hide revision history", "wp-public-revisions") : __("Show revision history", "wp-public-revisions"));

            if (fetched) return;

            $.ajax({
                url: wppr_frontend_ajax_obj.ajaxurl,
                type: 'GET',
                data: {
                    action: "wppr_fetch_revisions",
                    nonce: wppr_frontend_ajax_obj.nonce,
                    post_id: postID,
                    date_format: dateFormat,
                },
                success: (response) => {
                    if (!response.success) {
                        console.error("Something went wrong!");
                        return;
                    }

                    const revisions = response.data;
                    const olEl = $('<ol>', {
                        class: 'wppr-list',
                    })
                        .attr('reverse', true);

                    revisions.forEach(revision => {
                        const liEl = $('<li>');
                        const anchor = $('<a>')
                            .text(`${__("Revision", "wp-public-revisions")} - ${revision.timestamp}`)
                            .attr('href', `./?wppr_view_revision=${revision.id}`)


                        liEl.append(anchor);
                        olEl.append(liEl);
                    });

                    resultContainer.html('');
                    resultContainer.append(olEl);
                }
            })
        });
    }
});