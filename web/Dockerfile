FROM ubuntu:22.04

ARG CLONE_ADDFLAG
ARG USE_MIRROR
ENV USE_MIRROR $USE_MIRROR
SHELL ["/bin/bash", "-c"]

ENV DEBIAN_FRONTEND=noninteractive
ENV PKGS="php7.4 php7.4-yaml php7.4-xml php7.4-dev php7.4-zip php7.4-mysql php7.4-mbstring php7.4-gd php7.4-imagick libseccomp-dev git vim ntp zip unzip curl wget apache2 libapache2-mod-xsendfile php-pear mysql-client build-essential fp-compiler re2c libseccomp-dev libyaml-dev python2.7 python3.10 python3-requests openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk"
RUN if [[ "$USE_MIRROR" == "1" ]]; then\
        sed -i "s@http://.*archive.ubuntu.com@https://mirrors.aliyun.com@g" /etc/apt/sources.list &&\
        sed -i "s@http://.*security.ubuntu.com@https://mirrors.aliyun.com@g" /etc/apt/sources.list ;\
    fi &&\
    apt-get -o "Acquire::https::Verify-Peer=false" update && apt-get -o "Acquire::https::Verify-Peer=false" install -y --no-install-recommends gnupg ca-certificates &&\
    echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C &&\
    if [[ "$USE_MIRROR" == "1" ]]; then\
        find /etc/apt/sources.list.d/ -type f -name "*.list" -exec sed -i.bak -r 's#deb(-src)?\s*http(s)?://ppa.launchpad.net#deb\1 https://launchpad.proxy.ustclug.org#ig' {} \; &&\
        find /etc/apt/sources.list.d/ -type f -name "*.list" -exec sed -i.bak -r 's#deb(-src)?\s*http(s)?://ppa.launchpadcontent.net#deb\1 https://launchpad.proxy.ustclug.org#ig' {} \; ;\
    fi &&\
    apt-get update --allow-unauthenticated &&\
    (apt-get install -y --allow-unauthenticated --no-install-recommends -o Dpkg::Options::="--force-overwrite" -o APT::Acquire::Retries="30" --fix-missing $PKGS || apt-get install --fix-broken -y -o APT::Acquire::Retries="30")

ADD . /opt/uoj
WORKDIR /opt/uoj

# Install environment and set startup script
RUN sh web/install.sh -p && echo -e "\
#!/bin/sh\n\
if [ ! -f \"/var/uoj_data/.UOJSetupDone\" ]; then\n\
  cd /opt/uoj/web && sh install.sh -i\n\
fi\n\
service ntp start\n\
service apache2 start\n\
cd /opt/uoj/web && sh install.sh -i\n\
exec bash\n" >/opt/up && chmod +x /opt/up

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
EXPOSE 80
CMD /opt/up
