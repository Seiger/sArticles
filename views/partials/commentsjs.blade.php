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
jQuery(document).on("click", ".js__approve_comment", function () {
    let form_data = new FormData();
    let item = jQuery(this).closest('tr').find('.js__comment_edit').data('item');
    form_data.append('comment',item.comment);
    form_data.append('comid',item.comid);
    form_data.append('approved',jQuery(this).data('value'));
    sendFetch(form_data);
});
function sendFetch(form_data)
{
    fetch("/sarticles/comment-approve/", {
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
                button.data('value', 0);
                button.removeClass('btn-primary');
                button.addClass('btn-info');
                button.find('span').html('@lang('sArticles::global.comment_hidden')');
            }
            else
            {
                button.data('value', 1);
                button.removeClass('btn-info');
                button.addClass('btn-primary');
                button.find('span').html('@lang('sArticles::global.approved')');
            }
            let comment = data.comment.comment;
            tr.find('#comment'+data.comment.comid+'tinytext').html('<b>'+comment+'</b>')
            tr.find('.js__comment_edit').data('item', data.comment);
        }
    }).catch(function(error) {
        console.error("Request failed", error, ".")
    });
}
</script>
