/**
 * Module: TYPO3/CMS/Tinyurls/CopyToClipboard
 *
 * Handles the click events of the copyable field clipboard buttons.
 */
define(
    ['jquery', 'TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Lang/Lang'],
    function ($, notification, lang) {
        'use strict';

        var CopyToClipboard = {

            initialize: function () {
                var me = this;
                $('.tx-tinyurls-copyable-field-wrap').each(function () {
                    me.initializeSingleField($(this));
                });
            },

            initializeSingleField: function (copyableFieldContainer) {

                var copyButton = copyableFieldContainer.find('.tx-tinyurls-copyable-field-copy-button');
                var valueField = copyableFieldContainer.find('.tx-tinyurls-copyable-field-value');

                valueField.focus(function () {
                    valueField.select();
                });

                copyButton.click(function () {
                    try {
                        valueField.select();
                        document.execCommand('copy');
                        notification.success(
                            lang['tx_tinyurls.copy_to_clipboard.success.title'],
                            lang['tx_tinyurls.copy_to_clipboard.success.message']
                        );
                    } catch (err) {
                        notification.warning(
                            lang['tx_tinyurls.copy_to_clipboard.error.title'],
                            lang['tx_tinyurls.copy_to_clipboard.error.message']
                        );
                    }
                });
            }
        };


        CopyToClipboard.initialize();

        return CopyToClipboard;
    }
);
