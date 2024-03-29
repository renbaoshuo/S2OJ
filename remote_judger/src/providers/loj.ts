import superagent from 'superagent';
import proxy from 'superagent-proxy';
import { stripVTControlCharacters } from 'util';
import { crlf, LF } from 'crlf-normalize';
import sleep from '../utils/sleep';
import { IBasicProvider, RemoteAccount, USER_AGENT } from '../interface';
import Logger from '../utils/logger';
import { normalize, VERDICT } from '../verdict';
import htmlspecialchars from '../utils/htmlspecialchars';

proxy(superagent);
const logger = new Logger('remote/loj');

const LANGS_MAP = {
  C: {
    name: 'C (gcc, c11, O2, m64)',
    info: {
      language: 'c',
      compileAndRunOptions: {
        compiler: 'gcc',
        O: '2',
        m: '64',
        std: 'c11',
      },
    },
    comment: '//',
  },
  'C++03': {
    name: 'C++ (g++, c++03, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++03',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++11': {
    name: 'C++ (g++, c++11, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++11',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++': {
    name: 'C++ (g++, c++14, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++14',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++17': {
    name: 'C++ (g++, c++17, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++17',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'C++20': {
    name: 'C++ (g++, c++20, O2, m64)',
    info: {
      language: 'cpp',
      compileAndRunOptions: {
        compiler: 'g++',
        std: 'c++20',
        O: '2',
        m: '64',
      },
    },
    comment: '//',
  },
  'Python2.7': {
    name: 'Python (2.7)',
    info: {
      language: 'python',
      compileAndRunOptions: {
        version: '2.7',
      },
    },
    comment: '#',
  },
  Python3: {
    name: 'Python (3.10)',
    info: {
      language: 'python',
      compileAndRunOptions: {
        version: '3.10',
      },
    },
    comment: '#',
  },
  Java17: {
    name: 'Java',
    info: {
      language: 'java',
      compileAndRunOptions: {},
    },
    comment: '//',
  },
  Pascal: {
    name: 'Pascal',
    info: {
      language: 'pascal',
      compileAndRunOptions: {
        O: '2',
      },
    },
    comment: '//',
  },
};

const parse = (str: string) => crlf(str, LF);

export function getAccountInfoFromEnv(): RemoteAccount | null {
  const {
    LOJ_HANDLE,
    LOJ_TOKEN,
    LOJ_ENDPOINT = 'https://api.loj.ac/api',
    LOJ_PROXY,
  } = process.env;

  if (!LOJ_TOKEN) return null;

  const account: RemoteAccount = {
    type: 'loj',
    handle: LOJ_HANDLE,
    password: LOJ_TOKEN,
    endpoint: LOJ_ENDPOINT,
  };

  if (LOJ_PROXY) account.proxy = LOJ_PROXY;

  return account;
}

export default class LibreojProvider implements IBasicProvider {
  constructor(public account: RemoteAccount) {
    this.account.endpoint ||= 'https://api.loj.ac/api';
  }

  static constructFromAccountData(data) {
    return new this({
      type: 'loj',
      handle: data.username,
      password: data.token,
    });
  }

  get(url: string) {
    logger.debug('get', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .get(url)
      .auth(this.account.password, { type: 'bearer' })
      .set('User-Agent', USER_AGENT);
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  post(url: string) {
    logger.debug('post', url);
    if (!url.includes('//')) url = `${this.account.endpoint}${url}`;
    const req = superagent
      .post(url)
      .type('json')
      .auth(this.account.password, { type: 'bearer' })
      .set('User-Agent', USER_AGENT)
      .set('x-recaptcha-token', 'skip');
    if (this.account.proxy) return req.proxy(this.account.proxy);
    return req;
  }

  get loggedIn() {
    return this.get('/auth/getSessionInfo?token=' + this.account.password).then(
      res => res.body.userMeta && res.body.userMeta.id
    );
  }

  async ensureLogin() {
    if (await this.loggedIn) return true;
    logger.info('retry login');
    // TODO: login
    return false;
  }

  async getProblemId(displayId: number) {
    const { body } = await this.post('/problem/getProblem').send({ displayId });

    return body.meta.id;
  }

  async submitProblem(
    displayId: string,
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

    const programType = LANGS_MAP[lang] || LANGS_MAP['C++'];
    const comment = programType.comment;

    if (comment) {
      const msg = `S2OJ Submission #${submissionId} @ ${new Date().getTime()}`;
      if (typeof comment === 'string') code = `${comment} ${msg}\n${code}`;
      else if (comment instanceof Array)
        code = `${comment[0]} ${msg} ${comment[1]}\n${code}`;
    }

    const id = await this.getProblemId(parseInt(displayId));

    logger.debug(
      'Submitting',
      id,
      `(displayId: ${displayId})`,
      programType,
      lang,
      `(S2OJ Submission #${submissionId})`
    );

    await next({ status: 'Submitting to LibreOJ...' });

    const { body, error } = await this.post('/submission/submit').send({
      problemId: id,
      content: {
        code,
        ...programType.info,
      },
      uploadInfo: null,
    });

    if (error) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Failed to submit code.',
      });

      return null;
    }

    await next({ status: 'Submitted to LibreOJ' });

    return body.submissionId;
  }

  async ensureIsOwnSubmission(id: string) {
    const user_id = await this.get(
      '/auth/getSessionInfo?token=' + this.account.password
    ).then(res => res.body.userMeta?.id);

    if (!user_id) return false;

    const submission_user_id = await this.post(
      '/submission/getSubmissionDetail'
    )
      .send({ submissionId: String(id), locale: 'zh_CN' })
      .retry(3)
      .then(res => res.body.meta?.submitter.id);

    return user_id === submission_user_id;
  }

  async waitForSubmission(
    id: string,
    next,
    end,
    problem_id: string,
    result_show_source = false
  ) {
    if (!(await this.ensureLogin())) {
      await end({
        error: true,
        status: 'Judgment Failed',
        message: 'Login failed',
      });

      return null;
    }

    let count = 0;
    let fail = 0;

    while (count < 180 && fail < 10) {
      count++;
      await sleep(1000);

      try {
        const { body } = await this.post('/submission/getSubmissionDetail')
          .send({ submissionId: String(id), locale: 'zh_CN' })
          .retry(3);

        let files = [];

        if (body.content?.code) {
          files.push({
            name: 'answer.code',
            content: parse(body.content.code),
          });
        }

        if (body.meta.problem.displayId != problem_id) {
          return await end({
            id,
            error: true,
            status: 'Judgment Failed',
            message: 'Submission does not match current problem.',
            result: { files },
          });
        }

        if (!body.progress) {
          await next({
            status: 'Waiting for Remote Judge',
          });

          continue;
        }

        await next({
          status: `[LibreOJ] ${body.progress.progressType}`,
        });

        if (body.progress.progressType !== 'Finished') {
          continue;
        }

        const status =
          VERDICT[
            Object.keys(VERDICT).find(k =>
              normalize(body.meta.status).includes(k)
            )
          ];

        if (status === 'Compile Error') {
          await end({
            error: true,
            id,
            status: 'Compile Error',
            message: stripVTControlCharacters(body.progress.compile.message),
            result: { files },
          });
        }

        if (status === 'Judgment Failed') {
          await end({
            error: true,
            id,
            status: 'Judgment Failed',
            message: 'Error occurred on remote online judge.',
            result: { files },
          });
        }

        const getSubtaskStatusDisplayText = (testcases: any): string => {
          let result: string = null;

          for (const testcase of testcases) {
            if (!testcase.testcaseHash) {
              result = 'Skipped';

              break;
            } else if (
              body.progress.testcaseResult[testcase.testcaseHash].status !==
              'Accepted'
            ) {
              result =
                body.progress.testcaseResult[testcase.testcaseHash].status;

              break;
            }
          }

          result ||= 'Accepted';
          result =
            VERDICT[
              Object.keys(VERDICT).find(k => normalize(result).includes(k))
            ];

          return result;
        };

        const getTestcaseBlock = (id: string, index: number): string => {
          const testcase = body.progress.testcaseResult[id];

          if (!testcase) return '';

          const status =
            VERDICT[
              Object.keys(VERDICT).find(k =>
                normalize(testcase.status).includes(k)
              )
            ];
          let test_info = '';

          test_info += `<test num="${
            index + 1
          }" info="${status}" time="${Math.round(
            testcase.time || 0
          )}" memory="${testcase.memory}">`;

          if (testcase.input) {
            if (typeof testcase.input === 'string') {
              test_info += `<in>${parse(testcase.input)}</in>\n`;
            } else {
              test_info += `<in>${parse(testcase.input.data)}\n\n(${
                testcase.input.omittedLength
              } bytes omitted)</in>\n`;
            }
          }

          if (testcase.userOutput) {
            if (typeof testcase.userOutput === 'string') {
              test_info += `<out>${parse(testcase.userOutput)}</out>\n`;
            } else {
              test_info += `<out>${parse(testcase.userOutput.data)}\n\n(${
                testcase.userOutput.omittedLength
              } bytes omitted)</out>\n`;
            }
          }

          if (testcase.output) {
            if (typeof testcase.output === 'string') {
              test_info += `<ans>${parse(testcase.output)}</ans>\n`;
            } else {
              test_info += `<ans>${parse(testcase.output.data)}\n\n(${
                testcase.output.omittedLength
              } bytes omitted)</ans>\n`;
            }
          }

          if (testcase.checkerMessage) {
            if (typeof testcase.checkerMessage === 'string') {
              test_info += `<res>${parse(testcase.checkerMessage)}</res>\n`;
            } else {
              test_info += `<res>${parse(testcase.checkerMessage.data)}\n\n(${
                testcase.checkerMessage.omittedLength
              } bytes omitted)</res>\n`;
            }
          }

          test_info += '</test>';

          return test_info;
        };

        let details = '';

        details +=
          '<remote-result-container>' +
          '<remote-result-table>' +
          Object.entries({
            题目: `<a href="https://loj.ac/p/${body.meta.problem.displayId}">#${
              body.meta.problem.displayId
            }. ${htmlspecialchars(body.meta.problemTitle)}</a>`,
            提交记录: `<a href="https://loj.ac/s/${id}">${id}</a>`,
            提交时间: new Date(body.meta.submitTime).toLocaleString('zh-CN'),
            账号: `<a href="https://loj.ac/user/${body.meta.submitter.id}">${body.meta.submitter.username}</a>`,
            状态: status,
          })
            .map(
              o => `<remote-result-tr name="${o[0]}">${o[1]}</remote-result-tr>`
            )
            .join('') +
          '</remote-result-table>' +
          '</remote-result-container>';

        // Samples
        if (body.progress.samples) {
          details += `<subtask title="Samples" info="${getSubtaskStatusDisplayText(
            body.progress.samples
          )}" num="0">${body.progress.samples
            .map((item, index) =>
              item.testcaseHash
                ? getTestcaseBlock(item.testcaseHash, index)
                : `<test num="${index + 1}" info="Skipped"></test>`
            )
            .join('\n')}</subtask>`;
        }

        // Tests
        if (body.progress.subtasks.length === 1) {
          details += `<tests>${body.progress.subtasks[0].testcases
            .map((item, index) =>
              item.testcaseHash
                ? getTestcaseBlock(item.testcaseHash, index)
                : `<test num="${index + 1}" info="Skipped"></test>`
            )
            .join('\n')}</tests>`;
        } else {
          details += `<tests>${body.progress.subtasks
            .map(
              (subtask, index) =>
                `<subtask num="${
                  index + 1
                }" info="${getSubtaskStatusDisplayText(
                  subtask.testcases
                )}">${subtask.testcases
                  .map((item, index) =>
                    item.testcaseHash
                      ? getTestcaseBlock(item.testcaseHash, index)
                      : `<test num="${index + 1}" info="Skipped"></test>`
                  )
                  .join('\n')}</subtask>`
            )
            .join('\n')}</tests>`;
        }

        return await end({
          id,
          status: body.meta.status,
          score: body.meta.score,
          time: body.meta.timeUsed,
          memory: body.meta.memoryUsed,
          details: `<div>${details}</div>`,
          result: { files },
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
