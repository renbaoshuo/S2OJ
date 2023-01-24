const TIME_RE = /^([0-9]+(?:\.[0-9]*)?)([mu]?)s?$/i;
const TIME_UNITS = { '': 1000, m: 1, u: 0.001 };
const MEMORY_RE = /^([0-9]+(?:\.[0-9]*)?)([kmg])b?$/i;
const MEMORY_UNITS = { k: 1 / 1024, m: 1, g: 1024 };

export function parseTimeMS(str: string | number, throwOnError = true) {
  if (typeof str === 'number' || Number.isSafeInteger(+str)) return +str;
  const match = TIME_RE.exec(str);
  if (!match && throwOnError) throw new Error(`${str} error parsing time`);
  if (!match) return 1000;
  return Math.floor(parseFloat(match[1]) * TIME_UNITS[match[2].toLowerCase()]);
}

export function parseMemoryMB(str: string | number, throwOnError = true) {
  if (typeof str === 'number' || Number.isSafeInteger(+str)) return +str;
  const match = MEMORY_RE.exec(str);
  if (!match && throwOnError) throw new Error(`${str} error parsing memory`);
  if (!match) return 256;
  return Math.ceil(parseFloat(match[1]) * MEMORY_UNITS[match[2].toLowerCase()]);
}
