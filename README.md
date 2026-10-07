# MailChannels Contact Email API — unreleased candidate

This Joomla contact plugin sends selected core `com_contact` forms through the
MailChannels Email API. It preserves the native primary and optional sender-copy
templates. It is a form-mail integration; it does
not replace Joomla's global mailer or handle registration/password-reset mail.
Support: dev@mailchannels.com. License: GPL-2.0-or-later.

The candidate has been tested on Joomla 5.4.9 and 6.1.4 with PHP 8.3.35 and
MariaDB 11.8.9. It is not production-approved or listed in JED. Broader version
compatibility is unverified.

## Isolated installation and configuration

1. Build the review ZIP with `python build_package.py` from
   this repository root. The ZIP and file-hash manifest are in
   `dist`. Install only on an isolated review site.
2. Set server environment variables `MAILCHANNELS_API_KEY` and comma-separated
   `MAILCHANNELS_JOOMLA_ALLOWED_SENDERS`. Do not place credentials in plugin
   parameters, browser code, logs or exported Joomla configuration.
3. If templates use local attachments, set `MAILCHANNELS_JOOMLA_ATTACHMENT_ROOTS`
   to a JSON array of approved absolute directories. Default: no filesystem roots.
   Keep these directories administrator-controlled; concurrent file replacement
   after path validation is not covered.
4. For each selected contact, explicitly enable its Custom Reply setting to
   suppress core mail. The plugin refuses delivery if this is missing. Enter the
   comma-separated IDs in the plugin's Enabled contact IDs setting, then enable
   the plugin. Installation initially leaves it disabled; empty IDs select none.
5. Review the site's From address against the allowlist, both contact templates,
   sender-copy preference, custom fields, template attachment folder and CAPTCHA.
   Separately authorize any live-provider test; local fixtures use dummy keys.

Disabling/uninstalling the plugin does not restore core mail routing. Review each
contact's Custom Reply setting deliberately afterward. Same-version reinstall
preserves plugin parameters and enabled state; cross-version migrations are not
validated.

## Delivery behavior and compatibility

Both selected templates are rendered and validated before the first request.
Contact custom fields follow Joomla rendering; template bodies control whether
CUSTOMFIELDS appear in the primary/copy. To/Cc/Bcc stay separate, the configured
sender is allowlisted, and the visitor address is used as Reply-To.

Supported mapping includes UTF-8 plain/HTML alternatives, binary/string files,
approved local files, unique inline content IDs and permitted custom headers.
Reserved/duplicate headers, invalid paths, symlink escapes and path-bearing
filenames fail explicitly. Limits are 15MB aggregate decoded attachments and
20MB encoded JSON. Bcc-only messages, multiple Reply-To, non-UTF-8 content,
calendar/receipt requests, explicit Message-ID, DKIM-domain settings and alternate
envelopes remain unsupported; unsupported features must not be silently dropped.

The client makes one request per message to the fixed HTTPS send endpoint with
TLS peer/hostname verification, no redirects, a five-second connection timeout,
15-second total timeout and 64KiB response cap. Only HTTP202 with one index0
`sent` result confirms API acceptance, not inbox delivery. Failures and uncertain
responses do not trigger retry or SMTP/PHP fallback. A primary failure stops the
copy; a copy failure can occur after the primary was accepted.

There is no queue, automatic retry or durable receipt store. Repeating a user
submission starts a new attempt and may duplicate an already accepted message.
Do not automatically resend after uncertainty. Queueing, retry or exactly-once
claims would require additional durable operation tracking and reconciliation.

## Validation and release status

See [VALIDATION.md](VALIDATION.md) for the tested scope and [RELEASING.md](RELEASING.md)
for open release gates. Run the isolated TLS suite with the instructions in
[CONTRIBUTING.md](CONTRIBUTING.md). Local test success does not establish live
provider acceptance, production suitability or a JED listing.

For CAPTCHA, enable the chosen production provider and select it in Contact
component options. Verify its challenge is rendered and that a missing/failed
answer prevents delivery on the deployment's actual form. A per-contact value
alone did not configure the native CAPTCHA field in our fixtures. The deterministic test
provider used during native validation is not an anti-bot solution and is never
included in this candidate's ZIP.

## Service dependency and data handling

A MailChannels Email API account, authorized sender and API key are required.
The plugin code is GPL and has no separate license fee; the hosted email service
has its own terms and pricing. Review the current service plan before deployment.
Submitted contact details, message content, recipients and configured attachments
are sent to MailChannels for processing. Provide appropriate notice to site users.
Do not describe API acceptance as recipient delivery.

- [Email API documentation](https://docs.mailchannels.net/email-api/)
- Support and maintenance: dev@mailchannels.com
- [Contributing](CONTRIBUTING.md) · [Security reporting](SECURITY.md)
