# 🤖 AI Agent 任务派发与消息聚合平台

一个支持多 AI Agent 协作的任务派发与消息聚合 Web 平台。

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端 | Laravel 11 + PHP 8.3 |
| 前端 | Vue 3 + Vite + Inertia.js + Tailwind CSS v4 |
| 数据库 | PostgreSQL 16 |
| 缓存 | Redis 7 |
| 实时通信 | Laravel Reverb (WebSocket) |
| 容器化 | Docker + Docker Compose |

## 功能特性

- **Agent 管理**: 注册、配置、监控 AI Agent 状态
- **任务管理**: 创建、手动指派、状态流转 (待处理→进行中→已完成/失败)
- **消息聚合**: 按状态聚合任务消息，实时推送更新
- **实时通信**: 基于 Laravel Reverb 的 WebSocket 实时推送

## 快速开始

### 前置要求

- Docker & Docker Compose
- Git

### 安装步骤

1. 克隆仓库
```bash
git clone https://github.com/FengHanC/ai-agent-task-platform.git
cd ai-agent-task-platform
```

2. 配置环境变量
```bash
cp .env.example .env
# 编辑 .env 文件，配置数据库密码等
```

3. 启动服务
```bash
docker compose up -d
```

4. 运行数据库迁移
```bash
docker compose exec app php artisan migrate
```

5. 构建前端资源
```bash
docker compose run --rm node sh -c "cd /var/www/html && npm install && npm run build"
```

6. 访问应用
```
http://localhost:8000
```

## 项目结构

```
ai-agent-task-platform/
├── backend/                    # Laravel 后端
│   ├── app/
│   │   ├── Http/Controllers/   # 控制器
│   │   └── Models/             # Eloquent 模型 (Agent, Task, Message)
│   ├── database/migrations/    # 数据库迁移
│   ├── resources/
│   │   ├── js/
│   │   │   ├── Pages/          # Vue 页面组件
│   │   │   └── Layouts/        # Vue 布局组件
│   │   └── views/              # Blade 模板
│   └── routes/                 # 路由定义
├── docker/                     # Docker 配置
│   ├── Dockerfile              # PHP 8.3-FPM 镜像
│   ├── nginx/default.conf      # Nginx 配置
│   └── php/local.ini           # PHP 配置
├── docker-compose.yml          # Docker Compose 编排
└── .env.example                # 环境变量模板
```

## 数据库设计

### agents (AI Agent)
| 字段 | 类型 | 说明 |
|------|------|------|
| name | string | Agent 名称 |
| capabilities | jsonb | 能力标签数组 |
| status | enum | online/offline/busy |
| max_capacity | int | 最大并发任务数 |
| current_tasks | int | 当前任务数 |

### tasks (任务)
| 字段 | 类型 | 说明 |
|------|------|------|
| title | string | 任务标题 |
| type | enum | code/analysis/design/review/other |
| priority | enum | low/medium/high/urgent |
| status | enum | pending/processing/completed/failed/cancelled |
| assigned_agent_id | FK | 关联 Agent |

### messages (消息)
| 字段 | 类型 | 说明 |
|------|------|------|
| task_id | FK | 关联任务 |
| agent_id | FK | 关联 Agent |
| type | enum | system/agent/user/error |
| content | text | 消息内容 |

## 开发规范

### Git 提交规范
```
<type>(<scope>): <subject>

类型: feat / fix / docs / style / refactor / test / chore
示例: feat(task): 添加任务指派功能
```

### 分支策略
- `main` - 主分支，受保护
- `develop` - 开发分支
- `feature/*` - 功能分支

## 开发状态

当前处于 **Sprint 0 - 项目初始化** 阶段。

- [x] Docker 开发环境
- [x] Laravel 后端初始化
- [x] Vue 3 前端初始化
- [x] 数据库设计与迁移
- [ ] Agent CRUD API + 页面
- [ ] 任务管理 API + 页面
- [ ] 消息聚合面板
- [ ] 实时推送集成

## License

MIT
