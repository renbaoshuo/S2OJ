<img src="./web/images/logo_small.png" alt="Logo" width="100" height="100" align="right" />

# S2OJ

> 基于 Universal Online Judge 的在线测评系统。

## 特性

- 前后端全面更新为 Bootstrap 4 + PHP 7。
- 使用 Docker Compose 编排服务相关容器，省心省力、方便快捷，更加灵活。
- 各组成部分既可单点部署，也可分离部署；支持添加多个评测机。
- 题目搜索全局放置，任意页面均可快速到达。
- 所有题目从编译、运行到评分，都可以由出题人自定义。
- 引入 Extra Tests 和 Hack 机制，更加严谨、更有乐趣。
- 支持 OI/IOI/ACM 等比赛模式；比赛内设有提问区域。
- 博客功能，不仅可撰写图文内容，也可制作幻灯片。
- 支持赛后总结功能，从点滴反思中汲取奋进力量。
- 更细化的权限管理。
- 其他应校内训练需求而新增的功能。

## 文档

有关安装、管理、维护，可参阅：[https://universaloj.github.io/](https://universaloj.github.io/) 和 [https://vfleaking.github.io/uoj/](https://vfleaking.github.io/uoj/)。

## 部署

修改 `docker-compose.yml` 中的配置，然后执行：

```bash
docker-compose up -d
```

更新：

```bash
docker-compose pull
docker-compose up -d
```

## 开发

```bash
docker-compose -f docker-compose.development.yml up --build
```

## 感谢

- [vfleaking](https://github.com/vfleaking) 将 UOJ 代码 [开源](https://github.com/vfleaking/uoj)
- 向原项目或本项目贡献代码的人
- 给我们启发与灵感以及提供意见和建议的人

## 许可

```text
Universal Online Judge
Copyright (c) 2016-2022 vfleaking

S2OJ
Copyright (c) 2022-present Baoshuo
```

本项目采用 [AGPL-3.0](./LICENSE) 许可协议开源，在使用本项目的源代码时请遵守许可协议。
