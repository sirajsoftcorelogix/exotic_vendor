# SMTP setup (login OTP and password reset)

All portal OTP email goes through `helpers/mail_helper.php`, which reads **only** from:

`bootstrap/init/init.php` (this file is gitignored; use `folders/bootstrap/init/init.php` as a template)

## Required settings

```php
define('smtpHost', 'your.mail.server');
define('smtpPort', 587);
define('smtpSecure', 'tls');   // tls (port 587) | ssl (port 465) | none
define('smtpUser', 'sender@yourdomain.com');
define('smtpPass', 'your-password');
```

Optional:

```php
define('smtpFrom', smtpUser);  // if the provider requires a specific From address
```

## Common mistakes

1. **Editing the wrong file** — Login OTP does **not** use hardcoded values in `UsersController.php`. Update `bootstrap/init/init.php` on the server and redeploy/restart if needed.

2. **Port vs encryption** — Port `587` usually needs `smtpSecure` = `tls`. Port `465` usually needs `smtpSecure` = `ssl`.

3. **From address** — `setFrom` uses `smtpUser` (or `smtpFrom`). It must match an mailbox your SMTP provider allows.

4. **Username spelling** — e.g. `vendoradmin@` vs `vendor-admin@` are different accounts.

5. **Connection timeout (SMTP 110)** — The app server cannot reach `smtpHost` (firewall, wrong host, or outbound port blocked). Fixing the password does not help until the server can connect. Ask hosting to allow outbound **587** or **465**.

## Test from the server

```bash
php scripts/test_smtp.php your@email.com
```

Check PHP `error_log` after a failed login OTP for the full PHPMailer message.
