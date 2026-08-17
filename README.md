# PHP-Resque Web UI

![PHP-Resque UI Logo](Resources/public/images/logo_large.png)

This Symfony bundle provides a web interface for [mjphaynes/php-resque](https://github.com/mjphaynes/php-resque).

Core features of the web interface are:
  - Overview of running workers (similar to bin/resque workers)
  - Overview of all queues (similar to bin/resque queues)
  - Overview of all jobs
  - View job details, JSON formatting and easy copy/paste of payload
  - Queue a job again from its details page, on the same queue and with the same payload
  - Easy installation as Symfony bundle, integration (routing, security, ...)
  - Easy style customization/branding via Bootstrap3 themes and Symfony bundle overrides

## Requirements
The PHP-Resque Web UI is designed to run as part of an existing Symfony application.
To use it without an existing app, you can [install the Symfony framework](http://symfony.com/doc/current/setup.html) and then install the bundle.

## Installation

Install the bundle using composer:

```bash
composer require andaris/resque-webui-bundle
```

Register the bundle in your application kernel:
```php
<?php
// app/AppKernel.php or src/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            new Andaris\ResqueWebUiBundle\AndarisResqueWebUiBundle(),
        ];

        // ...
    }

    // ...
}
```

Configure the routing security for the bundle:
```yaml
# app/config/security.yml or config/security.yml
    access_control:
        - { path: ^/resque, roles: YOUR_ADMIN_ROLE } # e.g. ROLE_ADMIN
```

Everyone holding that role can also queue a job again from its details page, which puts
real work back on the queue. To let a wider group look without letting it run anything,
put a rule for the retry in front of the general one; `access_control` matches in order,
so the more specific rule has to come first:

```yaml
# app/config/security.yml or config/security.yml
    access_control:
        - { path: ^/resque/job/[^/]+/retry$, methods: [POST], roles: YOUR_ADMIN_ROLE } # e.g. ROLE_ADMIN
        - { path: ^/resque, roles: YOUR_VIEWER_ROLE }                                  # e.g. ROLE_USER
```

The bundle sends no framing headers of its own. As the dashboard acts on what it shows,
serving it with `X-Frame-Options: DENY` is recommended, so that a page elsewhere cannot
put it in a frame and have someone click in it unknowingly.

Enable the routing of the bundle:
```yaml
# app/config/routing.yml or config/routing.yml
resque:
    resource: "@AndarisResqueWebUiBundle/Resources/config/routing.yml"
    prefix:   /resque/
```

## Usage
The Web UI can now be accessed via http://your-application/resque/.

Queueing a job again is confirmed in the browser and posted with a CSRF token. Where the
application has CSRF protection enabled, its own token manager is used; otherwise the bundle
falls back to one of its own, which keeps the token in the PHP session.

