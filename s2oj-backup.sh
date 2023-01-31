#!/bin/bash

## 0 3 * * * /data/s2oj/s2oj-backup.sh >/dev/null 2>&1

if pidof -o %PPID -x "/data/s2oj/s2oj-backup.sh"; then
	echo "Already running"
    exit 1
fi

rclone sync /data/s2oj baoshuo-s3-de-fra2:s2oj --include "/docker-compose.local.yml" --include "/.config.local.php" --include "/uoj_data/web/data/*.zip" --include "/uoj_data/web/storage/**" --transfers=20 --buffer-size=500M --checkers=20
/usr/local/bin/docker-compose -f /data/s2oj/docker-compose.local.yml exec uoj-db mysqldump -uroot --password="${MYSQL_ROOT_PASSWORD:-root}" app_uoj233 > "/data/s2oj-db-backup/s2oj-database_$(date +'%F-%H%M%S').sql"
find /data/s2oj-db-backup/ -mtime +7 -name "*.sql" -exec rm -rf {} \;
rclone sync /data/s2oj-db-backup/ baoshuo-s3-de-fra2:s2oj-db-backup --buffer-size=500M
