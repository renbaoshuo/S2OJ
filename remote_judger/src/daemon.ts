import fs from 'fs-extra';
import superagent from 'superagent';
import proxy from 'superagent-proxy';
import Logger from './utils/logger';
import sleep from './utils/sleep';
import * as TIME from './utils/time';
import { apply } from './vjudge';
import path from 'path';
import child from 'child_process';
import htmlspecialchars from './utils/htmlspecialchars';

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
  judge_time: string;
}

export default async function daemon(config: UOJConfig) {
  const request = (url: string, data = {}) =>
    superagent
      .post(`${config.server_url}/judge${url}`)
      .set('Content-Type', 'application/x-www-form-urlencoded')
      .send(
        Object.entries({
          judger_name: config.judger_name,
          password: config.password,
          ...data,
        })
          .map(
            ([k, v]) =>
              `${k}=${encodeURIComponent(
                typeof v === 'string' ? v : JSON.stringify(v)
              )}`
          )
          .join('&')
      );
  const vjudge = await apply(request);

  while (true) {
    try {
      const { text, error } = await request('/submit');

      if (error) {
        logger.error('/submit', error.message);

        await sleep(2 * TIME.second);
      } else if (text.startsWith('Nothing to judge')) {
        await sleep(TIME.second);
      } else {
        const data: UOJSubmission = JSON.parse(text);
        const { id, content, judge_time } = data;
        const config = Object.fromEntries(content.config);
        const tmpdir = `/tmp/s2oj_rmj/${id}/`;

        if (config.test_sample_only === 'on') {
          await request('/submit', {
            submit: 1,
            fetch_new: 0,
            id,
            result: JSON.stringify({
              status: 'Judged',
              score: 100,
              time: 0,
              memory: 0,
              details: '<info-block>Sample test is not available.</info-block>',
            }),
            judge_time,
          });

          await sleep(TIME.second);

          continue;
        }

        fs.ensureDirSync(tmpdir);

        let code = '';

        try {
          // Download source code
          logger.debug('Downloading source code.', id);
          const zipFilePath = path.resolve(tmpdir, 'all.zip');
          const res = request(`/download${content.file_name}`);
          const stream = fs.createWriteStream(zipFilePath);
          res.pipe(stream);
          await new Promise((resolve, reject) => {
            stream.on('finish', resolve);
            stream.on('error', reject);
          });

          // Extract source code
          logger.debug('Extracting source code.', id);
          const extractedPath = path.resolve(tmpdir, 'all');
          await new Promise((resolve, reject) => {
            child.exec(`unzip ${zipFilePath} -d ${extractedPath}`, e => {
              if (e) reject(e);
              else resolve(true);
            });
          });

          // Read source code
          logger.debug('Reading source code.', id);
          const sourceCodePath = path.resolve(extractedPath, 'answer.code');
          code = fs.readFileSync(sourceCodePath, 'utf-8');
        } catch (e) {
          await request('/submit', {
            submit: 1,
            fetch_new: 0,
            id,
            result: JSON.stringify({
              status: 'Judged',
              score: 0,
              error: 'Judgment Failed',
              details: `<error>Failed to download and extract source code.</error>`,
            }),
            judge_time,
          });

          logger.error(
            'Failed to download and extract source code.',
            id,
            e.message
          );

          fs.removeSync(tmpdir);

          await sleep(TIME.second);

          continue;
        }

        // Start judging
        logger.info('Start judging', id, `(problem ${data.problem_id})`);
        try {
          await vjudge.judge(
            id,
            config.remote_online_judge,
            config.remote_problem_id,
            config.answer_language,
            code,
            judge_time,
            config
          );
        } catch (err) {
          await request('/submit', {
            submit: 1,
            fetch_new: 0,
            id,
            result: JSON.stringify({
              status: 'Judged',
              score: 0,
              error: 'Judgment Failed',
              details: `<error>${htmlspecialchars(err.stack)}</error>`,
            }),
            judge_time,
          });

          logger.error('Judgment Failed.', id, err.message);

          fs.removeSync(tmpdir);

          continue;
        }

        fs.removeSync(tmpdir);

        await sleep(TIME.second);
      }
    } catch (err) {
      logger.error(err.message);

      await sleep(2 * TIME.second);
    }
  }
}
