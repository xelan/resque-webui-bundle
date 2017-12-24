# PHP-Resque Web UI

![PHP-Resque UI Logo](Resources/public/images/logo_large.png)

This Symfony bundle provides a web interface for [mjphaynes/php-resque](https://github.com/mjphaynes/php-resque).

Core features of the web interface are:
  - Overview of running workers (similar to bin/resque workers)
  - Overview of all queues (similar to bin/resque queues)
  - Overview of all jobs
  - View job details, JSON formatting and easy copy/paste of payload
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

Enable the routing of the bundle:
```yaml
# app/config/routing.yml or config/routing.yml
resque:
    resource: "@AndarisResqueWebUiBundle/Resources/config/routing.yml"
    prefix:   /resque/
```

## Usage
The Web UI can now be accessed via http://your-application/resque/.

