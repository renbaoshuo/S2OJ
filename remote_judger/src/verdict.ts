export function normalize(key: string) {
  return key.toUpperCase().replace(/ /g, '_');
}

export const VERDICT = new Proxy<Record<string, string>>(
  {
    RUNTIME_ERROR: 'Runtime Error',
    WRONG_ANSWER: 'Wrong Answer',
    OK: 'Accepted',
    COMPILING: 'Compiling',
    TIME_LIMIT_EXCEEDED: 'Time Limit Exceeded',
    MEMORY_LIMIT_EXCEEDED: 'Memory Limit Exceeded',
    IDLENESS_LIMIT_EXCEEDED: 'Idleness Limit Exceeded',
    ACCEPTED: 'Accepted',
    PRESENTATION_ERROR: 'Wrong Answer',
    OUTPUT_LIMIT_EXCEEDED: 'Output Limit Exceeded',
    EXTRA_TEST_PASSED: 'Accepted',
    COMPILE_ERROR: 'Compile Error',
    'RUNNING_&_JUDGING': 'Judging',

    // Codeforces
    'HAPPY_NEW_YEAR!': 'Accepted',
  },
  {
    get(self, key) {
      if (typeof key === 'symbol') return null;
      key = normalize(key);
      if (self[key]) return self[key];
      return null;
    },
  }
);
