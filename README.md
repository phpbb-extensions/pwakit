# Progressive Web App Kit extension for phpBB

Progressive Web App Kit complements phpBB 4's native Web Push and web-app
manifest support. It carries forward the PWA presentation features previously
provided by the phpBB 3.3 Browser Push Notifications extension without
duplicating phpBB 4's notification delivery, subscription, or service-worker
implementation.

The extension provides:

- Per-style theme and background colours.
- Managed upload, storage, discovery, and removal of PNG app/touch icons.
- Manifest icons and Apple touch-icon links.
- Automatic discovery of existing `images/site_icons` files.

Settings are available under **General → Client communication → PWA settings**,
next to phpBB 4's native Web Push settings.

## Transitioning from phpBB 3.3

After upgrading to phpBB 4, disable Browser Push Notifications and select
**Delete data** before installing PWA Kit. Removing the old extension performs
its Web Push subscription and VAPID-key handoff to phpBB 4 core. PWA Kit will
not enable while Browser Push Notifications is still configured.

The old PWA short name and per-style colours are removed with the old
extension's data and must be configured again. Existing files in
`images/site_icons` are retained by the old extension and PWA Kit discovers
them automatically.

[![Build Status](https://github.com/phpbb-extensions/pwakit/workflows/Tests/badge.svg)](https://github.com/phpbb-extensions/pwakit/actions)
[![codecov](https://codecov.io/gh/phpbb-extensions/pwakit/graph/badge.svg?token=34V2MQSY3H)](https://codecov.io/gh/phpbb-extensions/pwakit)

## License

[GNU General Public License v2](license.txt)
