<script>
jQuery(document).on("click", ".js__comment_edit", function () {
    let item = jQuery(this).data('item');
    document.querySelector('.js_comment').value = item.comment;
    document.querySelector('.js_comid').value = item.comid;
});
jQuery(document).on("click", ".js__approve_modal", function () {
    let form_data = new FormData();
    form_data.append('comment',jQuery('.js_comment').val());
    form_data.append('comid',jQuery('.js_comid').val());
    form_data.append('approved',jQuery(this).data('value'));
    sendFetch(form_data);
    jQuery(this).closest('div.modal').modal('toggle');
});
jQuery(document).on("click", ".js__approve_comment", function (event) {
    event.preventDefault();

    let button = jQuery(this);
    if (button.data('loading')) {
        return;
    }

    let form_data = new FormData();
    let item = button.closest('tr').find('.js__comment_edit').data('item') || {};
    form_data.append('comment', item.comment || '');
    form_data.append('comid', item.comid || '');
    form_data.append('approved', button.attr('data-value'));
    sendFetch(form_data, button);
});
function sendFetch(form_data, sourceButton)
{
    if (sourceButton) {
        sourceButton.data('loading', true).prop('disabled', true);
    }

    fetch((window.sArticlesAdminConfig && window.sArticlesAdminConfig.routes && window.sArticlesAdminConfig.routes.commentApprove) || "/sarticles/comment-approve", {
        method: "POST",
        cache: "no-store",
        body: form_data
    }).then((response) => {
        return response.json()
    }).then((data) => {
        if (data)
        {
            let tr = jQuery('#comment'+data.comment.comid);
            let button = tr.find('.js__approve_comment');
            if (data.comment.approved == 1)
            {
                button.removeData('value');
                button.attr('data-value', 0);
                button.attr('title', '@lang('sArticles::global.comment_hidden')');
                button.attr('aria-label', '@lang('sArticles::global.comment_hidden')');
                button.removeClass('btn-outline-danger btn-danger btn-primary btn-info');
                button.addClass('btn-outline-success');
            }
            else
            {
                button.removeData('value');
                button.attr('data-value', 1);
                button.attr('title', '@lang('sArticles::global.approved')');
                button.attr('aria-label', '@lang('sArticles::global.approved')');
                button.removeClass('btn-outline-success btn-success btn-primary btn-info');
                button.addClass('btn-outline-danger');
            }
            let comment = data.comment.comment;
            tr.find('#comment'+data.comment.comid+'tinytext').html('<b>'+comment+'</b>')
            tr.find('.js__comment_edit').data('item', data.comment);
        }
    }).catch(function(error) {
        console.error("Request failed", error, ".")
    }).finally(function() {
        if (sourceButton) {
            sourceButton.data('loading', false).prop('disabled', false);
        }
    });
}
</script>
