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

Page alerts
----

`framework/html/libs/issabelAlert.class.php` renders the theme alert family with
the right modifier (`iss-alert--error|success|warning|info`). Use it for
page-level feedback instead of `mb_title`/`mb_message`: the iframe shell paints
`mb_message` as an error, so success notices would look like failures.

```php
$smarty->assign('SECURITY_ALERT',
    issabel_alert_html('success', _tr('Message'), _tr('Saved')));
```

```html
{if $SECURITY_ALERT}{$SECURITY_ALERT}{/if}
```

The message is escaped by default. Pass `array('html' => true)` only for HTML
built by the module, and escape every interpolated value yourself.

Audit log
----

`/var/log/issabel/audit.log` records who did what from where. One line per
event, sanitised (no newlines, values truncated) because the `issabel-gui`
fail2ban jail reads the same file.

* `LOGIN` / `LOGOUT` — web interface sessions, including failures.
* `NAVIGATION` — module visits.
* `CSRF-AUDIT` / `CSRF-REJECT` — state-changing requests without a valid token.
* `ACL` — changes to users, groups and permissions.
* `FIREWALL` — firewall rules (see the `security` module).
* `SHUTDOWN`, `HEALTH`, `UPDATES`, `ASTCLI`, `PBX` — other privileged actions.

`ACL` lines are written by `framework/html/libs/issabelAclAudit.class.php` and
look like this:

```
ACL admin: user.create login=jdoe id=7 group=operator result=ok from 10.0.0.5.
ACL admin: user.update login=jdoe id=7 fields=description,password result=ok from 10.0.0.5.
ACL admin: group.delete id=4 group=operators result=ok from 10.0.0.5.
ACL admin: permissions.apply group=operator added=pbx,reports removed=system result=ok from 10.0.0.5.
ACL admin: privileges.apply group=operator resource=userlist granted=create revoked=viewany result=ok from 10.0.0.5.
```

Events are `user.create|update|delete`, `group.create|update|delete`,
`permissions.apply` and `privileges.apply`; `result=error` carries a short
`msg`, and denied attempts are recorded as errors. Passwords are never logged,
not even their hash: `user.update` only lists `password` inside `fields`.

Client-side validation
----

`framework/html/libs/js/issabel-validate.js` toggles `is-valid`/`is-invalid` on
`input`/`blur` and blocks `submit` when a field does not match. The server must
still revalidate.

```html
<script src="{$WEBPATH}libs/js/issabel-validate.js"></script>
<script>
{literal}
IssabelValidate.attach(document.querySelector('form'), {
    port: function (v) { return /^[0-9]{1,5}$/.test(v) && parseInt(v, 10) <= 65535; }
});
{/literal}
</script>
```

The button whose `name` starts with `cancel` is exempt.

Charts
----

Chart.js is vendored once, in the theme, and modules must use that copy: never
from a CDN and never from a copy inside the module.

In `framework/html/themes/modern/js/`:

* `chart.umd.min.js` — Chart.js 4.5.1 (MIT)
* `chartjs-plugin-datalabels.min.js` — data labels plugin 2.2.0 (MIT)
* `chartjs-adapter-moment.min.js` — date adapter, only for time scales (MIT)
* `moment.min.js` — Moment.js, only for time scales (MIT)
* `issabel-charts.js` — theme wrapper: applies the light/dark defaults and
  exposes `IssabelCharts.colors()` and `IssabelCharts.palette(n)`

Load them from the template of the view that draws the chart, not in the whole
panel:

```html
<script src="{$WEBPATH}themes/{$THEMENAME}/js/chart.umd.min.js"></script>
<script src="{$WEBPATH}themes/{$THEMENAME}/js/issabel-charts.js"></script>
```

The chart container needs a position and a height of its own, otherwise the
canvas collapses or overflows the page. The theme provides the primitive:

```html
<div class="iss-chart"><canvas id="myChart"></canvas></div>
```

`iss-chart--tall` and `iss-chart--short` change the height. The categorical
colours come from the theme tokens through `IssabelCharts.palette(n)`, so a
module never writes a colour of its own.


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
