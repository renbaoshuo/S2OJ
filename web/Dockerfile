FROM ubuntu:20.04

ARG CLONE_ADDFLAG

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
exec bash\n" >/opt/up && chmod +x /opt/up

ENV LANG=C.UTF-8 TZ=Asia/Shanghai
EXPOSE 80
CMD /opt/up
