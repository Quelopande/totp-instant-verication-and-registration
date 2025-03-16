# TOTP instant verication and registration
It generates a QR code to access the TOTP, and on the same page, the user must enter the TOTP code so the information is registered in the database. This repository shows what an environment would look like for the user to set up the TOTP without the application having to register additional data or the same types of data repeatedly.

## Potential Vulnerabilities
* Cross-Site Scripting (XSS): They could get access to the TOTP secret
* Man-in-the-Middle (MitM): They could get access to the TOTP secret using the user WIFI (the most usual), VPN ... The user should use a secure DNS and VPN like cloudflare, but if the user isn't using any protection you should store the secret temporaly or cypher the session.
* Cross-site request forgery (CSRF): Protect it by validating the form.

## Requirement and Notes
* This repository uses a file from [PHPGangsta/GoogleAuthenticator](https://github.com/PHPGangsta/GoogleAuthenticator) repository called GoogleAuthenticator.php
* The QR are generated using https://api.qrserver.com/v1/ they don't store the QR data.
