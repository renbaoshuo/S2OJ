#include <cstdio>
#include <algorithm>
#include <cmath>
#include <cstdlib>
#include <cstring>
#include <string>

int main() {
    int c = '?', last;
    std::string buf;
    while (true) {
        last = c;
        c = getchar();
        if (c == EOF) {
            if (last != '\n') {
                putchar('\n');
            }
            break;
        } else if (c == ' ' || c == '\r') {
            buf.push_back((char)c);
        } else {
            if (!buf.empty()) {
                if (c != '\n') {
                    printf("%s", buf.c_str());
                }
                buf.clear();
            }
            putchar(c);
        }
    }
    return 0;
}
