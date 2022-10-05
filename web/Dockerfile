FROM ubuntu:22.04

ARG CLONE_ADDFLAG

ENV DEBIAN_FRONTEND=noninteractive
RUN dpkg -s gnupg 2>/dev/null || (apt-get update && apt-get install -y gnupg) &&\
echo "deb http://ppa.launchpad.net/stesie/libv8/ubuntu bionic main" | tee /etc/apt/sources.list.d/stesie-libv8.list && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 1A10946ED858A0DF &&\
echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C &&\
apt-get update --allow-unauthenticated &&\
apt-get install -y --allow-unauthenticated -o Dpkg::Options::="--force-overwrite" libv8 php7.4 php7.4-yaml php7.4-xml php7.4-dev php7.4-zip php7.4-mysql php7.4-mbstring php7.4-gd libseccomp-dev git vim ntp zip unzip curl wget libapache2-mod-xsendfile mysql-server php-pear cmake fp-compiler re2c libv8-7.5-dev libyaml-dev python2.7 python3.10 python3-requests openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk &&\
git clone https://github.com/phpv8/v8js.git --depth=1 -b 2.1.2 /tmp/pear/download/v8js-master && cd /tmp/pear/download/v8js-master &&\
phpize && ./configure --with-php-config=/usr/bin/php-config --with-v8js=/opt/libv8-7.5 && make install && cd -

ADD . /opt/uoj
WORKDIR /opt/uoj

# Install environment and set startup script
RUN sh web/install.sh -p && echo "\
#!/bin/sh\n\
if [ ! -f \"/var/uoj_data/.UOJSetupDone\" ]; then\n\
  cd /opt/uoj/web && sh install.sh -i\n\
fi\n\
service ntp start\n\
service apache2 start\n\
cd /opt/uoj/web && sh install.sh -d\n\
exec bash\n" >/opt/up && chmod +x /opt/up

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
EXPOSE 80
CMD /opt/up
