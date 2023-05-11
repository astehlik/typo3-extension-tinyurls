/**
 * Handles the click events of the copyable field clipboard buttons.
 */

'use strict';

// noinspection NpmUsedModulesInstalled,JSFileReferences
import Notification from '@typo3/backend/notification.js';

class CopyToClipboard {
  constructor() {
    document.querySelectorAll('.tx-tinyurls-copyable-field-wrap')
      .forEach((copyableFieldContainer) => this.initializeSingleField(copyableFieldContainer));
  }

  initializeSingleField(copyableFieldContainer) {
    const that = this;
    const copyButton = copyableFieldContainer.querySelector('.tx-tinyurls-copyable-field-copy-button');
    const valueField = copyableFieldContainer.querySelector('.tx-tinyurls-copyable-field-value');

    valueField.addEventListener('focus', function () { this.select(); });

    copyButton.addEventListener('click', async function () {
      try {
        await that.writeToClipboard(valueField);
        Notification.success(
          TYPO3.lang['tx_tinyurls.copy_to_clipboard.success.title'],
          TYPO3.lang['tx_tinyurls.copy_to_clipboard.success.message']
        );
      } catch (err) {
        Notification.warning(
          TYPO3.lang['tx_tinyurls.copy_to_clipboard.error.title'],
          TYPO3.lang['tx_tinyurls.copy_to_clipboard.error.message']
        );
      }
    });
  }

  async writeToClipboard(selectField) {
    // Fallback to legacy copy method if clipboard API is not available.
    if (!navigator.clipboard) {
      selectField.select();
      document.execCommand('copy');
      return;
    }

    await navigator.clipboard.writeText(selectField.value);
  }
}

export default new CopyToClipboard();
