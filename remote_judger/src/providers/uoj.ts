import { JSDOM } from 'jsdom';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import Logger from '../utils/logger';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import { parseTimeMS, parseMemoryMB } from '../utils/parse';
import sleep from '../utils/sleep';

proxy(superagent);
const logger = new Logger('remote/uoj');

const langs_map = {
  C: {
    name: 'C',
    id: 'C',
    comment: '//',
  },
  'C++03': {
    name: 'C++ 03',
    id: 'C++',
    comment: '//',
  },
  'C++11': {
    name: 'C++ 11',
    id: 'C++11',
    comment: '//',
  },
  'C++': {
    name: 'C++ 14',
    id: 'C++14',
    comment: '//',
  },
  'C++17': {
    name: 'C++ 17',
    id: 'C++17',
    comment: '//',
  },
  'C++20': {
    name: 'C++ 20',
    id: 'C++20',
    comment: '//',
  },
  'Python2.7': {
    name: 'Python 2.7',
    id: 'Python2.7',
    comment: '#',
  },
  Python3: {
    name: 'Python 3',
    id: 'Python3',
    comment: '#',
  },
  Java8: {
    name: 'Java 8',
    id: 'Java8',
    comment: '//',
  },
  Java11: {
    name: 'Java 11',
    id: 'Java11',
    comment: '//',
  },
  Java17: {
    name: 'Java 17',
    id: 'Java17',
    comment: '//',
  },
  Pascal: {
    name: 'Pascal',
    id: 'Pascal',
    comment: '//',
  },
};

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    UOJ_HANDLE,
    UOJ_PASSWORD,
    UOJ_ENDPOINT = 'https://uoj.ac',
    UOJ_PROXY,
  } = process.env;

  if (!UOJ_HANDLE || !UOJ_PASSWORD) return null;

  const account: RemoteAccount = {
    type: 'uoj',
    handle: UOJ_HANDLE,
    password: UOJ_PASSWORD,
    endpoint: UOJ_ENDPOINT,
  };

  if (UOJ_PROXY) account.proxy = UOJ_PROXY;

  return account;
}

export default class UOJProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    if (account.cookie) this.cookie = account.cookie;
  }

  static constructFromAccountData(data) {
    throw new Error('Method not implemented.');
  }

  cookie: string[] = [];
  csrf: string;

  get(url: string) {
    logger.debug('get', url);
    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://uoj.ac'}${url}`;
    const req = superagent
      .get(url)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT);
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  post(url: string) {
    logger.debug('post', url, this.cookie);
    if (!url.includes('//'))
      url = `${this.account.endpoint || 'https://uoj.ac'}${url}`;
    const req = superagent
      .post(url)
      .set('Cookie', this.cookie)
      .set('User-Agent', USER_AGENT)
      .type('form');
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  async getCsrfToken(url: string) {
    const { text: html, header } = await this.get(url);
    if (header['set-cookie']) {
      this.cookie = header['set-cookie'];
    }
    let value = /_token *: *"(.+?)"/g.exec(html);
    if (value) return value[1];
    value = /_token" value="(.+?)"/g.exec(html);
    return value?.[1];
  }

  get loggedIn() {
    return this.get('/login').then(
      ({ text: html }) => !html.includes('<title>登录')
    );
  }

  async ensureLogin() {
    if (await this.loggedIn) return true;
    logger.info('retry login');
    const _token = await this.getCsrfToken('/login');
    const { header, text } = await this.post('/login').send({
      _token,
      login: '',
      username: this.account.handle,
      // NOTE: you should pass a pre-hashed key!
      password: this.account.password,
    });
    if (header['set-cookie'] && this.cookie.length === 1) {
      header['set-cookie'].push(...this.cookie);
      this.cookie = header['set-cookie'];
    }
    if (text === 'ok') return true;
    return text;
  }

  async submitProblem(
    id: string,
    lang: string,
    code: string,
    submissionId: number,
    next,
    end
  ) {
    const programType = langs_map[lang] || langs_map['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const _token = await this.getCsrfToken(`/problem/${id}`);
    const { text } = await this.post(`/problem/${id}`).send({
      _token,
      answer_answer_language: programType.id,
      answer_answer_upload_type: 'editor',
      answer_answer_editor: code,
      'submit-answer': 'answer',
    });

    if (!text.includes('我的提交记录')) throw new Error('Submit failed');

    const { text: status } = await this.get(
      `/submissions?problem_id=${id}&submitter=${this.account.handle}`
    );

    const $dom = new JSDOM(status);
    return $dom.window.document
      .querySelector('tbody>tr>td>a')
      .innerHTML.split('#')[1];
  }

  async waitForSubmission(problem_id: string, id: string, next, end) {
    let i = 0;

    while (true) {
      if (++i > 180) {
        return await end({
          id,
          error: true,
          status: 'Judgment Failed',
          message: 'Failed to fetch submission details.',
        });
      }

      await sleep(1000);
      const { text } = await this.get(`/submission/${id}`);
      const {
        window: { document },
      } = new JSDOM(text);
      const find = (content: string) =>
        Array.from(
          document.querySelectorAll('.panel-heading>.panel-title')
        ).find(n => n.innerHTML === content).parentElement.parentElement
          .children[1];
      if (text.includes('Compile Error')) {
        return await end({
          error: true,
          id,
          status: 'Compile Error',
          message: find('详细').children[0].innerHTML,
        });
      }

      await next({});

      const summary = document.querySelector('tbody>tr');
      if (!summary) continue;
      const time = parseTimeMS(summary.children[4].innerHTML);
      const memory = parseMemoryMB(summary.children[5].innerHTML) * 1024;
      let panel = document.getElementById(
        'details_details_accordion_collapse_subtask_1'
      );
      if (!panel) {
        panel = document.getElementById('details_details_accordion');
        if (!panel) continue;
      }

      if (document.querySelector('tbody').innerHTML.includes('Judging'))
        continue;

      const score = +summary.children[3]?.children[0]?.innerHTML || 0;
      const status = score === 100 ? 'Accepted' : 'Unaccepted';

      return await end({
        id,
        status,
        score,
        time,
        memory,
      });
    }
  }
}
