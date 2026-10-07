# Disposable native Joomla acceptance

Requires Docker, Python3.12+ and Python cryptography. Build both PHP images:

```sh
docker build -f .ci/Dockerfile -t mailchannels-joomla-tests:php83 .
docker build -f .ci/Native.Dockerfile -t mailchannels-joomla-native:php83 .
python native/run.py 5.4.9
python native/run.py 6.1.4
```

Run versions sequentially on one host; the CI matrix uses separate runners.
The harness downloads official full packages and verifies pinned SHA256 before
extraction. `--archive /path/to/package.tar.gz` reuses a local archive but still
checks its hash. Downloads, fresh installed sites and logs use ignored
`.native-work/`; it removes each installed site after its run. It creates a unique
internal Docker network and fresh MariaDB container, with no published ports or
host trust/DNS changes. Cleanup removes only its own named containers/network. A network-disabled Python
container clears the disposable site mount so root-owned Docker cache files can
be removed on rootful runners; it does not change host ownership or use sudo.

Dummy fixture credentials are intentionally in the scripts and not production
secrets. Only the isolated API stand-in receives requests. PHP mail is disabled;
no real API key is provided. Generated certificates are ignored under tls/generated.
Never install the deterministic CAPTCHA fixture on a real site or ship it in a ZIP.

Require `JOOMLA_NATIVE_COMPLETE VERSION 136 native checks + 12 TLS checks`.
The runner verifies individual completion sentinels and PASS counts because
Joomla's exception handler can sometimes exit zero after an error. It verifies
fresh installation, templates/attachments, plugin package/discovery/lifecycle,
real HTTP primary/copy/failure/routing, custom fields, native CAPTCHA ordering
and administrator HTTP access/CSRF/save. Native cleanup probes check records,
files and restored configuration before the disposable site is removed.

The runner does not automate Chrome, a real CAPTCHA service, live MailChannels
validation, mobile/accessibility acceptance or every runtime/feature variant.
Known native FieldTable null/explode deprecations during custom-field deletion
are distinct from the asserted successful deletion/absence checks.

## Administrator browser review

Run `python native/run.py 6.1.4 --review-port 18191` (or5.4.9).
After the native checks pass, the runner prints the administrator URL, synthetic
login and plugin-editor URL. It keeps the plugin disabled, supplies no API key,
disables PHP mail and uses the same internal Docker network. A loopback-only
PHP/docker-exec relay exposes the admin UI without a published container port.
Do not enable the plugin or enter real credentials in this disposable site.

The HTTP checks leave contact IDs42,77 selected with status Disabled. Review the
localized description, toggle inline help, edit the selection and save. Keep the
plugin disabled. Ctrl-C closes the relay and then runs administrator cleanup,
including configuration restoration and synthetic record/plugin removal, before
removing the disposable site/database/network. Require the final cleanup marker.

Actual Chrome review on5.4.9/6.1.4 confirmed localized editor text, inline Custom
Reply help, contact-ID edit/save and disabled status in the database. Desktop1280
screenshots were inspected. At390px, form fields/help wrap but the long plugin
page title makes the document651px wide; narrow-screen acceptance remains open.
This does not cover browser contact submission, custom ACL/session flows,
keyboard-only or screen-reader acceptance. No provider call occurs in this mode.
