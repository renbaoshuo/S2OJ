import { JSDOM } from 'jsdom';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import sleep from '../utils/sleep';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import Logger from '../utils/logger';

proxy(superagent);
const logger = new Logger('remote/atcoder');

const LANGS_MAP = {
  C: {
    name: 'C (GCC 9.2.1)',
    id: 4001,
    comment: '//',
  },
  'C++': {
    name: 'C++ (GCC 9.2.1)',
    id: 4003,
    comment: '//',
  },
  Pascal: {
    name: 'Pascal (FPC 3.0.4)',
    id: 4041,
    comment: '//',
  },
  Python3: {
    name: 'Python (3.8.2)',
    id: 4006,
    comment: '#',
  },
};

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    ATCODER_HANDLE,
    ATCODER_PASSWORD,
    ATCODER_ENDPOINT = 'https://atcoder.jp',
    ATCODER_PROXY,
  } = process.env;

  if (!ATCODER_HANDLE || !ATCODER_PASSWORD) return null;

  const account: RemoteAccount = {
    type: 'atcoder',
    handle: ATCODER_HANDLE,
    password: ATCODER_PASSWORD,
    endpoint: ATCODER_ENDPOINT,
  };

  if (ATCODER_PROXY) account.proxy = ATCODER_PROXY;

  return account;
}

function parseProblemId(id: string) {
  let [, contestId, problemId] = /^(\w+)([a-z][1-9]?)$/.exec(id);

  if (contestId.endsWith('_')) {
    problemId = `${contestId}${problemId}`;
  } else {
    problemId = `${contestId}_${problemId}`;
  }

  contestId = contestId.replace(/_/g, '');

  return [contestId, problemId];
}

export default class AtcoderProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    if (account.cookie) this.cookie = account.cookie;
    this.account.endpoint ||= 'https://atcoder.jp';
  }

  static constructFromAccountData(data) {
    throw new Error('Method not implemented.');
  }

  cookie: string[] = ['language=en'];
  csrf: string;

  get(url: string) {
    logger.debug('get', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .get(url)
      .redirects(0)
      .ok(res => res.status < 400)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT);
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  post(url: string) {
    logger.debug('post', url, this.cookie);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .post(url)
      .type('form')
      .redirects(0)
      .ok(res => res.status < 400)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT);
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
    const { text: html, header } = await this.get(url);
    const {
      window: { document },
    } = new JSDOM(html);

    if (header['set-cookie']) {
      this.cookie = header['set-cookie'];
    }

    let value = /csrfToken = "(.+?)"/g.exec(html);
    if (value) return value[1];

    if (document.body.children.length < 2 && html.length < 512) {
      throw new Error(document.body.textContent!);
    }

    return document
      .querySelector('input[name="csrf_token"]')
      ?.getAttribute('value');
  }

  get loggedIn() {
    return this.get('/login').then(res => {
      const html = res.text;

      if (res.header['set-cookie']) {
        this.cookie = res.header['set-cookie'];
      }

      if (html.includes('<a href="/login">Sign In</a>')) return false;
      return true;
    });
  }

  async ensureLogin() {
    if (await this.loggedIn) return true;
    logger.info('retry normal login');
    const csrf = await this.getCsrfToken('/login');
    const res = await this.post('/login').send({
      csrf_token: csrf,
      username: this.account.handle,
      password: this.account.password,
    });
    const cookie = res.header['set-cookie'];
    if (cookie) {
      this.cookie = cookie;
    }
    if (await this.loggedIn) {
      logger.success('Logged in');
      return true;
    }
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
    const programType = LANGS_MAP[lang] || LANGS_MAP['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const [contestId, problemId] = parseProblemId(id);
    const csrf = await this.getCsrfToken(
      `/contests/${contestId}/tasks/${problemId}`
    );

    logger.debug(
      'Submitting',
      id,
      programType,
      lang,
      `(S2OJ Submission #${submissionId})`
    );

    // TODO: check submit time to ensure submission
    const res = await this.post(`/contests/${contestId}/submit`).send({
      csrf_token: csrf,
      'data.TaskScreenName': problemId,
      'data.LanguageId': programType.id,
      sourceCode: code,
    });

    if (res.error) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Failed to submit code.',
      });

      return null;
    }

    if (res.header['set-cookie']) {
      this.cookie = res.header['set-cookie'];
    }

    const { text: status, header: status_header } = await this.get(
      `/contests/${contestId}/submissions/me`
    ).retry(3);

    if (status_header['set-cookie']) {
      this.cookie = status_header['set-cookie'];
    }

    const {
      window: { document },
    } = new JSDOM(status);

    return document
      .querySelector('.submission-score[data-id]')
      .getAttribute('data-id');
  }

  async waitForSubmission(id: string, next, end, problem_id: string) {
    let count = 0;
    let fail = 0;

    const [contestId] = parseProblemId(problem_id);
    const status_url = `/contests/${contestId}/submissions/me/status/json?reload=true&sids[]=${id}`;

    while (count < 180 && fail < 10) {
      count++;
      await sleep(1000);

      try {
        const { body, header } = await this.get(status_url).retry(3);

        if (header['set-cookie']) {
          this.cookie = header['set-cookie'];
        }

        const result = body.Result[id];
        const {
          window: { document },
        } = new JSDOM(`<table>${result.Html}</table>`);

        const elements = document.querySelectorAll('td');
        const statusTd = elements[0];
        const statusElem = statusTd.querySelector('span');

        if (
          statusElem.title === 'Waiting for Judging' ||
          statusElem.title === 'Waiting for Re-judging' ||
          ['WJ', 'WR'].includes(statusElem.innerHTML.trim())
        ) {
          await next({ test_id: 0 });

          continue;
        }

        if (
          statusElem.title === 'Judging' ||
          (statusTd.colSpan == 3 &&
            statusTd.className.includes('waiting-judge'))
        ) {
          await next({ test_id: /(\d+)/.exec(statusElem.innerHTML)[1] || 0 });

          continue;
        }

        if (statusElem.title === 'Compilation Error') {
          return await end({
            id,
            error: true,
            status: 'Compile Error',
            message: '',
          });
        }

        if (statusElem.title === 'Internal Error') {
          return await end({
            error: true,
            status: 'Judgment Failed',
            message: 'AtCoder Internal Error.',
          });
        }

        const time = parseInt(elements[1].innerHTML.trim());
        const memory = parseInt(elements[2].innerHTML.trim());

        return await end({
          id,
          status: statusElem.title || 'None',
          score:
            statusElem.title === 'Accepted' ||
            statusElem.innerHTML.trim() === 'AC'
              ? 100
              : 0,
          time,
          memory,
        });
      } catch (e) {
        logger.error(e);

        fail++;
      }
    }

    return await end({
      id,
      error: true,
      status: 'Judgment Failed',
      message: 'Failed to fetch submission details.',
    });
  }
}
