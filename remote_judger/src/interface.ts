export interface RemoteAccount {
  type: string;
  cookie?: string[];
  handle: string;
  password: string;
  endpoint?: string;
  proxy?: string;
}

export type NextFunction = (body: Partial<any>) => void;

export interface IBasicProvider {
  ensureLogin(): Promise<boolean | string>;
  submitProblem(
    id: string,
    lang: string,
    code: string,
    submissionId: number,
    next: NextFunction,
    end: NextFunction
  ): Promise<string | void>;
  waitForSubmission(
    problem_id: string,
    id: string,
    next: NextFunction,
    end: NextFunction
  ): Promise<void>;
}

export interface BasicProvider {
  new (account: RemoteAccount): IBasicProvider;
}

export const USER_AGENT =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36 S2OJ/3.1.0';
