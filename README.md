```
  ___               _          _ 
 |_ _|___ ___  __ _| |__   ___| |
  | |/ __/ __|/ _` | '_ \ / _ \ |
  | |\__ \__ \ (_| | |_) |  __/ |
 |___|___/___/\__,_|_.__/ \___|_|
```

Issabel is an open source distribution and GUI for Unified Communications systems forked from Elastix&copy;

It uses the [Asterisk©](http://www.asterisk.org/ "Asterisk Home Page") open source PBX software as its core.

Framework
----

Main GUI Framework for Issabel.

CSRF protection
----

Issabel has a global anti-CSRF helper: `framework/html/libs/issabelCsrf.class.php`
with the procedural wrappers in `framework/html/libs/misc.lib.php`. It uses a
single 32-byte random token per session, shared with the GUI tab manager. The
token is published in every rendered page and validated before any module is
dispatched.

**Any new form that changes state must carry the token.** There are three ways,
in order of preference:

1. Build the form with `paloForm` or `paloSantoGrid`. Both inject the hidden
   field automatically, so no template change is needed.
2. Add `{$ISSABEL_CSRF_FIELD}` right after the `<form>` tag of the template.
3. Call `issabel_csrf_field()` inside the PHP that renders the form.

For AJAX requests, `libs/js/csrf.js` reads the `issabel-csrf-token` meta tag and
adds the `X-Issabel-CSRF-Token` header to every jQuery request automatically, so
no per-module JavaScript is required. Modules that bypass jQuery must send the
same header or the hidden field.

The token is also available to templates as `{$ISSABEL_CSRF_TOKEN}`.

### Configuration

In `framework/html/configs/default.conf.php`:

* `$arrConf['csrf_mode']` — `off`, `audit` or `enforce`. Default is `audit`.
  * `audit` logs requests that change state without a valid token to
    `/var/log/issabel/audit.log` (`CSRF-AUDIT ...`) but does not block them.
  * `enforce` also answers 403 (`CSRF-REJECT ...`).
* `$arrConf['csrf_exempt_modules']` — list of module ids (the `menu` parameter)
  exempt from validation. Use it for legacy code that does not send the token
  yet; the embedded IssabelPBX (`pbxadmin`) already has its own Referer check.

### Enabling enforcement

1. Keep `csrf_mode` in `audit` and use the GUI normally.
2. Watch for missing tokens:
   `grep CSRF-AUDIT /var/log/issabel/audit.log`
3. Migrate the offending forms to the helper, or add the module to
   `csrf_exempt_modules` if it is out of scope.
4. Switch `csrf_mode` to `enforce`.

The following requests are always exempt: the login and two-factor forms, the
tab manager endpoint, the logout link and grid exports
(`exportcsv`/`exportpdf`/`exportspreadsheet`).


License
----

GPLv2 or Later

>This program is free software; you can redistribute it and/or
>modify it under the terms of the GNU General Public License
>as published by the Free Software Foundation; either version 2
>of the License, or (at your option) any later version.

>This program is distributed in the hope that it will be useful,
>but WITHOUT ANY WARRANTY; without even the implied warranty of
>MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
>GNU General Public License for more details.

>You should have received a copy of the GNU General Public License
>along with this program; if not, write to the Free Software
>Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
