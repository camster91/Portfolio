/* global MotomotusSort */
(function ($) {
    'use strict';

    $(function () {
        var $list = $('#motomotus-sort-list');
        if (!$list.length || typeof MotomotusSort === 'undefined') {
            return;
        }

        var $status = $('#motomotus-sort-status');
        var saveTimer = null;
        var inFlight = false;
        var pendingSave = false;

        function setStatus(text, kind) {
            $status
                .text(text)
                .removeClass('is-saving is-saved is-error')
                .addClass(kind ? 'is-' + kind : '');
        }

        function collectOrder() {
            var ids = [];
            $list.children('li').each(function () {
                ids.push($(this).data('id'));
            });
            return ids;
        }

        function renumberMeta() {
            var total = $list.children('li').length;
            $list.children('li').each(function (index) {
                $(this).find('.motomotus-sort-meta').text('#' + (total - index));
            });
        }

        function saveOrder() {
            if (inFlight) {
                pendingSave = true;
                return;
            }

            inFlight = true;
            setStatus(MotomotusSort.i18n.saving, 'saving');

            $.post(MotomotusSort.ajaxUrl, {
                action: 'motomotus_save_order',
                nonce: MotomotusSort.nonce,
                order: collectOrder().join(','),
                post_status: MotomotusSort.context.postStatus,
                collection: MotomotusSort.context.collection,
                artist: MotomotusSort.context.artist
            })
                .done(function (response) {
                    if (response && response.success) {
                        setStatus(MotomotusSort.i18n.saved, 'saved');
                        if (saveTimer) {
                            clearTimeout(saveTimer);
                        }
                        saveTimer = setTimeout(function () {
                            $status.text('').removeClass('is-saved');
                        }, 1800);
                    } else {
                        setStatus(MotomotusSort.i18n.error, 'error');
                    }
                })
                .fail(function () {
                    setStatus(MotomotusSort.i18n.error, 'error');
                })
                .always(function () {
                    inFlight = false;
                    if (pendingSave) {
                        pendingSave = false;
                        saveOrder();
                    }
                });
        }

        $list.sortable({
            handle: '.motomotus-sort-handle',
            placeholder: 'ui-sortable-placeholder motomotus-sort-item',
            tolerance: 'pointer',
            axis: 'y',
            opacity: 0.9,
            update: function () {
                renumberMeta();
                saveOrder();
            }
        });
    });
})(jQuery);
