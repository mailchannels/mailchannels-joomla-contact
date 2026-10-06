# Validation scope

The unchanged candidate files were tested on Joomla 5.4.9 and 6.1.4 with PHP
8.3.35 and MariaDB 11.8.9. The reviewed nine-file ZIP SHA-256 is:

`b95cd1f8c068ca121fa194a4fe74054a4e33cb6296edcb91482a48a7dbad0724`

Per release, native validation covered 20 template checks, 12 attachment/header
checks, 10 delivery/copy/failure checks, 8 installation/discovery checks,
25 HTTP-to-isolated-HTTPS checks, 15 configured-field/template-attachment checks,
8 required-field HTTP checks, 12 administrator HTTP permission/CSRF checks,
12 test-provider CAPTCHA checks and 14 package/language/lifecycle checks.

Desktop Chrome 155 administrator login/edit/help/save was checked at 1920×929,
with DB confirmation of saved contact IDs and retained disabled state. This was
DOM-driven functional acceptance, not a complete visual/accessibility review.
The deterministic CAPTCHA provider tested native ordering, not real anti-bot
strength or a production CAPTCHA service. It is not distributed here.

The public `native/run.py` harness reproduces 136 native checks per release on
fresh sites using the pinned official full packages and a disposable MariaDB
container. Local fresh-site runs passed on both releases. GitHub Actions has a
separate native matrix; check the current PR commit's results before release.
Browser acceptance remains separate from this automated harness.

The reproducible TLS suite in this repository exercises 12 real cURL scenarios:
trusted TLS, wrong hostname, expired certificate, untrusted CA, redirect,
dry-run response, failed result, malformed/empty/oversized body, wrong result
index and timeout. Only the trusted successful acceptance returns true.

All email requests in these checks went to an isolated stand-in, not MailChannels.
No evidence here proves live delivery, all Joomla/PHP versions, every custom
field/MIME feature, mobile/accessibility acceptance, or exactly-once behavior.
