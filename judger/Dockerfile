FROM ubuntu:20.04

ARG CLONE_ADDFLAG

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y vim ntp zip unzip curl wget build-essential fp-compiler python2.7 python3.8 python3-requests

ADD . /opt/uoj_judger
WORKDIR /opt/uoj_judger

# Install environment and set startup script
RUN sh install.sh -p && echo "\
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
