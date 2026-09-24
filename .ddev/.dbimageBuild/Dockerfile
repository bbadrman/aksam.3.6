
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
