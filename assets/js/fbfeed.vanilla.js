/*
 * Facebook Feed: "Load more" in vanilla JavaScript (no jQuery).
 *
 * Uses Winter's Snowboard AJAX (window.Snowboard), so the request carries the CSRF token the
 * same way the rest of the site does. The layout must include {% snowboard request %}. Without
 * it the button is hidden and the feed still shows its first posts.
 */
(function () {
    'use strict';

    function setLoading(button, loading) {
        button.disabled = loading;
        var label = button.querySelector('.fb-load-more-label');
        var spinner = button.querySelector('.fb-load-more-spinner');
        if (label) label.style.display = loading ? 'none' : '';
        if (spinner) spinner.style.display = loading ? '' : 'none';
    }

    function init(feed) {
        var button = feed.querySelector('[data-fb-load-more]');
        if (!button) return;

        var wrap = feed.querySelector('[data-fb-load-more-wrap]');
        var grid = feed.querySelector('[data-fb-grid]');
        var handler = button.getAttribute('data-handler');

        if (!window.Snowboard || !handler || !grid) {
            if (wrap) wrap.style.display = 'none';
            return;
        }

        var page = 2;
        var busy = false;

        button.addEventListener('click', function () {
            if (busy) return;
            busy = true;
            setLoading(button, true);

            window.Snowboard.request(button, handler, {
                data: { page: page },
                success: function (data) {
                    busy = false;
                    setLoading(button, false);

                    if (data && data.posts_html) {
                        grid.insertAdjacentHTML('beforeend', data.posts_html);
                    }
                    if (data && data.has_more) {
                        page = data.next_page;
                    } else if (wrap) {
                        wrap.style.display = 'none';
                    }
                },
                error: function () {
                    busy = false;
                    setLoading(button, false);
                }
            });
        });
    }

    function initAll() {
        document.querySelectorAll('[data-fb-feed]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
