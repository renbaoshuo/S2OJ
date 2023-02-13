FROM ubuntu:22.04

SHELL ["/bin/bash", "-c"]

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get install -y --no-install-recommends gnupg ca-certificates apt-transport-https && \
    apt-get update && \
    for pkg in vim ntp zip unzip curl wget build-essential fp-compiler python2.7 python3.10 python3-requests libseccomp-dev openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk tzdata; do \
        cnt=10 && \
        while ! apt-get install -y "$pkg"; do \
            if [ $cnt -le 0 ]; then \
              echo "Failed to install $pkg" && \
              exit 1; \
            fi; \
            cnt=$((cnt - 1)); \
        done; \
    done

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
