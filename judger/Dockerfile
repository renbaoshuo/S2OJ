FROM ubuntu:22.04

ARG CLONE_ADDFLAG
ARG USE_MIRROR
ENV USE_MIRROR $USE_MIRROR
SHELL ["/bin/bash", "-c"]

ENV DEBIAN_FRONTEND=noninteractive
ENV PKGS="vim ntp zip unzip curl wget build-essential fp-compiler python2.7 python3.10 python3-requests libseccomp-dev openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk"
RUN if [[ "$USE_MIRROR" == "1" ]]; then\
    sed -i "s@http://.*archive.ubuntu.com@http://mirrors.tuna.tsinghua.edu.cn@g" /etc/apt/sources.list &&\
    sed -i "s@http://.*security.ubuntu.com@http://mirrors.tuna.tsinghua.edu.cn@g" /etc/apt/sources.list ;\
fi &&\
apt-get update &&\
(apt-get install -y --allow-unauthenticated --no-install-recommends -o Dpkg::Options::="--force-overwrite" -o APT::Acquire::Retries="30" --fix-missing $PKGS || apt --fix-broken install -y || apt-get install -y --allow-unauthenticated --no-install-recommends -o Dpkg::Options::="--force-overwrite" -o APT::Acquire::Retries="30" --fix-missing $PKGS)

ADD . /opt/uoj_judger
WORKDIR /opt/uoj_judger

# Install environment and set startup script
RUN sh install.sh -p && echo -e "\
#!/bin/sh\n\
if [ ! -f \"/opt/uoj_judger/.conf.json\" ]; then\n\
  cd /opt/uoj_judger && sh install.sh -i\n\
fi\n\
service ntp start\n\
su judger -c \"/opt/uoj_judger/judge_client start\"\n\
exec bash\n" >/opt/up && chmod +x /opt/up

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
EXPOSE 2333
CMD /opt/up
