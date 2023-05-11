/**
 * Module: TYPO3/CMS/Tinyurls/CopyToClipboard
 *
 * Handles the click events of the copyable field clipboard buttons.
 */
define(
    ['jquery', 'TYPO3/CMS/Backend/Notification'],
    function ($, notification) {
        'use strict';

        const CopyToClipboard = {
            initialize: function () {
                const me = this;
                $('.tx-tinyurls-copyable-field-wrap').each(function () {
                    me.initializeSingleField($(this));
                });
            },

            initializeSingleField: function (copyableFieldContainer) {
                const me = this;

                const copyButton = copyableFieldContainer.find('.tx-tinyurls-copyable-field-copy-button');
                const valueField = copyableFieldContainer.find('.tx-tinyurls-copyable-field-value');

                valueField.focus(function () {
                    valueField.select();
                });

                copyButton.click(async function () {
                    try {
                        await me.writeToClipboard(valueField);
                        notification.success(
                            TYPO3.lang['tx_tinyurls.copy_to_clipboard.success.title'],
                            TYPO3.lang['tx_tinyurls.copy_to_clipboard.success.message']
                        );
                    } catch (err) {
                        console.log(err);
                        notification.warning(
                            TYPO3.lang['tx_tinyurls.copy_to_clipboard.error.title'],
                            TYPO3.lang['tx_tinyurls.copy_to_clipboard.error.message']
                        );
                    }
                });
            },

            writeToClipboard: async function (selectField) {
                // Fallback to legacy copy method if clipboard API is not available.
                if (!navigator.clipboard){
                    selectField.select();
                    document.execCommand('copy');
                    return;
                }

                await navigator.clipboard.writeText(selectField[0].value);
            }
        };

        CopyToClipboard.initialize();

        return CopyToClipboard;
    }
);
