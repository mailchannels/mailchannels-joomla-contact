# Validation scope

Version 1.0.0, built by `python build_package.py`, is a nine-file ZIP with SHA-256:

`13b733bd00a98f2cb8baa8f928ea0355beb4ba373030e129f2bf59bff09e1734`

On 2026-11-03 this ZIP was installed on a fresh Joomla 6.1.4 site. One contact
form submission was accepted by the live MailChannels Email API and delivered to
a MailChannels inbox. With an invalid API key, the same form showed the visitor
warning and logged HTTP 401 without the key or addresses. JED Checker 2.4.4 reports
no errors; the base64 attachment encoding needs a JED editor's manual review.

The JED Checker fixes rebuilt the ZIP as
`ba2dd7780d78b7e8aa8bdf19d7d56445d83ab1a9dea9724c17fed5d0b01d6d95`.

Both ZIPs are also covered by the automated `Candidate checks` workflow (TLS
suite, package build, PHP syntax and the `native/run.py` matrix). No manual
browser review has covered them.

The evidence below covers the earlier nine-file ZIP built at commit `7659809`,
SHA-256 `601c87c198496068696c25d9f14568f4892c9e5aa29e079de27f2f9016f8bbe9`.
It was tested on Joomla 5.4.9 and 6.1.4 with PHP 8.3.35 and MariaDB 11.8.9.

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

The public `native/run.py` harness reproduces 133 native checks per release on
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

Chrome screenshots inspected on both versions at 390px, taken while the display
name was MailChannels Contact, show document width 375px (previously 651px). The
current display name is Contact - MailChannels; its 390px layout check is
pending. The unreleased description, Custom Reply help and Disabled status
remained visible.
The native header still truncates under its gear control; this is not full
mobile or accessibility certification. No provider request was made.
