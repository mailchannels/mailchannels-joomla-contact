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
host trust/DNS changes. Cleanup removes only its own named containers/network.

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
