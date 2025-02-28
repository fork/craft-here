<div align="left">
  <img width="600" title="Craft Here" src="/resources/img/plugin-logo.png">
</div>

**Table of contents**

- [Features](#features)
- [Requirements](#requirements)
- [Setup](#setup)
- [Usage](#usage)
- [Roadmap](#roadmap)

<!-- /TOC -->

---

## Features

- Manage custom redirects directly within the server configuration (for Nginx and Apache)
- Perfectly suited for headless Craft CMS Setups
- Use the redirects features and UI of the [Craft SEO Plugin](https://github.com/ethercreative/seo)

## Requirements

- Craft CMS 5
- [Craft SEO Plugin](https://github.com/ethercreative/seo)

## Setup

**1. Install**

Install the package

```sh
cd /path/to/project
composer require fork/craft-here
```

**2. Configuration file**

- Copy the example `config.php` to your Craft config directory and rename it to `redirects.php`
- Specify the server type (and a reload command if you use nginx). Here's an example:

```php
<?php

return [
    // Global settings
    '*' => [
        'serverType' => 'nginx' // or 'apache'
    ],

    // Dev environment settings
    'dev' => [
        //'redirectsReloadCommand' => 'my-command',
    ],

    // Staging environment settings
    'staging' => [
    ],

    // Production environment settings
    'production' => [
        //'redirectsReloadCommand' => 'sudo /etc/init.d/nginx reload',
    ],
];
```

In your server configuration include the redirect map files (which will be created after plugin has been installed):

```nginx
# NGINX EXAMPLE:

# see https://serverfault.com/a/890715/487169 for why we use "[.]" instead of a regular period "."
include /var/www/html/redirects/my.domain.com/redirects-301[.]map;
include /var/www/html/redirects/my.domain.com/redirects-302[.]map;

# 301 MOVED PERMANENTLY
if ($redirect_moved = false) {
    set $redirect_moved "";
}
if ($redirect_moved != "") {
    rewrite ^(.*)$ $redirect_moved permanent;
}
# 302 FOUND (aka MOVED TEMPORARILY)
if ($redirect_found = false) {
    set $redirect_found "";
}
if ($redirect_found != "") {
    rewrite ^(.*)$ $redirect_found redirect;
}
```

```apacheconf
# APACHE EXAMPLE:

RewriteEngine On
RewriteMap redirects-301 txt:/var/www/html/redirects/my.domain.com/redirects-301.map
RewriteMap redirects-302 txt:/var/www/html/redirects/my.domain.com/redirects-302.map

RewriteCond ${redirects-301:%{REQUEST_URI}} ^.+$
RewriteRule .* https://${redirects-301:%{HTTP_HOST}%{REQUEST_URI}} [redirect=permanent,last]

RewriteCond ${redirects-302:%{REQUEST_URI}} ^.+$
RewriteRule .* https://${redirects-302:%{HTTP_HOST}%{REQUEST_URI}} [redirect=temp,last]
```

## Usage

Once the plugin has been installed it will create all necessary redirect map files which need to be included into the server config.
After that just use the SEO Plugin UI to manage your redirects.

## Roadmap

- [ ] Settings maybe (instead of config file)

---

<div align="center">
  <img src="/resources/img/heart.png" width="38" height="41" alt="Fork Logo" />

  <p>Brought to you by <a href="https://www.fork.de">Fork Unstable Media GmbH</a></p>
</div>
