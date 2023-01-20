FROM node:18.13.0

WORKDIR /opt/s2oj_remote_judger
COPY package*.json ./

RUN npm ci --only=production

COPY . .

RUN npm run build

CMD [ "node", "--experimental-specifier-resolution=node", "dist/entrypoint.js" ]
