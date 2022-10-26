#!/bin/bash
genRandStr(){
    cat /dev/urandom | tr -dc [:alnum:] | head -c $1
}
#Set some vars
_database_host_="${DATABASE_HOST:-uoj-db}"
_database_password_="${DATABASE_PASSWORD:-root}"
_judger_socket_port_="${JUDGER_SOCKET_PORT:-2333}"
_judger_socket_password_="${JUDGER_SOCKET_PASSWORD:-_judger_socket_password_}"
_salt0_="${SALT_0:-salt0}"
_salt1_="${SALT_1:-salt1}"
_salt2_="${SALT_2:-salt2}"
_salt3_="${SALT_3:-salt3}"
_uoj_protocol_="${UOJ_PROTOCOL:-http}"

getAptPackage(){
    printf "\n\n==> Getting environment packages\n"
    # Update apt sources and install
    export DEBIAN_FRONTEND=noninteractive
    dpkg -s gnupg 2>/dev/null || (apt-get update && apt-get install -y gnupg)
	sudo apt-get update && sudo apt-get install software-properties-common && add-apt-repository ppa:ondrej/php &&\
	apt-key adv --keyserver http://keyserver.ubuntu.com --recv 4F4EA0AAE5267A6C &&\
    apt-get update --allow-unauthenticated
    apt-get install -y --allow-unauthenticated -o Dpkg::Options::="--force-overwrite" php7.4 php7.4-yaml php7.4-xml php7.4-dev php7.4-zip php7.4-mysql php7.4-mbstring php7.4-gd php7.4-imagick libseccomp-dev git vim ntp zip unzip curl wget libapache2-mod-xsendfile mysql-server php-pear cmake fp-compiler re2c libyaml-dev python2.7 python3.10 python3-requests openjdk-8-jdk openjdk-11-jdk openjdk-17-jdk
}

setLAMPConf(){
    printf "\n\n==> Setting LAMP configs\n"
    #Set Apache UOJ site conf
    cat >/etc/apache2/sites-available/000-uoj.conf <<UOJEOF
<VirtualHost *:80>
    #ServerName local_uoj.ac
    ServerAdmin opensource@uoj.ac
    DocumentRoot /var/www/uoj

    SetEnvIf Request_URI "^/judge/.*$" judgelog
    #LogLevel info ssl:warn
    ErrorLog \${APACHE_LOG_DIR}/uoj_error.log
    CustomLog \${APACHE_LOG_DIR}/uoj_judge.log common env=judgelog
    CustomLog \${APACHE_LOG_DIR}/uoj_access.log combined env=!judgelog

    XSendFile On
    XSendFilePath /var/uoj_data
    XSendFilePath /var/www/uoj/app/storage
    XSendFilePath /opt/uoj/judger/uoj_judger/include
</VirtualHost>
UOJEOF
    #Enable modules and make UOJ site conf enabled
    a2ensite 000-uoj.conf && a2dissite 000-default.conf
    a2enmod rewrite headers && sed -i -e '172s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
    #Create UOJ session save dir and make PHP extensions available
    mkdir --mode=733 /var/lib/php/uoj_sessions && chmod +t /var/lib/php/uoj_sessions
	sed -i 's|;sys_temp_dir = "/tmp"|sys_temp_dir = "/tmp"|g' /etc/php/7.4/apache2/php.ini
}

setWebConf(){
    printf "\n\n==> Setting web files\n"
    # Set webroot path
    ln -sf /opt/uoj/web /var/www/uoj
    chown -R www-data /var/www/uoj/app/storage
    # Set web config file
    php7.4 -a <<UOJEOF
\$config = include '/var/www/uoj/app/.default-config.php';
\$config['database']['host']='$_database_host_';
\$config['database']['password']='$_database_password_';
\$config['judger']['socket']['port']='$_judger_socket_port_';
file_put_contents('/var/www/uoj/app/.config.php', "<?php\nreturn ".str_replace('\'_httpHost_\'','UOJContext::httpHost()',var_export(\$config, true)).";\n");
UOJEOF
    # Prepare local sandbox
    cd /opt/uoj/judger/uoj_judger
    cat >include/uoj_work_path.h <<UOJEOF
#define UOJ_WORK_PATH "/opt/uoj/judger/uoj_judger"
#define UOJ_JUDGER_BASESYSTEM_UBUNTU1804
#define UOJ_JUDGER_PYTHON3_VERSION "3.6"
#define UOJ_JUDGER_FPC_VERSION "3.0.4"
UOJEOF
    make all -j$(($(nproc) + 1)) && cd /opt/uoj/web
}

initProgress(){
    printf "\n\n==> Doing initial config and start service\n"
    #Set uoj_data path
    mkdir -p /var/uoj_data/upload
    chown -R www-data:www-data /var/uoj_data
    #Replace password placeholders
    sed -i -e "s/salt0/$_salt0_/g" -e "s/salt1/$_salt1_/g" -e "s/salt2/$_salt2_/g" -e "s/salt3/$_salt3_/g" -e "s/_judger_socket_password_/$_judger_socket_password_/g" /var/www/uoj/app/.config.php
	sed -i -e "s/'protocol' => 'http'/'protocol' => '$_uoj_protocol_'/g" /var/www/uoj/app/.config.php
    #Start services
    service ntp restart
    service apache2 restart
	mkdir -p /opt/uoj/web/app/storage/submission
	mkdir -p /opt/uoj/web/app/storage/tmp
	mkdir -p /opt/uoj/web/app/storage/image_hosting
	chmod -R 777 /opt/uoj/web/app/storage
	#Using cli upgrade to latest
    php7.4 /var/www/uoj/app/cli.php upgrade:latest
    touch /var/uoj_data/.UOJSetupDone
    #Touch SetupDone flag file
    printf "\n\n***Installation complete. Enjoy!***\n"
}

prepProgress(){
    setLAMPConf;setWebConf
}

if [ $# -le 0 ]; then
    echo 'Installing UOJ System web...'
    getAptPackage;prepProgress;initProgress
fi
while [ $# -gt 0 ]; do
    case "$1" in
        -p | --prep)
            echo 'Preparing UOJ System web environment...'
            prepProgress
        ;;
        -i | --init)
            echo 'Initing UOJ System web...'
            initProgress
        ;;
        -? | --*)
            echo "Illegal option $1"
        ;;
    esac
    shift $(( $#>0?1:0 ))
done
