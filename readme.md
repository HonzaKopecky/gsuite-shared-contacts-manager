Google Shared Contacts Manager
=================

This is a [Nette Framework](https://nette.org/en/) based application that enables G Suite administrators to manage 
shared contacts in a G Suite domain. App can be used to manage external contacts that will be shared with the whole
G Suite domain.

App uses [Domain Shared Contacts API](https://developers.google.com/admin-sdk/domain-shared-contacts/) to communicate
with Google's servers. The communication is based on XML requests (ugly!) which is the only way this API works.

Requirements
------------
PHP 5.6 or higher. App does not use any database connection. Contacts data are cached on the server or fetched directly 
from the API.

Installation
------------
To install the app on your server follow the following steps:

- Download the app to your server.
- Edit `RewriteBase` in the root `.htaccess` file. It should contain everything that is part of the path except your 
domain. Example: root of the app is available at `http://example.com/contact-manager`  the directive is then 
`/contact-manager/`.
- Make directories `temp/` and `log/` writable.
- Run `composer install` to install all dependencies.

**It is CRITICAL that whole `app/`, `log/` and `temp/` directories are not accessible directly
via a web browser. See [security warning](https://nette.org/security-warning).**

Usage
------------
1. Log in with your Google account. **Note that the account has to be Superadmin of the G Suite domain.**
2. Go to **Contacts** screen.
3. Create new contacts using the button in the bottom right corner or edit the existing contacts.