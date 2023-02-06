FROM node:18.13.0

WORKDIR /opt/s2oj_remote_judger
COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

ENV LANG=C.UTF-8 TZ="Asia/Shanghai"
CMD [ "node", "--experimental-specifier-resolution=node", "dist/entrypoint.js" ]
