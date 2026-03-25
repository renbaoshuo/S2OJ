FROM ubuntu:22.04

SHELL ["/bin/bash", "-c"]

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get install -y --no-install-recommends gnupg curl ca-certificates apt-transport-https && \
    curl https://git.m.ac/api/packages/baoshuo/debian/repository.key -o /etc/apt/trusted.gpg.d/gitmac-baoshuo.asc && \
    echo "deb https://git.m.ac/api/packages/baoshuo/debian all main" | tee -a /etc/apt/sources.list.d/gitmac-baoshuo.list && \
    (echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" | tee /etc/apt/sources.list.d/ondrej-php.list) && \
    apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C && \
    apt-get update && \
    for pkg in php7.4 php7.4-yaml php7.4-xml php7.4-dev php7.4-zip php7.4-mysql php7.4-mbstring php7.4-gd php7.4-curl php7.4-imagick libseccomp-dev git vim ntp zip unzip wget apache2 libapache2-mod-xsendfile php-pear mysql-client build-essential s2oj-gcc fp-compiler re2c libseccomp-dev libyaml-dev python2.7 python3.10 python3-requests openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk language-pack-zh-hans cron tzdata; do \
        cnt=10 && \
        while ! apt-get install -y "$pkg"; do \
            if [ $cnt -le 0 ]; then \
              echo "Failed to install $pkg" && \
              exit 1; \
            fi; \
            cnt=$((cnt - 1)); \
        done; \
    done

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
