import type { BasicProvider, IBasicProvider, RemoteAccount } from './interface';
import * as Time from './utils/time';
import Logger from './utils/logger';
import htmlspecialchars from './utils/htmlspecialchars';

const logger = new Logger('vjudge');

class AccountService {
  api: IBasicProvider;

  constructor(public Provider: BasicProvider, public account: RemoteAccount) {
    this.api = new Provider(account);
    this.main().catch(e =>
      logger.error(`Error occured in ${account.type}/${account.handle}`, e)
    );
  }

  async judge(
    id: number,
    problem_id: string,
    language: string,
    code: string,
    next,
    end
  ) {
    try {
      const rid = await this.api.submitProblem(
        problem_id,
        language,
        code,
        id,
        next,
        end
      );

      if (!rid) return;

      await this.api.waitForSubmission(problem_id, rid, next, end);
    } catch (e) {
      logger.error(e);

      await end({ error: true, status: 'Judgment Failed', message: e.message });
    }
  }

  async login() {
    const login = await this.api.ensureLogin();
    if (login === true) {
      logger.info(`${this.account.type}/${this.account.handle}: logged in`);
      return true;
    }
    logger.warn(
      `${this.account.type}/${this.account.handle}: login fail`,
      login || ''
    );
    return false;
  }

  async main() {
    const res = await this.login();
    if (!res) return;
    setInterval(() => this.login(), Time.hour);
  }
}

class VJudge {
  private p_imports: Record<string, any> = {};
  private providers: Record<string, AccountService> = {};

  constructor(private request: any) {}

  async importProvider(type: string) {
    if (this.p_imports[type]) throw new Error(`duplicate provider ${type}`);
    const provider = await import(`./providers/${type}`);

    this.p_imports[type] = provider.default;
  }

  async addProvider(type: string) {
    if (this.p_imports[type]) throw new Error(`duplicate provider ${type}`);
    const provider = await import(`./providers/${type}`);
    const account = provider.getAccountInfoFromEnv();

    if (!account) throw new Error(`no account info for ${type}`);

    this.p_imports[type] = provider.default;
    this.providers[type] = new AccountService(provider.default, account);
  }

  async judge(
    id: number,
    type: string,
    problem_id: string,
    language: string,
    code: string,
    judge_time: string,
    config
  ) {
    const next = async payload => {
      return await this.request('/submit', {
        'update-status': 1,
        fetch_new: 0,
        id,
        status:
          payload.status ||
          (payload.test_id ? `Judging Test #${payload.test_id}` : 'Judging'),
      });
    };

    const end = async payload => {
      if (payload.error) {
        return await this.request('/submit', {
          submit: 1,
          fetch_new: 0,
          id,
          result: JSON.stringify({
            status: 'Judged',
            score: 0,
            error: payload.status,
            details:
              '<div>' +
              `<info-block>ID = ${payload.id || 'None'}</info-block>` +
              `<error>${htmlspecialchars(payload.message)}</error>` +
              '</div>',
          }),
          judge_time,
        });
      }

      return await this.request('/submit', {
        submit: 1,
        fetch_new: 0,
        id,
        result: JSON.stringify({
          status: 'Judged',
          score: payload.score,
          time: payload.time,
          memory: payload.memory,
          details:
            payload.details ||
            '<div>' +
              `<info-block>REMOTE_SUBMISSION_ID = ${
                payload.id || 'None'
              }\nVERDICT = ${payload.status}</info-block>` +
              '</div>',
        }),
        judge_time,
      });
    };

    if (!config.remote_submit_type || config.remote_submit_type == 'bot') {
      if (!this.providers[type]) throw new Error(`No provider ${type}`);

      await this.providers[type].judge(
        id,
        problem_id,
        language,
        code,
        next,
        end
      );
    } else if (config.remote_submit_type == 'my') {
      if (!this.p_imports[type]) throw new Error(`No provider ${type}`);

      try {
        const provider = this.p_imports[type].constructFromAccountData(
          JSON.parse(config.remote_account_data)
        );

        const rid = await provider.submitProblem(
          problem_id,
          language,
          code,
          id,
          next,
          end
        );

        if (!rid) return;

        await provider.waitForSubmission(problem_id, rid, next, end);
      } catch (e) {
        logger.error(e);

        await end({
          error: true,
          status: 'Judgment Failed',
          message: e.message,
        });
      }
    } else {
      throw new Error(
        'Unsupported remote submit type: ' + config.remote_submit_type
      );
    }
  }
}

export async function apply(request: any) {
  const vjudge = new VJudge(request);

  await vjudge.addProvider('codeforces');
  await vjudge.addProvider('atcoder');
  await vjudge.addProvider('uoj');
  await vjudge.addProvider('loj');
  await vjudge.importProvider('luogu');

  return vjudge;
}
