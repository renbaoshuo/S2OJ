export default function sleep(timeout: number) {
  return new Promise(resolve => {
    setTimeout(() => resolve(true), timeout);
  });
}
