import { JSDOM } from 'jsdom';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import Logger from '../utils/logger';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import sleep from '../utils/sleep';
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
} as const;

const API_LANGS_MAP = {
  C: {
    id: 'c/99/gcc',
    name: 'C',
    comment: '//',
  },
  'C++98': {
    id: 'cxx/98/gcc',
    name: 'C++98',
    comment: '//',
  },
  'C++11': {
    id: 'cxx/11/gcc',
    name: 'C++11',
    comment: '//',
  },
  'C++': {
    id: 'cxx/14/gcc',
    name: 'C++14',
    comment: '//',
  },
  'C++17': {
    id: 'cxx/17/gcc',
    name: 'C++17',
    comment: '//',
  },
  'C++20': {
    id: 'cxx/20/gcc',
    name: 'C++20',
    comment: '//',
  },
  Python3: {
    id: 'python3/c',
    name: 'Python 3',
    comment: '#',
  },
  Java8: {
    id: 'java/8',
    name: 'Java 8',
    comment: '//',
  },
  Pascal: {
    id: 'pascal/fpc',
    name: 'Pascal',
    comment: '//',
  },
};

function buildLuoguTestCaseInfoBlock(test) {
  let res = '';

  res += `<test num="${test.id}" info="${STATUS_MAP[test.status]}" time="${
    test.time || -1
  }" memory="${test.memory || -1}" score="${test.score || ''}">`;
  res += `<res>${htmlspecialchars(test.description || '')}</res>`;
  res += '</test>';

  return res;
}

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    LUOGU_HANDLE,
    LUOGU_PASSWORD,
    LUOGU_ENDPOINT = 'https://open-v1.lgapi.cn',
    LUOGU_PROXY,
  } = process.env;

  if (!LUOGU_HANDLE || !LUOGU_PASSWORD) return null;

  const account: RemoteAccount = {
    type: 'luogu-api',
    handle: LUOGU_HANDLE,
    password: LUOGU_PASSWORD,
    endpoint: LUOGU_ENDPOINT,
  };

  if (LUOGU_PROXY) account.proxy = LUOGU_PROXY;

  return account;
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
  csrf: string = null;

  get(url: string) {
    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://www.luogu.com.cn'}${url}`;

    logger.debug('get', url, this.cookie);

    const req = superagent
      .get(url)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT);

    if (this.account.type == 'luogu-api') {
      req.auth(this.account.handle, this.account.password);
    }

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
    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://www.luogu.com.cn'}${url}`;

    logger.debug('post', url, this.cookie);

    const req = superagent
      .post(url)
      .set('Cookie', this.cookie)
      .set('x-csrf-token', this.csrf)
      .set('User-Agent', USER_AGENT)
      .set('x-requested-with', 'XMLHttpRequest')
      .set('origin', 'https://www.luogu.com.cn');

    if (this.account.type == 'luogu-api') {
      req.auth(this.account.handle, this.account.password);
    }

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
    if (this.account.type == 'luogu-api') return true;

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

    const programType =
      this.account.type == 'luogu-api'
        ? API_LANGS_MAP[lang] || API_LANGS_MAP['C++']
        : LANGS_MAP[lang] || LANGS_MAP['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    if (this.account.type == 'luogu-api') {
      const result = await this.post('/judge/problem').send({
        pid: id,
        code,
        lang: programType.id,
        o2: 1,
        trackId: submissionId,
      });

      if (result.status == 402) {
        await end({
          error: true,
          id,
          status: 'Judgment Failed',
          message: 'Payment required.',
        });

        return null;
      }

      return result.body.requestId;
    } else {
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
  }

  async ensureIsOwnSubmission(id: string) {
    if (this.account.type == 'luogu-api') return true;

    const { body } = await this.safeGet(`/record/${id}?_contentOnly=1`);

    const current_uid = body.currentUser?.uid;
    const submission_uid = body.currentData.record?.user.uid;

    return current_uid && submission_uid && current_uid === submission_uid;
  }

  async waitForSubmission(id: string, next, end, problem_id: string) {
    if (!(await this.ensureLogin())) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Login failed',
      });

      return null;
    }

    let fail = 0;
    let count = 0;

    while (count < 360 && fail < 10) {
      await sleep(500);
      count++;

      try {
        let result,
          data,
          body,
          files = [];

        if (this.account.type == 'luogu-api') {
          result = await this.get(`/judge/result?id=${id}`);
          body = result.body;
          data = body.data;
        } else {
          result = await this.safeGet(`/record/${id}?_contentOnly=1`);
          body = result.body;
          data = body.currentData.record;

          if (data?.sourceCode) {
            files.push({
              name: 'answer.code',
              content: data.sourceCode,
              lang:
                Object.keys(LANGS_MAP).find(
                  lang => LANGS_MAP[lang].id == data.language
                ) || '/',
            });
          }
        }

        if (result.status == 204) {
          await next({ status: '[Luogu] Waiting' });

          continue;
        }

        if (result.status == 200 && !data) {
          return await end({
            error: true,
            id,
            status: 'Judgment Failed',
            message: 'Failed to fetch submission details.',
            result: { files },
          });
        }

        if (
          this.account.type != 'luogu-api' &&
          data.problem.pid != problem_id
        ) {
          return await end({
            id,
            error: true,
            status: 'Judgment Failed',
            message: 'Submission does not match current problem.',
            result: { files },
          });
        }

        if (this.account.type == 'luogu-api') {
          data = {
            ...data,
            status: data.judge.status,
            score: data.judge.score,
            time: data.judge.time,
            memory: data.judge.memory,
            detail: {
              compileResult: data.compile,
              judgeResult: {
                ...data.judge,
                subtasks: data.judge.subtasks.map(sub => ({
                  ...sub,
                  testCases: sub.cases,
                })),
              },
            },
          };
        }

        if (
          data.detail.compileResult &&
          data.detail.compileResult.success === false
        ) {
          return await end({
            error: true,
            id,
            status: 'Compile Error',
            message: data.detail.compileResult.message,
            result: { files },
          });
        }

        if (!data.detail.judgeResult?.subtasks) continue;

        const finishedTestCases = Object.entries(
          data.detail.judgeResult.subtasks
        )
          .map(o => o[1])
          .reduce(
            (acc: number, sub: any) =>
              acc +
              Object.entries(sub.testCases as any[])
                .map(o => o[1])
                .filter(test => test.status >= 2).length,
            0
          );

        await next({
          status: `[Luogu] Judging (${finishedTestCases} judged)`,
        });

        if (data.status < 2) continue;

        logger.info('RecordID:', id, 'done');

        const status = STATUS_MAP[data.status];
        let details = '';

        if (this.account.type != 'luogu-api') {
          details +=
            '<remote-result-container>' +
            '<remote-result-table>' +
            Object.entries({
              题目: `<a href="https://www.luogu.com.cn/problem/${
                data.problem.pid
              }">${data.problem.pid} ${htmlspecialchars(
                data.problem.title
              )}</a>`,
              提交记录: `<a href="https://www.luogu.com.cn/record/${id}">R${id}</a>`,
              提交时间: new Date(data.submitTime * 1000).toLocaleString(
                'zh-CN'
              ),
              账号: `<a href="https://www.luogu.com.cn/user/${data.user.uid}">${data.user.name}</a>`,
              状态: status,
            })
              .map(
                o =>
                  `<remote-result-tr name="${o[0]}">${o[1]}</remote-result-tr>`
              )
              .join('') +
            '</remote-result-table>' +
            '</remote-result-container>';
        }

        details += '<tests>';

        if (data.detail.judgeResult.subtasks.length === 1) {
          details += Object.entries(
            data.detail.judgeResult.subtasks[0].testCases
          )
            .map(o => o[1])
            .map((testcase: any) =>
              buildLuoguTestCaseInfoBlock({
                ...testcase,
                id: testcase.id + (this.account.type != 'luogu-api'),
              })
            )
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
                  .map((testcase: any) =>
                    buildLuoguTestCaseInfoBlock({
                      ...testcase,
                      id: testcase.id + (this.account.type != 'luogu-api'),
                    })
                  )
                  .join('\n')}</subtask>`
            )
            .join('\n');
        }

        details += '</tests>';

        return await end({
          id,
          status,
          score: status === 'Accepted' ? 100 : Math.min(97, data.score),
          time: data.time,
          memory: data.memory,
          details:
            this.account.type != 'luogu-api'
              ? `<div>${details}</div>`
              : details,
          result: { files },
        });
      } catch (e) {
        logger.error(e);

        fail++;
      }
    }

    return await end({
      error: true,
      id,
      status: 'Judgment Failed',
      message: 'Failed to fetch submission details.',
    });
  }
}
