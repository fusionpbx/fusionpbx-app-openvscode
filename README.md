# fusionpbx-app-openvscode

# Using OpenVSCode Server Editor in FusionPBX

This document is mostly my notes on how I added Open VSCode to FusionPBX. This is extremely experimental, so **do not use this in production**.

**All commands should be run as root**

### Install prerequisites

**Install programs needed**
```
apt install -y nodejs npm
```

**Set the PHP version to use**
```
export PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
```

**Install optional debugger tool**
```
apt install -y php${PHP_VERSION}-xdebug
```

### Install openvscode-server
```
cd /opt \
&& arch=$(uname -m); case "$arch" in x86_64) f=x64;; aarch64) f=arm64;; armv7l|armhf) f=armhf;; *) echo "unsupported: $arch"; exit 1;; esac; ver=1.105.1; url="https://github.com/gitpod-io/openvscode-server/releases/download/openvscode-server-v${ver}/openvscode-server-v${ver}-linux-${f}.tar.gz"; echo "️--> downloading $url"; wget -O openvscode-server.tar.gz "$url" \
&& tar -xvzf openvscode-server.tar.gz \
&& rm -f openvscode-server.tar.gz
```

**Create a home for the editor**

```
mkdir -p /var/www/.local/openvscode/{home,user-data,extensions,cache,state,config} && chown -R www-data:www-data /var/www/.local
```

**Install the app**

```
cd /var/www/fusionpbx/app
git clone https://github.com/fusionpbx/fusionpbx-app-openvscode openvscode
chown -R www-data:www-data /var/www/fusionpbx/app/openvscode
```

### Copy the systemd file for intel based OR arm based systems

**Intel x86 or AMD 64 - Copy the system service file**

```
cp /var/www/fusionpbx/app/openvscode/resources/services/openvscode.service /etc/systemd/system
systemctl daemon-reload
systemctl start openvscode
```

**OR**

**ARM 64 - Copy the system service file**

```
cp /var/www/fusionpbx/app/openvscode/resources/services/openvscode.service /etc/systemd/system
systemctl daemon-reload
systemctl start openvscode
```

**Optional: Auto start the system service at boot time**
```
systemctl enable openvscode
```

**Enable the service on boot and start it up**
```
systemctl daemon-reload && systemctl enable --now openvscode
```

**Create an Nginx proxy**
```
echo 'map $http_upgrade $connection_upgrade { default upgrade; "" close; }' > /etc/nginx/conf.d/map_connection_upgrade.conf
tee /tmp/vs_editor_block.conf >/dev/null <<'EOF'

        # auth subrequest: calls PHP to verify FusionPBX session & permission
        location = /_vscode_auth {
                internal;
                include fastcgi_params;
                # adjust the PHP-FPM socket or host:port for your system:
                fastcgi_pass unix:/run/php/php-fpm.sock;
                fastcgi_param HTTP_COOKIE $http_cookie;
                fastcgi_param SCRIPT_FILENAME /var/www/fusionpbx/app/openvscode/index.php;
        }

        # proxy the IDE under /app/editor only if auth passes
        location ^~ /app/openvscode/ { 
                auth_request /_vscode_auth;

                error_page 401 402 403 404 405 406 407 408 409 410 411 412 413 414 415 416 417 418 421 422 423 424 425 426 428 429 431 451 503 =302 /login.php;

                proxy_pass http://127.0.0.1:4070/;
                proxy_http_version 1.1;

                # WS upgrade (terminal, extensions, etc.)
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection $connection_upgrade;

                # preserve host/forwarded headers
                proxy_set_header Host $host;
                proxy_set_header X-Forwarded-Proto $scheme;
                proxy_set_header X-Forwarded-For $remote_addr;

                proxy_read_timeout 3600;
                proxy_send_timeout 3600;
        }
EOF
cp /etc/nginx/sites-enabled/fusionpbx /root/fusionpbx.nginx.bak.$(date +%F_%H%M%S)
sed -i '/^[[:space:]]*ssl_session_tickets[[:space:]]\+off;[[:space:]]*$/r /tmp/vs_editor_block.conf' /etc/nginx/sites-enabled/fusionpbx
```

**Reload Nginx**
```
nginx -t && nginx -s reload
```

**Add the needed configuration to xdebug**
```
unlink /etc/php/${PHP_VERSION}/fpm/conf.d/*-xdebug.ini \
; cat <<'EOF' > /etc/php/${PHP_VERSION}/fpm/conf.d/99-xdebug.ini
zend_extension=xdebug.so
xdebug.mode=debug,develop,profile
;
; By using 'trigger' instead of 'yes' like most examples you will find,
; we are not allowing xdebug to work in debug or "step" mode debugging.
; Instead, the 'trigger' will allow us to use an XDEBUG_SESSION cookie,
; url param, or environment variable. This also allows debug to profile
; or to step debug without changes to the ini file. Instead, we can use
; the xdebug browser extension to send the cookie or not, even when the
; debugging process has connected.
;
xdebug.start_with_request=trigger

;
; Setting a trigger value is setting a 'shared secret'. When setting this value, ensure that the xdebug browser extension uses the same value.
; Using the value is more secure but you can't switch between debug and profile without restarting php-fpm
; Also, by default, xdebug will only try connecting to localhost port 9003. This means that a remote user can't connect to it.
;
;xdebug.trigger_value=some_secret_key

;
; Profiling
;
xdebug.profile=profile
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%R.%u

;;;
; Logging xdebug actions
;;;
;;;xdebug.log=/tmp/xdebug/xdebug.log

;
; Debug callback URLs
;
; Use this value instead of the 'trigger' value above when you are trying to
; step debug a call from an external source. This value will always start
; the debugger and connect to the IDE. You can use this if you are not using
; the browser extension but still want to connect. It is also helpful if an
; external source is causing the connection such as a URL Callback. A good
; example of this is if you need to debug SMS files that trigger from remote
; or automated systems calling in to the index.php file.
;
;xdebug.start_with_request=yes
EOF
```

**Restart php-fpm**
```
systemctl restart php${PHP_VERSION}-fpm
```

**Update permissions and load the menu item**
```
chown -R www-data:www-data /var/www/fusionpbx/app/openvscode
php /var/www/fusionpbx/core/upgrade/upgrade.php -g
php /var/www/fusionpbx/core/upgrade/upgrade.php -m
```

### Modify Paste (Optional)

I dislike the paste option box that always comes up when I use `CTRL+V`, as it really slows down a copy/paste and is not uniform to every other application I have ever used. This took a lot of breaking things before I figured out how to turn this off, so I am documenting it for future reference and convenience.

- Open the settings dialog: `CTRL+,`
- Search for `paste`
- Uncheck the box for `Editor › Paste As: Enabled`

### Add Extensions (Optional)

I would recommend adding the xdebug extension as it will allow you to debug the code on the web server live. Look for the PHP Debug extension by xdebug.

Recommend the following:
- PHP DocBlocker by neilbrayfield
- PHP Formatter - pretty-php by lkrms
	- Goto Extension Settings: "Pretty-PHP: Formatter Arguments" and Add Item `--one-true-brace-style`
- PHP Debug by xdebug
- PHP Intelephense by bmewburn (https://intelephense.com/ $35 one-time fee for premium features)
- PHP String Syntax by ericgomez
- Trailing Spaces by shardulm94
- Smarty by imperez for smarty template highlighting
- Breakpoint Highlight by ericgomez

Other extensions I have tried:
- PHP All-in-One PHP support by devsense (Installs xdebug, Code Lense, Auto creates PHPDoc Blocks, yearly cost)
- PHP IntelliSense
- PHP Profiler by devsense

### Block Select
- Press ALT+SHIFT for the block select instead of just ALT
	- On some keyboards, if ALT doesn't work, try OPT+Shift

### Composer

Composer may be required for some extensions to work with PHP code. To install composer, [follow their instructions](https://getcomposer.org/download/) from their website because we are prohibited from having them here. It is likely you will need to composer in the /usr/local/bin/ path so make sure you run the command on their website, which should be `mv composer.phar /usr/local/bin/composer` but, double-check against their website as instructions can (and have) changed. Moving the file to a globally available execution path such as /usr/local/bin will install the extension globally to keep it out of the project and still allow the extensions to work.