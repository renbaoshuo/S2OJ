import { JSDOM } from 'jsdom';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import Logger from '../utils/logger';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import sleep from '../utils/sleep';
import flattenDeep from 'lodash.flattendeep';
import htmlspecialchars from '../utils/htmlspecialchars';

proxy(superagent);
const logger = new Logger('remote/luogu');

const STATUS_MAP = [
  'Waiting', // WAITING,
  'Judging', // JUDGING,
  'Compile Error', // CE
  'Output Limit Exceeded', // OLE
  'Memory Limit Exceeded', // MLE
  'Time Limit Exceeded', // TLE
  'Wrong Answer', // WA
  'Runtime Error', // RE
  0,
  0,
  0,
  'Judgment Failed', // UKE
  'Accepted', // AC
  0,
  'Wrong Answer', // WA
];

const LANGS_MAP = {
  C: {
    id: 2,
    name: 'C',
    comment: '//',
  },
  'C++98': {
    id: 3,
    name: 'C++98',
    comment: '//',
  },
  'C++11': {
    id: 4,
    name: 'C++11',
    comment: '//',
  },
  'C++': {
    id: 11,
    name: 'C++14',
    comment: '//',
  },
  'C++17': {
    id: 12,
    name: 'C++17',
    comment: '//',
  },
  'C++20': {
    id: 27,
    name: 'C++20',
    comment: '//',
  },
  Python3: {
    id: 7,
    name: 'Python 3',
    comment: '#',
  },
  Java8: {
    id: 8,
    name: 'Java 8',
    comment: '//',
  },
  Pascal: {
    id: 1,
    name: 'Pascal',
    comment: '//',
  },
};

function buildLuoguTestCaseInfoBlock(test) {
  let res = '';

  res += `<test num="${test.id + 1}" info="${STATUS_MAP[test.status]}" time="${
    test.time || -1
  }" memory="${test.memory || -1}" score="${test.score || ''}">`;
  res += `<res>${htmlspecialchars(test.description || '')}</res>`;
  res += '</test>';

  return res;
}

export default class LuoguProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    if (account.cookie) this.cookie = account.cookie;
  }

  static constructFromAccountData(data) {
    return new this({
      type: 'luogu',
      cookie: Object.entries(data).map(([key, value]) => `${key}=${value}`),
    });
  }

  cookie: string[] = [];
  csrf: string;

  get(url: string) {
    logger.debug('get', url, this.cookie);

    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://www.luogu.com.cn'}${url}`;

    const req = superagent
      .get(url)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT);

    if (this.account.proxy) return req.proxy(this.account.proxy);

    return req;
  }

  async safeGet(url: string) {
    const res = await this.get(url);

    if (res.text.startsWith('<html><script>document.location.reload()')) {
      const sec = this.getCookie.call(
        { cookie: res.header['set-cookie'] },
        'sec'
      );
      this.setCookie('sec', sec);
      logger.debug('sec', sec);

      return await this.get(url);
    }

    return res;
  }

  post(url: string) {
    logger.debug('post', url, this.cookie);

    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://www.luogu.com.cn'}${url}`;

    const req = superagent
      .post(url)
      .set('Cookie', this.cookie)
      .set('x-csrf-token', this.csrf)
      .set('User-Agent', USER_AGENT)
      .set('x-requested-with', 'XMLHttpRequest')
      .set('origin', 'https://www.luogu.com.cn');

    if (this.account.proxy) return req.proxy(this.account.proxy);

    return req;
  }

  getCookie(target: string) {
    return this.cookie
      .find(i => i.startsWith(`${target}=`))
      ?.split('=')[1]
      ?.split(';')[0];
  }

  setCookie(target: string, value: string) {
    this.cookie = this.cookie.filter(i => !i.startsWith(`${target}=`));
    this.cookie.push(`${target}=${value}`);
  }

  async getCsrfToken(url: string) {
    let { text: html } = await this.safeGet(url);

    const $dom = new JSDOM(html);

    this.csrf = $dom.window.document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute('content');

    logger.info('csrf-token=', this.csrf);
  }

  get loggedIn() {
    return this.safeGet('/user/setting?_contentOnly=1').then(
      ({ body }) => body.currentTemplate !== 'AuthLogin'
    );
  }

  async ensureLogin() {
    if (await this.loggedIn) {
      await this.getCsrfToken('/user/setting');

      return true;
    }

    logger.info('retry login');

    // TODO login;

    return false;
  }

  async submitProblem(
    id: string,
    lang: string,
    code: string,
    submissionId: number,
    next,
    end
  ) {
    if (!(await this.ensureLogin())) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Login failed',
      });

      return null;
    }

    if (code.length < 10) {
      await end({
        error: true,
        status: 'Compile Error',
        message: 'Code too short',
      });

      return null;
    }

    const programType = LANGS_MAP[lang] || LANGS_MAP['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const result = await this.post(`/fe/api/problem/submit/${id}`)
      .set('referer', `https://www.luogu.com.cn/problem/${id}`)
      .send({
        code,
        lang: programType.id,
        enableO2: 1,
      });

    logger.info('RecordID:', result.body.rid);

    return result.body.rid;
  }

  async waitForSubmission(id: string, next, end) {
    let fail = 0;
    let count = 0;

    while (count < 180 && fail < 10) {
      await sleep(1000);
      count++;

      try {
        const { body } = await this.safeGet(`/record/${id}?_contentOnly=1`);
        const data = body.currentData.record;

        if (
          data.detail.compileResult &&
          data.detail.compileResult.success === false
        ) {
          return await end({
            error: true,
            id: `R${id}`,
            status: 'Compile Error',
            message: data.detail.compileResult.message,
          });
        }

        logger.info('Fetched with length', JSON.stringify(body).length);
        const total = flattenDeep(
          Object.entries(body.currentData.testCaseGroup || {}).map(o => o[1])
        ).length;

        if (!data.detail.judgeResult?.subtasks) continue;

        await next({
          status: `Judging (${
            data.detail.judgeResult?.finishedCaseCount || '?'
          }/${total})`,
        });

        if (data.status < 2) continue;

        logger.info('RecordID:', id, 'done');

        const status = STATUS_MAP[data.status];
        let details = '';

        details +=
          '<remote-result-container>' +
          '<remote-result-table>' +
          Object.entries({
            题目: `<a href="https://www.luogu.com.cn/problem/${
              data.problem.pid
            }">${data.problem.pid} ${htmlspecialchars(data.problem.title)}</a>`,
            提交记录: `<a href="https://www.luogu.com.cn/record/${id}">R${id}</a>`,
            提交时间: new Date(data.submitTime * 1000).toLocaleString('zh-CN'),
            账号: `<a href="https://www.luogu.com.cn/user/${data.user.uid}">${data.user.name}</a>`,
            状态: status,
          })
            .map(
              o => `<remote-result-tr name="${o[0]}">${o[1]}</remote-result-tr>`
            )
            .join('') +
          '</remote-result-table>' +
          '</remote-result-container>';

        if (data.detail.judgeResult.subtasks.length === 1) {
          details += Object.entries(
            data.detail.judgeResult.subtasks[0].testCases
          )
            .map(o => o[1])
            .map(buildLuoguTestCaseInfoBlock)
            .join('\n');
        } else {
          details += Object.entries(data.detail.judgeResult.subtasks)
            .map(o => o[1])
            .map(
              (subtask: any, index) =>
                `<subtask num="${index}" info="${
                  STATUS_MAP[subtask.status]
                }" time="${subtask.time || -1}" memory="${
                  subtask.memory || -1
                }" score="${subtask.score || ''}">${Object.entries(
                  subtask.testCases
                )
                  .map(o => o[1])
                  .map(buildLuoguTestCaseInfoBlock)
                  .join('\n')}</subtask>`
            )
            .join('\n');
        }

        return await end({
          id: `R${id}`,
          status,
          score:
            status === 'Accepted'
              ? 100
              : (data.score / data.problem.fullScore) * 100,
          time: data.time,
          memory: data.memory,
          details: `<div>${details}</div>`,
        });
      } catch (e) {
        logger.error(e);

        fail++;
      }
    }

    return await end({
      error: true,
      id: `R${id}`,
      status: 'Judgment Failed',
      message: 'Failed to fetch submission details.',
    });
  }
}
