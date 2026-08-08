# tinyurls TYPO3 Extension

> [!WARNING]
> This extension is deprecated for TYPO3 14+ as the core now includes a native [Short URL feature](https://docs.typo3.org/c/typo3/cms-core/main/en-us//Changelog/14.2/Feature-108826-AddShortURLModule.html).
>
> To migrate your existing records to core redirects, see [Migrating to core redirects](#migrating-to-core-redirects) below (will become available with the v14 release of this Extension).
>
> If you believe this extension still offers unique value, I'd love to hear from you. Please [open a ticket](https://github.com/astehlik/typo3-extension-tinyurls/issues) to share your feedback.

[![.github/workflows/test.yml](https://github.com/astehlik/typo3-extension-tinyurls/actions/workflows/test.yml/badge.svg)](https://github.com/astehlik/typo3-extension-tinyurls/actions/workflows/test.yml)
[![Maintainability](https://qlty.sh/gh/astehlik/projects/typo3-extension-tinyurls/maintainability.svg)](https://qlty.sh/gh/astehlik/projects/typo3-extension-tinyurls)
[![Code Coverage](https://qlty.sh/gh/astehlik/projects/typo3-extension-tinyurls/coverage.svg)](https://qlty.sh/gh/astehlik/projects/typo3-extension-tinyurls)

This is a TYPO3 Extension for converting a normal URL to a tiny URL.

It works similar as services provided by https://bitly.com/ or https://tinyurl.com/.

Have a look in the `Documentation` folder for more information or
browse to these URLs:

- [TYPO3 Extension Repository (TER)](https://typo3.org/extensions/repository/view/tinyurls)
- [tinyurl Extension Manual](https://docs.typo3.org/typo3cms/extensions/tinyurls/)

## Migrating to core redirects

The `tinyurls:migrate-to-redirects` CLI command converts your existing tiny URL records into
`sys_redirect` records, so that you can stop relying on this extension.

Always start with a dry run to see what would happen, without changing anything:

```
vendor/bin/typo3 tinyurls:migrate-to-redirects --pid <storage-pid> --dry-run
```

Then run it for real:

```
vendor/bin/typo3 tinyurls:migrate-to-redirects --pid <storage-pid>
```

The command is safe to run multiple times — records that were already migrated are skipped.
Already-expired tiny URLs are migrated too (with the same expiry date set on the redirect via
`endtime`), so it is up to you whether to delete them afterward. One-time-use tiny URLs
(`delete_on_use`) are skipped, as redirects have no equivalent for "delete after first hit".

If speaking URLs are enabled and a usable `speakingUrlTemplate` is configured, `--url-template`
defaults to a path derived from it, so you usually don't need to set it explicitly. Otherwise
you must pass `--url-template` yourself — there is no silent guessed fallback.

For the full picture — all available options (`--host`, `--url-template`, `--target-pid`),
what exactly gets migrated or skipped, and a recommended step-by-step workflow — see the
[migration guide](Documentation/Administrator/Migration/Index.rst) in the `Documentation`
folder.
