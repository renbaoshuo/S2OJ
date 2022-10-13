FROM ubuntu:22.04

ARG CLONE_ADDFLAG

ENV DEBIAN_FRONTEND=noninteractive
RUN dpkg -s gnupg 2>/dev/null || (apt-get update && apt-get install -y gnupg) &&\
echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C &&\
apt-get update --allow-unauthenticated &&\
apt-get install -y --allow-unauthenticated -o Dpkg::Options::="--force-overwrite" php7.4 php7.4-yaml php7.4-xml php7.4-dev php7.4-zip php7.4-mysql php7.4-mbstring php7.4-gd php7.4-imagick libseccomp-dev git vim ntp zip unzip curl wget libapache2-mod-xsendfile mysql-server php-pear cmake fp-compiler re2c libyaml-dev python2.7 python3.10 python3-requests openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk

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
cd /opt/uoj/web && sh install.sh -i\n\
exec bash\n" >/opt/up && chmod +x /opt/up

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
EXPOSE 80
CMD /opt/up
