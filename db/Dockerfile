FROM mysql:8.0.32

ADD . /opt/uoj_db
WORKDIR /opt/uoj_db

# Run the latest UOJ Community verison db install script
RUN sh install.sh

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
