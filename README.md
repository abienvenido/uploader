# Intro

A basic Docker setup, with zero initial configuration, for a development environment using Docker, Nginx, MySQL, phpmyadmin, PHP-FPM, Xdebug.

## Quickstart

---

Place your code in the `/apps` folder, then run `docker-compose up` and your app will be available on `http://localhost`, port `80`.

#### Things to know:

- Create the mysqldb folder in `./`
- The document root of the server is in the `./apps` folder.
- Configuration files are located in the `./config` folder
- Log files will be placed in the `./logs` folder

## Nginx

---

If you need to customize the Nginx configuration (and you most probably will), make sure that you include in your configuration the `./nginx/xdebug/remote.conf` file. That sets the `remote_host` setting needed by Xdebug to be able to connect back to your IDE.

To add more hosts or edit existing ones, add/edit the files located in `./config/nginx/hosts`.

## MySQL

---

`MYSQL_DATABASE:` Database name

<pre>
environment:
	LANG: es_ES.UTF-8
	MYSQL_ROOT_PASSWORD: root
	MYSQL_DATABASE: nameproject
	MYSQL_USER: user
	MYSQL_PASSWORD: p@ssw0rd
</pre>

<code>
User: root

Password: root
</code>

## phpMyAdmin

phpMyAdmin is a tool written in PHP with the intention of managing MySQL administration through web pages, using a web browser.

<pre>
environment:
	- PMA_ARBITRARY=1
</pre>

<code>
User: admin

Password : admin
</code>

## Xdebug

---

#### PHP Version Support

This table list which version of Xdebug is still supported.

https://xdebug.org/docs/compat

#### Installation From Source

https://xdebug.org/docs/install#configure-php

#### Example in Docker:

https://matthewsetter.com/setup-step-debugging-php-xdebug3-docker/

The configuration file for Xdebug is located at `./config/php/xdebug.ini`.

<pre>
#----------------------#
# Custom Xdebug config #
#----------------------#

zend_extension=xdebug

[xdebug]
xdebug.mode=develop,debug
xdebug.client_host=host.docker.internal
xdebug.start_with_request=yes
xdebug.client_port= 9003


</pre>

By default, Xdebug is configured to use port `9003` and `autostart` is `off`. You can set it to `on` or you can use a browser extension such as [Xdebug helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) for Chrome to only enable debugging when necessary (for performance reasons).

`xdebug.remote_connect_back` is disabled and `xdebug.remote_host` is automatically set by Nginx to one of the special hostnames provided by Docker (`docker.for.win.localhost` or `docker.for.mac.localhost`) after sniffing the client's operating system. (see `./config/nginx/xdebug/remote.conf`)

To see the debug log, un-comment the `xdebug.remote_log` setting in the config file.

## Other

---

<!--  -->

Sample debug configuration for _VS Code_ (using the default settings):

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for XDebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/apps": "${workspaceFolder}/apps"
      }
    }
  ]
}
```

## Docker

---

#### Compose file version 2 reference:

There are several versions of the Compose file format – 1, 2, 2.x, and 3.x. The table below is a quick look. For full details on what each version includes and how to upgrade, see About versions and upgrading.

Link: https://docs.docker.com/compose/compose-file/compose-file-v2/
<br>
info: https://man7.org/linux/man-pages/man7/capabilities.7.html
