# Local Mail plugin for Moodle

This plugin allows users to send messages to each other, using an
interface and features similar to web-based mail clients.

Messages are tied to courses, so users can only send messages to other
participants in courses they are enrolled in.

Users can access all mail features through the envelope icon
present in the header of the site, or the main menu of the Moodle app.

## Features

- Course-based internal messaging with inbox, sent, drafts, starred, archive, and trash trays.
- Reply-by-email: users can reply directly to notification emails from their email client (e.g., Outlook), and the reply is delivered as a new SATS Mail message.
- Labels, search, attachments, and per-user read/unread/starred state.
- Responsive UI with Moodle app support.

## Installation

Unpack archive inside `/path/to/moodle/local/satsmail`

For general instructions on installing plugins see:
https://docs.moodle.org/en/Installing_plugins

## Reply-by-Email Setup

Reply-by-email allows users to reply to SATS Mail notification emails directly from their email client. Replies are processed by Moodle and delivered as new SATS Mail messages to the original sender.

### Prerequisites

1. **Enable Incoming Mail** in Site Administration > Server > Email > Incoming mail configuration.
   - Configure the IMAP mailbox, domain, and mailbox name.
   - Moodle uses plus addressing (e.g., `incoming+data@yourdomain.com`) to route replies.

2. **Enable the SATS Mail handler** in Site Administration > Server > Email > Message handlers.
   - Find "SATS Mail reply" and enable it.

3. **Apply the core patch** to `lib/classes/message/inbound/address_manager.php` for SES-compatible base64 encoding.
   - In the `process()` method, change `base64_decode($encodeddata, true)` to `base64_decode(strtr($encodeddata, '-.', '+/'), true)`.
   - This is needed to avoid `+`, `/`, and `=` characters in the email subaddress, which cause issues with email proxies like Amazon SES (see MDL-71652).

### How It Works

1. When a SATS Mail message is sent, each recipient's notification email includes a unique cryptographic reply-to address.
2. When a user replies from their email client, Moodle's cron job picks up the email via IMAP.
3. The reply handler validates the user, strips quoted text, creates a reply message, and sends notifications to recipients.
4. The replying user receives a confirmation email.

## Contributing

See: [CONTRIBUTING.md](CONTRIBUTING.md)

## Credits

Maintainers:

- Marc Català <reskit@gmail.com>
- Albert Gasset <albertgasset@fsfe.org>

Contributors:

- Daniel Barnett
- Manuel Cagigas
- Russell Smith

Version 2.0 of the project implemented by the "Recovery, Transformation and Resilience Plan". Funded by the European Union - Next Generation EU. Produced by the UNIMOODLE University Group: Universities of Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca, Illes Balears, València, Rey Juan Carlos, La Laguna, Zaragoza, Málaga, Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria and Burgos.

## Copyright

© 2012-2014 Institut Obert de Catalunya <https://ioc.gencat.cat>

© 2014-2023 Marc Català <reskit@gmail.com>

© 2016-2025 Albert Gasset <albertgasset@fsfe.org>

© 2023-2024 Proyecto UNIMOODLE <direccion.area.estrategia.digital@uva.es>

## License

This plugin is distributed under the terms of the GNU General Public License,
version 3 or later.

See the [LICENSES/GPL-3.0-or-later.txt](LICENSES/GPL-3.0-or-later.txt) file for details.

