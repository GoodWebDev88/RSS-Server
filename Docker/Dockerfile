FROM ubuntu:19.10

ENV TZ UTC
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update && \
	apt-get install --no-install-recommends -y \
	ca-certificates cron \
	apache2 libapache2-mod-php \
	php-curl php-gmp php-intl php-mbstring php-xml php-zip \
	php-sqlite3 php-mysql php-pgsql && \
	rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/RSSServer/ /run/apache2/
WORKDIR /var/www/RSSServer

COPY . /var/www/RSSServer
COPY ./Docker/*.Apache.conf /etc/apache2/sites-available/

ARG RSSSERVER_VERSION
ARG SOURCE_BRANCH
ARG SOURCE_COMMIT

LABEL \
	org.opencontainers.image.authors="Alkarex" \
	org.opencontainers.image.description="A self-hosted RSS feed aggregator" \
	org.opencontainers.image.documentation="https://rssserver.github.io/RSSServer/" \
	org.opencontainers.image.licenses="AGPL-3.0" \
	org.opencontainers.image.revision="${SOURCE_BRANCH}.${SOURCE_COMMIT}" \
	org.opencontainers.image.source="https://github.com/RSSServer/RSSServer" \
	org.opencontainers.image.title="RSSServer" \
	org.opencontainers.image.url="https://rssserver.org/" \
	org.opencontainers.image.vendor="RSSServer" \
	org.opencontainers.image.version="$RSSSERVER_VERSION"

RUN a2dismod -f alias autoindex negotiation status && \
	a2enmod deflate expires headers mime setenvif && \
	a2disconf '*' && \
	a2dissite '*' && \
	a2ensite 'RSSServer*'

RUN sed -r -i "/^\s*(CustomLog|ErrorLog|Listen) /s/^/#/" /etc/apache2/apache2.conf && \
	sed -r -i "/^\s*Listen /s/^/#/" /etc/apache2/ports.conf && \
	touch /var/www/RSSServer/Docker/env.txt && \
	echo "7,37 * * * * . /var/www/RSSServer/Docker/env.txt; \
		su www-data -s /bin/sh -c 'php /var/www/RSSServer/app/actualize_script.php' \
		2>> /proc/1/fd/2 > /tmp/RSSServer.log" | crontab -

ENV COPY_LOG_TO_SYSLOG On
ENV COPY_SYSLOG_TO_STDERR On
ENV CRON_MIN ''
ENV RSSSERVER_ENV ''

ENTRYPOINT ["./Docker/entrypoint.sh"]

EXPOSE 80
# hadolint ignore=DL3025
CMD ([ -z "$CRON_MIN" ] || cron) && \
	. /etc/apache2/envvars && \
	exec apache2 -D FOREGROUND

HEALTHCHECK --start-period=20s --interval=37s --timeout=5s --retries=3 \
	CMD (php -r "readfile('http://localhost/i/');" | grep -q 'jsonVars') || exit 1
