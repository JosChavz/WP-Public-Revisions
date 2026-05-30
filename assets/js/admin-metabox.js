jQuery(document).ready(function ($) {
    const createBtn = $("#wppr_create-revision-btn");
    const container = $("#wppr_btn-disabled-text");
    const historyDialogBtn = $("#wppr_history-revisions-btn");
    const historyDialog = $("#wppr_history-revisions-dialog");
    const historyDialogCloseBtn = $("#wppr_history-revision-close-btn");

    let requiresUpdate = true;

    let prevShouldDisable = null;

    wp.data.subscribe(() => {
        const status = wp.data
            .select("core/editor")
            .getEditedPostAttribute("status");
        const isDirty = wp.data.select("core/editor").isEditedPostDirty();

        const shouldDisable =
            status === "auto-draft" ||
            status === "draft" ||
            (status === "publish" && isDirty);

        if (shouldDisable === prevShouldDisable) {
            return;
        }

        prevShouldDisable = shouldDisable;
        createBtn.prop("disabled", shouldDisable);
        container.css("display", shouldDisable ? "block" : "none");
    });

    createBtn.on("click", function () {
        const confirmation = confirm("Create new revision?");

        if (!confirmation) return;

        const postID = createBtn.data("post-id");

        $.ajax({
            url: wppr_ajax_obj.ajaxurl,
            type: "POST",
            data: {
                action: "wppr_create_revision",
                nonce: wppr_ajax_obj.nonce,
                post_id: postID,
            },
            success: function (response) {
                if (response.success) {
                    const revisionCountEl = $("#wppr_revision-count");
                    const revisionCount = Number(revisionCountEl.text());
                    revisionCountEl.html(revisionCount + 1);

                    requiresUpdate = true;
                } else {
                    alert("Something went wrong!" + response.data);
                }
            },
        });
    });

    function deleteRevision(e, revId, postID) {
        const confirmDeletion = confirm(
            `Are you sure you want to delete revision #${revId}?`,
        );
        if (!confirmDeletion) return;

        const currentTarget = $(e.currentTarget).parent(".wppr-revision-wrapper");

        $.ajax({
            url: wppr_ajax_obj.ajaxurl,
            type: "POST",
            data: {
                action: "wppr_delete_revision",
                delete_nonce: wppr_ajax_obj.delete_nonce,
                rev_id: revId,
                post_id: postID,
            },
            success: function (response) {
                if (!response.success || !response.data) {
                    console.error("Something went wrong with deleting ", revId);
                    return;
                }

                // Delete the element from the DOM
                currentTarget.remove();
                const dialogContent = $("#wppr_dialog-results");
                if (dialogContent.children().length === 0) {
                    dialogContent.html("<p>No revision history</p>");
                }

                // Update the counter outside
                const revisionCountEl = $("#wppr_revision-count");
                const revisionCount = Number(revisionCountEl.text());
                revisionCountEl.html(revisionCount - 1);
            },
        });
    }

    // Opens the dialog and fetches the revisions
    historyDialogBtn.on("click", () => {
        historyDialog[0].showModal();

        if (!requiresUpdate) return;

        const postID = createBtn.data("post-id");
        const dialogContent = $("#wppr_dialog-results");

        $.ajax({
            url: wppr_ajax_obj.ajaxurl,
            type: "GET",
            data: {
                action: "wppr_fetch_revisions",
                fetch_nonce: wppr_ajax_obj.fetch_nonce,
                post_id: postID,
            },
            success: function (response) {
                if (!response.success) {
                    dialogContent.html("<p>Something went wrong.</p>");
                    return;
                }

                const revisions = response.data;
                dialogContent.html("");

                if (revisions.length === 0) {
                    dialogContent.html("<p>No revision history.</p>");
                }

                revisions.forEach((revision) => {
                    const templateEl = $("<div>", {
                        class: "wppr-revision-wrapper",
                    });
                    const innerWrapper = $("<div>", {
                        class: "wppr-revision-data-wrapper",
                    });
                    innerWrapper.append($("<p>").text(`Revision No: ${revision.rev_no}`));
                    innerWrapper.append(
                        $("<span>").append(
                            $("<time>")
                                .attr("datetime", revision.timestamp)
                                .text(revision.timestamp),
                        ),
                    );
                    const deleteBtn = $("<button>", {
                        class: "wppr_metabox-btn wppr_revision-delete",
                    })
                        .text("Delete Revision")
                        .click((e) => {
                            deleteRevision(e, Number(revision.id), Number(revision.post_id));
                        });

                    templateEl.append(innerWrapper);
                    templateEl.append(deleteBtn);
                    dialogContent.append(templateEl);
                });

                requiresUpdate = false;
            },
            error: function (e) {
                console.error(e);
            },
        });
    });

    historyDialogCloseBtn.on("click", () => {
        historyDialog[0].close();
    });
});
