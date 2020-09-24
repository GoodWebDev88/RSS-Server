![Docker Cloud Automated build](https://img.shields.io/docker/cloud/automated/rssserver/rssserver.svg)
![Docker Cloud Build Status](https://img.shields.io/docker/cloud/build/rssserver/rssserver.svg)
![MicroBadger Size](https://img.shields.io/microbadger/image-size/rssserver/rssserver.svg)
![Docker Pulls](https://img.shields.io/docker/pulls/rssserver/rssserver.svg)

# Deploy RSSServer with Docker
* See also https://hub.docker.com/r/rssserver/rssserver/


## Install Docker

```sh
curl -fsSL https://get.docker.com/ -o get-docker.sh
sh get-docker.sh
```


## Create an isolated network
```sh
docker network create rssserver-network
```

## Recommended: use [Træfik](https://traefik.io/) reverse proxy
It is a good idea to use a reverse proxy on your host server, providing HTTPS.
Here is the recommended configuration using automatic [Let’s Encrypt](https://letsencrypt.org/) HTTPS certificates and with a redirection from HTTP to HTTPS. See further below for alternatives.

```sh
docker volume create traefik-letsencrypt
docker volume create traefik-tmp

# Just change your e-mail address in the command below:
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v traefik-letsencrypt:/etc/traefik/acme \
  -v traefik-tmp:/tmp \
  -v /var/run/docker.sock:/var/run/docker.sock:ro \
  --net rssserver-network \
  -p 80:80 \
  -p 443:443 \
  --name traefik traefik:1.7 --docker \
  --loglevel=info \
  --entryPoints='Name:http Address::80 Compress:true Redirect.EntryPoint:https' \
  --entryPoints='Name:https Address::443 Compress:true TLS TLS.MinVersion:VersionTLS12 TLS.SniStrict:true TLS.CipherSuites:TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256,TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384,TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA' \
  --defaultentrypoints=http,https --keeptrailingslash=true \
  --acme=true --acme.entrypoint=https --acme.onhostrule=true --acme.tlsChallenge \
  --acme.storage=/etc/traefik/acme/acme.json --acme.email=you@example.net
```

See [more information about Docker and Let’s Encrypt in Træfik](https://docs.traefik.io/user-guide/docker-and-lets-encrypt/).


## Run RSSServer
Example using the built-in refresh cron job (see further below for alternatives).
You must first chose a domain (DNS) or sub-domain, e.g. `rssserver.example.net`.

> **N.B.:** Default images are for x64 (Intel, AMD) platforms. For ARM (e.g. Raspberry Pi), use the `*-arm` tags. For other platforms, see the section *Build Docker image* further below.

```sh
docker volume create rssserver-data
docker volume create rssserver-extensions

# Remember to replace rssserver.example.net by your server address in the command below:
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v rssserver-data:/var/www/RSSServer/data \
  -v rssserver-extensions:/var/www/RSSServer/extensions \
  -e 'CRON_MIN=4,34' \
  -e TZ=Europe/Paris \
  --net rssserver-network \
  --label traefik.port=80 \
  --label traefik.frontend.rule='Host:rssserver.example.net' \
  --label traefik.frontend.headers.forceSTSHeader=true \
  --label traefik.frontend.headers.STSSeconds=31536000 \
  --name rssserver rssserver/rssserver
```

* Replace `TZ=Europe/Paris` by your [server timezone](http://php.net/timezones), or remove the line to use `UTC`.
* If you cannot have RSSServer at the root of a dedicated domain, update the command above according to the following model:
	`--label traefik.frontend.rule='Host:rssserver.example.net;PathPrefixStrip:/RSSServer/' \`
* You may remove the `--label traefik.*` lines if you do not use Træfik.
* Add `-p 8080:80 \` if you want to expose RSSServer locally, e.g. on port `8080`.
* Replace `rssserver/rssserver` by a more specific tag (see below) such as `rssserver/rssserver:dev` for the development version, or `rssserver/rssserver:arm` for a Raspberry Pi version.

This already works with a built-in **SQLite** database (easiest), but more powerful databases are supported:

### [MySQL](https://hub.docker.com/_/mysql/) or [MariaDB](https://hub.docker.com/_/mariadb)
```sh
# If you already have a MySQL or MariaDB instance running, just attach it to the RSSServer network:
docker network connect rssserver-network mysql

# Otherwise, start a new MySQL instance, remembering to change the passwords:
docker volume create mysql-data
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v mysql-data:/var/lib/mysql \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=rssserver \
  -e MYSQL_USER=rssserver \
  -e MYSQL_PASSWORD=pass \
  --net rssserver-network \
  --name mysql mysql
```

### [PostgreSQL](https://hub.docker.com/_/postgres/)
```sh
# If you already have a PostgreSQL instance running, just attach it to the RSSServer network:
docker network connect rssserver-network postgres

# Otherwise, start a new PostgreSQL instance, remembering to change the passwords:
docker volume create pgsql-data
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v pgsql-data:/var/lib/postgresql/data \
  -e POSTGRES_DB=rssserver \
  -e POSTGRES_USER=rssserver \
  -e POSTGRES_PASSWORD=pass \
  --net rssserver-network \
  --name postgres postgres
```

### Complete installation
Browse to your server https://rssserver.example.net/ to complete the installation via the RSSServer Web interface,
or use the command line described below.


## How to update

```sh
# Rebuild an image (see build section above) or get a new online version:
docker pull rssserver/rssserver
# And then
docker stop rssserver
docker rename rssserver rssserver_old
# See the run section above for the full command
docker run ... --name rssserver rssserver/rssserver
# If everything is working, delete the old container
docker rm rssserver_old
```


## [Docker tags](https://hub.docker.com/r/rssserver/rssserver/tags)
The tags correspond to RSSServer branches and versions:
* `:latest` (default) is the `master` branch, more stable
* `:dev` is the `dev` branch, rolling release
* `:x.y.z` are specific RSSServer releases
* `:arm` or `:*-arm` are the ARM versions (e.g. for Raspberry Pi)

### Linux: Ubuntu vs. Alpine
Our default image is based on [Ubuntu](https://www.ubuntu.com/server). We offer an alternative based on [Alpine](https://alpinelinux.org/) (with the `*-alpine` tag suffix).
In [our tests], Ubuntu is ~3 times faster,
while Alpine is ~2.5 times [smaller on disk](https://hub.docker.com/r/rssserver/rssserver/tags) (and much faster to build).


## Optional: Build Docker image of RSSServer
Building your own Docker image is optional because online images can be fetched automatically.
Note that prebuilt images are less recent and only available for x64 (Intel, AMD) platforms.

```sh
# First time only
git clone https://github.com/RSSServer/RSSServer.git

cd RSSServer/
git pull
docker build --pull --tag rssserver/rssserver -f Docker/Dockerfile .
```


## Command line

```sh
docker exec --user www-data -it rssserver php ./cli/list-users.php
```

See the [CLI documentation](../cli/) for all the other commands.
You might have to replace `--user www-data` by `--user apache` when using our images based on Linux Alpine.


## Debugging

```sh
# See RSSServer data if you use Docker volume
docker volume inspect rssserver-data
sudo ls /var/lib/docker/volumes/rssserver-data/_data/

# See Web server logs
docker logs -f rssserver

# Enter inside RSSServer docker container
docker exec -it rssserver sh
## See RSSServer root inside the container
ls /var/www/RSSServer/
```


## Cron job to automatically refresh feeds
We recommend a refresh rate of about twice per hour (see *WebSub* / *PubSubHubbub* for real-time updates).
There are no less than 3 options. Pick a single one.

### Option 1) Cron inside the RSSServer Docker image
Easiest, built-in solution, also used already in the examples above
(but your Docker instance will have a second process in the background, without monitoring).
Just pass the environment variable `CRON_MIN` to your `docker run` command,
containing a valid cron minute definition such as `'13,43'` (recommended) or `'*/20'`.
Not passing the `CRON_MIN` environment variable – or setting it to empty string – will disable the cron daemon.

```sh
docker run ... \
  -e 'CRON_MIN=13,43' \
  --name rssserver rssserver/rssserver
```

### Option 2) Cron on the host machine
Traditional solution.
Set a cron job up on your host machine, calling the `actualize_script.php` inside the RSSServer Docker instance.
Remember not pass the `CRON_MIN` environment variable to your Docker run, to avoid running the built-in cron daemon of option 1.

Example on Debian / Ubuntu: Create `/etc/cron.d/RSSServer` with:

```
7,37 * * * * root docker exec --user www-data rssserver php ./app/actualize_script.php > /tmp/RSSServer.log 2>&1
```

### Option 3) Cron as another instance of the same RSSServer Docker image
For advanced users. Offers good logging and monitoring with auto-restart on failure.
Watch out to use the same run parameters than in your main RSSServer instance, for database, networking, and file system.
See cron option 1 for customising the cron schedule.

#### For the Ubuntu image (default)
```sh
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v rssserver-data:/var/www/RSSServer/data \
  -v rssserver-extensions:/var/www/RSSServer/extensions \
  -e 'CRON_MIN=17,47' \
  --net rssserver-network \
  --name rssserver_cron rssserver/rssserver \
  cron
```

#### For the Alpine image
```sh
docker run -d --restart unless-stopped --log-opt max-size=10m \
  -v rssserver-data:/var/www/RSSServer/data \
  -v rssserver-extensions:/var/www/RSSServer/extensions \
  -e 'CRON_MIN=27,57' \
  --net rssserver-network \
  --name rssserver_cron rssserver/rssserver:alpine \
  crond -f -d 6
```

## Development mode

To contribute to RSSServer development, you can use one of the Docker images to run and serve the PHP code,
while reading the source code from your local (git) directory, like the following example:

```sh
cd /path-to-local/RSSServer/
docker run --rm -p 8080:80 -e TZ=Europe/Paris -e RSSSERVER_ENV=development \
  -v $(pwd):/var/www/RSSServer \
  rssserver/rssserver:dev
```

This will start a server on port 8080, based on your local PHP code, which will show the logs directly in your terminal.
Press <kbd>Control</kbd>+<kbd>c</kbd> to exit.

The `RSSSERVER_ENV=development` environment variable increases the level of logging and ensures that errors are displayed.

## More deployment options

### Custom Apache configuration (advanced users)

Changes in Apache `.htaccess` files are applied when restarting the container.
In particular, if you want RSSServer to use HTTP-based login (instead of the easier Web form login), you can mount your own `./RSSServer/public/i/.htaccess`:

```
docker run ...
  -v /your/.htaccess:/var/www/RSSServer/public/i/.htaccess \
  -v /your/.htpasswd:/var/www/RSSServer/data/.htpasswd \
  ...
  --name rssserver rssserver/rssserver
```

Example of `/your/.htaccess` referring to `/your/.htpasswd`:
```
AuthUserFile /var/www/RSSServer/data/.htpasswd
AuthName "RSSServer"
AuthType Basic
Require valid-user
```

### Example with [docker-compose](https://docs.docker.com/compose/)

A [docker-compose.yml](docker-compose.yml) file is given as an example, using PostgreSQL. In order to use it, you have to adapt:
- In the `postgresql` service:
    * `container_name` directive. Whatever you set this to will be the value you put in the "Host" field during the "Database Configuration" step of installation;
	* the `volumes` section. Be careful to keep the path `/var/lib/postgresql/data` for the container. If the path is wrong, you will not get any error but your db will be gone at the next run;
	* the `POSTGRES_PASSWORD` in the `environment` section;
- In the `rssserver` service:
	* the `volumes` section;
	* options under the `labels` section are specific to [Træfik](https://traefik.io/), a reverse proxy. If you are not using it, feel free to delete this section. If you are using it, adapt accordingly to your config, especially the `traefik.frontend.rule` option.
	* the `environment` section to adapt the strategy to update feeds.

You can then launch the stack (RSSServer + PostgreSQL) with:
```sh
docker-compose up -d
```

### Alternative reverse proxy using [nginx](https://docs.nginx.com/nginx/admin-guide/web-server/reverse-proxy/)

Here is an example of configuration to run RSSServer behind an Nginx reverse proxy (as subdirectory).
In particular, the proxy should be setup to allow cookies via HTTP headers (see `proxy_cookie_path` below) to allow logging in via the Web form method.

```
upstream rssserver {
	server 127.0.0.1:8080;
	keepalive 64;
}

server {
	listen 80;

	location / {
		return 301 https://$host$request_uri;
	}
}

server {
	server_name mywebsite.example.net;
	listen 443 ssl http2;

	# Other SSL stuff goes here

	# Needed for rssServer cookie/session :
	proxy_cookie_path / "/; HTTPOnly; Secure; SameSite=Lax";

	location / {
		try_files $uri $uri/ =404;
		index index.htm index.html;
	}

	location /rssserver/ {
		proxy_pass http://rssserver;
		add_header X-Frame-Options SAMEORIGIN;
		add_header X-XSS-Protection "1; mode=block";
		proxy_redirect off;
		proxy_buffering off;
		proxy_set_header Host $host;
		proxy_set_header X-Real-IP $remote_addr;
		proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
		proxy_set_header X-Forwarded-Proto $scheme;
		proxy_set_header X-Forwarded-Port $server_port;
		proxy_read_timeout 90;

		# Forward the Authorization header for the Google Reader API.
		proxy_set_header Authorization $http_authorization;
		proxy_pass_header Authorization;
	}
}
```
### Alternative reverse proxy using [Apache 2.4](https://httpd.apache.org/docs/2.4/howto/reverse_proxy.html)

Here is an example of a configuration file for running RSSServer behind an Apache reverse proxy (as a subdirectory).
You need a working SSL configuration and the Apache modules `proxy`, `proxy_http` and `headers` installed (depends on your distribution) and enabled (```a2enmod proxy proxy_http headers```).

```
ProxyPreserveHost On

<Location /rssserver/>
  ProxyPass http://127.0.0.1:8080/
  ProxyPassReverse http://127.0.0.1:8080/
  RequestHeader set X-Forwarded-Prefix "/rssserver"
  RequestHeader set X-Forwarded-Proto "https"
  Require all granted
  Options none
</Location>
```
