import fs from 'fs';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import prefix from 'superagent-prefix';
import Logger from '@/utils/logger';
import sleep from '@/utils/sleep';
import * as TIME from '@/utils/time';

proxy(superagent);

const logger = new Logger('daemon');

interface UOJConfig {
  server_url: string;
  judger_name: string;
  password: string;
}

interface UOJSubmission {
  id: number;
  problem_id: number;
  problem_mtime: number;
  content: any;
  status: string;
}

export default async function daemon(config: UOJConfig) {
  const agent = superagent
    .agent()
    .use(prefix(`${this.config.server_url}/judge`))
    .type('application/x-www-form-urlencoded')
    .serialize(data =>
      new URLSearchParams({
        judger_name: config.judger_name,
        password: config.password,
        ...data,
      }).toString()
    );

  while (true) {
    try {
      const { body, error } = await agent.post('/submit');

      if (error) {
        logger.error(error.message);

        await sleep(TIME.second);
      } else if (body === 'Nothing to judge') {
        await sleep(2 * TIME.second);
      } else {
        const data: UOJSubmission = JSON.parse(body);

        logger.info('Start judging', data.id);

        // TODO: judge
      }
    } catch (err) {
      logger.error(err);
      await sleep(TIME.second);
    }
  }
}
