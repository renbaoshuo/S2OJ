import { JSDOM } from 'jsdom';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import sleep from '../utils/sleep';
import mathSum from 'math-sum';
import { IBasicProvider, RemoteAccount } from '../interface';
import { normalize, VERDICT } from '../verdict';
import Logger from '../utils/logger';

proxy(superagent);
const logger = new Logger('remote/codeforces');

const langs_map = {
  C: {
    name: 'GNU GCC C11 5.1.0',
    id: 43,
    comment: '//',
  },
  'C++': {
    name: 'GNU G++14 6.4.0',
    id: 50,
    comment: '//',
  },
  'C++17': {
    name: 'GNU G++17 7.3.0',
    id: 54,
    comment: '//',
  },
  'C++20': {
    name: 'GNU G++20 11.2.0 (64 bit, winlibs)',
    id: 73,
    comment: '//',
  },
  Pascal: {
    name: 'Free Pascal 3.0.2',
    id: 4,
    comment: '//',
  },
  'Python2.7': {
    name: 'Python 2.7.18',
    id: 7,
    comment: '#',
  },
  Python3: {
    name: 'Python 3.9.1',
    id: 31,
    comment: '#',
  },
};

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    CODEFORCES_HANDLE,
    CODEFORCES_PASSWORD,
    CODEFORCES_ENDPOINT = 'https://codeforces.com',
    CODEFORCES_PROXY,
  } = process.env;

  if (!CODEFORCES_HANDLE || !CODEFORCES_PASSWORD) return null;

  const account: RemoteAccount = {
    type: 'codeforces',
    handle: CODEFORCES_HANDLE,
    password: CODEFORCES_PASSWORD,
    endpoint: CODEFORCES_ENDPOINT,
  };

  if (CODEFORCES_PROXY) account.proxy = CODEFORCES_PROXY;

  return account;
}

function parseProblemId(id: string) {
  const [, type, contestId, problemId] = id.startsWith('921')
    ? ['', '921', '01']
    : /^(|GYM)(\d+)([A-Z]+[0-9]*)$/.exec(id);
  if (type === 'GYM' && +contestId < 100000) {
    return [type, (+contestId + 100000).toString(), problemId];
  }
  return [type, contestId, problemId];
}

export default class CodeforcesProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    if (account.cookie) this.cookie = account.cookie;
    this.account.endpoint ||= 'https://codeforces.com';
  }

  cookie: string[] = [];
  csrf: string;

  get(url: string) {
    logger.debug('get', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .get(url)
      .set('Cookie', this.cookie)
      .set(
        'User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36 S2OJ/3.1.0'
      );
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  post(url: string) {
    logger.debug('post', url, this.cookie);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .post(url)
      .type('form')
      .set('Cookie', this.cookie)
      .set(
        'User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36 S2OJ/3.1.0'
      );
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

  tta(_39ce7: string) {
    let _tta = 0;
    for (let c = 0; c < _39ce7.length; c++) {
      _tta = (_tta + (c + 1) * (c + 2) * _39ce7.charCodeAt(c)) % 1009;
      if (c % 3 === 0) _tta++;
      if (c % 2 === 0) _tta *= 2;
      if (c > 0)
        _tta -=
          Math.floor(_39ce7.charCodeAt(Math.floor(c / 2)) / 2) * (_tta % 5);
      _tta = ((_tta % 1009) + 1009) % 1009;
    }
    return _tta;
  }

  async getCsrfToken(url: string) {
    const { text: html } = await this.get(url);
    const {
      window: { document },
    } = new JSDOM(html);
    if (document.body.children.length < 2 && html.length < 512) {
      throw new Error(document.body.textContent!);
    }
    const ftaa = this.getCookie('70a7c28f3de') || 'n/a';
    const bfaa = this.getCookie('raa') || this.getCookie('bfaa') || 'n/a';
    return [
      (
        document.querySelector('meta[name="X-Csrf-Token"]') ||
        document.querySelector('input[name="csrf_token"]')
      )?.getAttribute('content'),
      ftaa,
      bfaa,
    ];
  }

  get loggedIn() {
    return this.get('/enter').then(res => {
      const html = res.text;
      if (html.includes('Login into Codeforces')) return false;
      if (html.length < 1000 && html.includes('Redirecting...')) {
        logger.debug('Got a redirect', html);
        return false;
      }
      return true;
    });
  }

  async ensureLogin() {
    if (await this.loggedIn) return true;
    logger.info('retry normal login');
    const [csrf, ftaa, bfaa] = await this.getCsrfToken('/enter');
    const { header } = await this.get('/enter');
    if (header['set-cookie']) {
      this.cookie = header['set-cookie'];
    }
    const res = await this.post('/enter').send({
      csrf_token: csrf,
      action: 'enter',
      ftaa,
      bfaa,
      handleOrEmail: this.account.handle,
      password: this.account.password,
      remember: 'on',
      _tta: this.tta(this.getCookie('39ce7')),
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
    const programType = langs_map[lang] || langs_map['C++'];
    const comment = programType.comment;
    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }
    const [type, contestId, problemId] = parseProblemId(id);
    const [csrf, ftaa, bfaa] = await this.getCsrfToken(
      type !== 'GYM' ? '/problemset/submit' : `/gym/${contestId}/submit`
    );
    logger.debug(
      'Submitting',
      id,
      programType,
      lang,
      `(S2OJ Submission #${submissionId})`
    );
    // TODO: check submit time to ensure submission
    const { text: submit, error } = await this.post(
      `/${
        type !== 'GYM' ? 'problemset' : `gym/${contestId}`
      }/submit?csrf_token=${csrf}`
    ).send({
      csrf_token: csrf,
      action: 'submitSolutionFormSubmitted',
      programTypeId: programType.id,
      source: code,
      tabsize: 4,
      sourceFile: '',
      ftaa,
      bfaa,
      _tta: this.tta(this.getCookie('39ce7')),
      ...(type !== 'GYM'
        ? {
            submittedProblemCode: contestId + problemId,
            sourceCodeConfirmed: true,
          }
        : {
            submittedProblemIndex: problemId,
          }),
    });

    if (error) {
      end({
        error: true,
        status: 'Judgment Failed',
        message: 'Failed to submit code.',
      });

      return null;
    }

    const {
      window: { document: statusDocument },
    } = new JSDOM(submit);
    const message = Array.from(statusDocument.querySelectorAll('.error'))
      .map(i => i.textContent)
      .join('')
      .replace(/&nbsp;/g, ' ')
      .trim();

    if (message) {
      end({ error: true, status: 'Compile Error', message });
      return null;
    }

    const { text: status } = await this.get(
      type !== 'GYM' ? '/problemset/status?my=on' : `/gym/${contestId}/my`
    ).retry(3);
    const {
      window: { document },
    } = new JSDOM(status);
    this.csrf = document
      .querySelector('meta[name="X-Csrf-Token"]')
      .getAttribute('content');
    return document
      .querySelector('[data-submission-id]')
      .getAttribute('data-submission-id');
  }

  async waitForSubmission(problem_id: string, id: string, next, end) {
    let i = 0;

    while (true) {
      if (++i > 60) {
        return await end({
          id,
          error: true,
          status: 'Judgment Failed',
          message: 'Failed to fetch submission details.',
        });
      }

      await sleep(3000);
      const { body, error } = await this.post('/data/submitSource')
        .send({
          csrf_token: this.csrf,
          submissionId: id,
        })
        .retry(3);
      if (error) continue;
      if (body.compilationError === 'true') {
        return await end({
          id,
          error: 1,
          status: 'Compile Error',
          message: body['checkerStdoutAndStderr#1'],
        });
      }
      const time = mathSum(
        Object.keys(body)
          .filter(k => k.startsWith('timeConsumed#'))
          .map(k => +body[k])
      );
      const memory =
        Math.max(
          ...Object.keys(body)
            .filter(k => k.startsWith('memoryConsumed#'))
            .map(k => +body[k])
        ) / 1024;
      await next({ test_id: body.testCount });
      if (body.waiting === 'true') continue;
      const status =
        VERDICT[
          Object.keys(VERDICT).find(k => normalize(body.verdict).includes(k))
        ];
      return await end({
        id,
        status,
        score: status === 'Accepted' ? 100 : 0,
        time,
        memory,
      });
    }
  }
}
