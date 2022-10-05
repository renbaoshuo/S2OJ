#include "testlib.h"

using namespace std;

const string YES = "YES";
const string NO = "NO";

int main(int argc, char* argv[]) {
    setName("compare ordered sequences of YES or NO (case insensetive)");

    registerTestlibCmd(argc, argv);

    int n = 0;

    while (!ans.seekEof() && !ouf.seekEof()) {
        n++;
        std::string ja = upperCase(ans.readWord());
        std::string pa = upperCase(ouf.readWord());

        if (ja != YES && ja != NO)
            quitf(_fail, "%d%s differ - %s or %s expected, but %s found", n, englishEnding(n).c_str(), YES.c_str(), NO.c_str(), compress(ja).c_str());

        if (pa != YES && pa != NO)
            quitf(_pe, "%d%s differ - %s or %s expected, but %s found", n, englishEnding(n).c_str(), YES.c_str(), NO.c_str(), compress(pa).c_str());

        if (ja != pa)
            quitf(_wa, "%d%s differ - expected: '%s', found: '%s'", n, englishEnding(n).c_str(), vtos(ja).c_str(), vtos(pa).c_str());
    }

    int extraInAnsCount = 0;

    while (!ans.seekEof()) {
        ans.readToken();
        extraInAnsCount++;
    }

    int extraInOufCount = 0;

    while (!ouf.seekEof()) {
        ouf.readToken();
        extraInOufCount++;
    }

    if (extraInAnsCount > 0)
        quitf(_wa, "Answer contains longer sequence [length = %d], but output contains %d elements", n + extraInAnsCount, n);

    if (extraInOufCount > 0)
        quitf(_wa, "Output contains longer sequence [length = %d], but answer contains %d elements", n + extraInOufCount, n);

    quitf(_ok, "%d token(s)", n);
}
