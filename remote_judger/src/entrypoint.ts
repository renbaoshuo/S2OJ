import daemon from './daemon';

const {
  UOJ_PROTOCOL = 'http',
  UOJ_HOST = 'uoj-web',
  UOJ_JUDGER_NAME = 'remote_judger',
  UOJ_JUDGER_PASSWORD = '',
} = process.env;
const UOJ_BASEURL = `${UOJ_PROTOCOL}://${UOJ_HOST}`;

daemon({
  server_url: UOJ_BASEURL,
  judger_name: UOJ_JUDGER_NAME,
  password: UOJ_JUDGER_PASSWORD,
});
