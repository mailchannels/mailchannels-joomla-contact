# Contributing

This project is unreleased. Keep the contact-only scope and explicit routing
controls intact. Do not add retries or silently fall back to Joomla/SMTP mail.
Never put API keys or actual visitor data in issues, examples or test fixtures.

## Isolated transport checks

Requires Docker and Python 3.12 with `cryptography` installed. From the repository
root, build the test runtime and create an internal network:

```sh
docker build -f .ci/Dockerfile -t mailchannels-joomla-tests:php83 .
docker network create --internal visibility-joomla-native
python tls/run.py
python build_package.py
```

If the network already exists, verify `Internal=true` before using it. The TLS
runner checks that invariant. It generates temporary test certificates in ignored
`tls/generated/`, uses dummy values and maps the fixed API host only inside the
internal network. It never calls the real service. Require 12 PASS lines and
TLS_PROBE_COMPLETE. Remove the unused network and generated certificates when
done. Do not alter system trust stores or DNS.

## Native Joomla checks

See [native/README.md](native/README.md). The fresh-site runner installs the pinned
Joomla release on an internal Docker network and checks native package, contact
HTTP, field/CAPTCHA and administrator behavior. Do not run individual mutating
probes against an existing site. CI runs each version on a separate disposable
runner. Changes to supported versions require fresh evidence.

Submit a focused PR with behavior, validation and relevant limitations. Changes
are contributed under GPL-2.0-or-later; preserve upstream Joomla attribution.
