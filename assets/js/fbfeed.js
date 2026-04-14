$(function () {
    var $btn  = $('#fb-load-more-btn');
    var $wrap = $('#fb-load-more-wrap');
    var $grid = $('#fb-posts-grid');

    if (!$btn.length) return;

    var handler     = $btn.data('handler');
    var fbFeedPage  = 2;
    var fbFeedLoading = false;

    function fbSetLoading(loading) {
        fbFeedLoading = loading;
        $btn.find('.fb-load-more-label').toggle(!loading);
        $btn.find('.fb-load-more-spinner').toggle(loading);
    }

    $btn.on('click', function () {
        if (fbFeedLoading) return;
        fbSetLoading(true);

        $.request(handler, {
            data: { page: fbFeedPage },
            success: function (data) {
                if (data.posts_html) {
                    $grid.append(data.posts_html);
                }
                if (data.has_more) {
                    fbFeedPage = data.next_page;
                    fbSetLoading(false);
                    $wrap.show();
                } else {
                    fbFeedLoading = false;
                    $wrap.hide();
                }
            },
            error: function () {
                fbSetLoading(false);
            }
        });
    });
});
