
#ddev-generated - Do not modify this file; your modifications will be overwritten.
ARG BASE_IMAGE="scratch"

### DDEV-injected base Dockerfile contents
FROM $BASE_IMAGE
SHELL ["/bin/bash", "-c"]
ARG TARGETPLATFORM
ARG TARGETARCH
ARG TARGETOS
ARG username
ARG uid
ARG gid
ARG DDEV_PHP_VERSION
ARG DDEV_DATABASE
RUN getent group tty || groupadd tty
# Group 0 (root) is added here so this user can read/write directories that are
# baked group-writable-by-root in the base image (e.g. /usr/local/n, /var/log,
# see containers/ddev-webserver/Dockerfile) without a per-project chgrp/chmod
# of those directories. See the note near the chmod -R below, and
# https://developers.redhat.com/blog/2020/10/26/adapting-docker-and-kubernetes-containers-to-run-on-red-hat-openshift-container-platform
RUN (groupadd --gid "$gid" "$username" || groupadd "$username" || true) && \
    (useradd -G tty,0 -l -m -s "/bin/bash" --gid "$username" --comment '' --uid "$uid" "$username" || \
    useradd -G tty,0 -l -m -s "/bin/bash" --gid "$username" --comment '' "$username" || \
    useradd -G tty,0 -l -m -s "/bin/bash" --gid "$gid" --comment '' "$username" || \
    useradd -G tty,0 -l -m -s "/bin/bash" --comment '' "$username")

### DDEV-injected extra content

RUN mkdir -p /home/$username && chown $username /home/$username && chmod 600 /home/$username/.pgpass
RUN log-stderr.sh mariadb-compat-install.sh || true

RUN log-stderr.sh mariadb-skip-ssl-wrapper-install.sh || true


### DDEV-injected composer update
RUN export XDEBUG_MODE=off; composer self-update --stable || composer self-update --stable || true; composer self-update --2 || log-stderr.sh composer self-update --2 || true

### DDEV-injected folders permission fix
RUN mkdir -p /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && chown "$uid:$gid" /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && chmod 755 /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && if [ -d /run/blackfire ]; then chown "$uid:$gid" /run/blackfire && chmod 755 /run/blackfire; fi && \
    mkdir -p /var/log/nginx /var/log/apache2 && \
    touch /var/log/nginx/error.log /var/log/nginx/access.log /var/log/php-fpm.log /var/log/supervisord.log && \
    chmod ugo+rw /var/log/nginx/error.log /var/log/nginx/access.log /var/log/php-fpm.log /var/log/supervisord.log && \
    chmod ugo+rwX /var/log/nginx /var/log/apache2 && \
    chgrp -R "$gid" /etc/alternatives /var/lib/dpkg/alternatives && \
    chmod -R g+rwX,o-w /etc/alternatives /var/lib/dpkg/alternatives && \
    chmod -f ugo+rx /usr/local/bin /usr/local/bin/* && \
    chgrp "$gid" /usr/local/bin/composer && chmod g+rwx,o-w /usr/local/bin/composer && \
    mkdir -p /tmp/xhprof && chmod -R ugo+w /etc/php /var/lib/php /tmp/xhprof

### DDEV-injected removal of the ddev-keep-root shim (only needed for apache-fpm on Docker rootless)
RUN rm -f /usr/local/lib/ddev/ddev-keep-root.so
