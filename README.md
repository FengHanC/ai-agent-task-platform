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

## 🏗 架构说明

> **当前架构：Inertia 全栈（Monolith）**
>
> 目前项目的 Vue 前端代码托管在 `backend/resources/js/` 目录下，由 Laravel 的 Inertia.js 中间件直接渲染页面。这是一种**全栈单库**架构——前后端代码在同一个仓库中，不需要独立的前端开发和部署流程。
>
> **这不是真正的前后端分离（SPA）架构**，但开发效率高，适合 MVP 快速迭代。后续可以考虑将前端拆分为独立的 Vite/Vue 项目，通过 API 通信实现前后端分离。

### 当前架构 vs 前后端分离

| 对比项 | 当前 (Inertia) | 前后端分离 |
|--------|---------------|-----------|
| 前端位置 | `backend/resources/js/` | 独立 `frontend/` 项目 |
| 路由 | Laravel 控制 | Vue Router |
| 数据通信 | Inertia 直连 | API + Axios/fetch |
| 认证方式 | Session | JWT / Sanctum Token |
| 部署 | 单点部署 | 可独立扩缩 |
| 开发效率 | ✅ 高，适合原型 | 需维护双端 |

## 功能特性

- **Agent 管理**: 注册、配置、监控 AI Agent 状态 (online/offline/busy)
- **任务管理**: 创建、手动指派、状态流转 (待处理→进行中→已完成/失败)
- **消息聚合**: 任务消息面板，支持系统/Agent/用户三类消息
- **活动日志**: Dashboard 展示最近任务活动记录
- **用户认证**: 登录 / 注册 / 登出（Session 认证）
- **移动端适配**: 响应式布局，移动端汉堡菜单 + 卡片布局
- **Toast 通知**: 全局操作成功/失败弹框提示
- **实时通信**: 基于 Laravel Reverb 的 WebSocket 广播（计划中）

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
├── backend/                        # Laravel 后端（含前端代码）
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/        # 控制器
│   │   │   │   ├── Api/            # API 控制器（前后端共用）
│   │   │   │   └── Auth/           # 认证控制器
│   │   │   └── Middleware/         # 中间件
│   │   └── Models/                 # Eloquent 模型 (Agent, Task, Message, User)
│   ├── bootstrap/app.php           # Laravel 应用配置
│   ├── database/migrations/        # 数据库迁移
│   ├── resources/
│   │   ├── js/                     # 🔵 Vue 前端代码
│   │   │   ├── Components/         #   组件 (Toast, MessagePanel, ActivityLog...)
│   │   │   ├── Layouts/            #   布局 (AppLayout)
│   │   │   ├── Pages/              #   页面 (Dashboard, Agents/*, Tasks/*, Auth/*)
│   │   │   └── composables/        #   组合式函数 (useToast)
│   │   └── views/                  # Blade 模板
│   └── routes/                     # 路由定义
│       ├── web.php                 #   页面路由 + 认证路由
│       └── api.php                 #   RESTful API
├── docker/                         # Docker 配置
│   ├── Dockerfile                  #   PHP 8.3-FPM 镜像
│   ├── nginx/default.conf          #   Nginx 配置
│   └── php/local.ini               #   PHP 配置
├── docker-compose.yml              # Docker Compose 编排
└── .env.example                    # 环境变量模板
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

## API 端点

### Agent API (`/api/v1/agents`)
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/agents | Agent 列表 (支持 ?status=online 筛选) |
| POST | /api/v1/agents | 创建 Agent |
| GET | /api/v1/agents/{id} | Agent 详情 |
| PUT | /api/v1/agents/{id} | 更新 Agent |
| DELETE | /api/v1/agents/{id} | 删除 Agent |

### Task API (`/api/v1/tasks`)
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/tasks | 任务列表 (支持 ?status=&type=&priority= 筛选) |
| POST | /api/v1/tasks | 创建任务 |
| GET | /api/v1/tasks/{id} | 任务详情 |
| PUT | /api/v1/tasks/{id} | 更新任务 |
| DELETE | /api/v1/tasks/{id} | 删除任务 |
| POST | /api/v1/tasks/{id}/assign | 指派 Agent (body: { agent_id }) |
| POST | /api/v1/tasks/{id}/status | 更新状态 (body: { status }) |

## 认证 API

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /login | 登录页 |
| POST | /login | 登录 (email + password) |
| GET | /register | 注册页 |
| POST | /register | 注册 (name + email + password) |
| POST | /logout | 登出 |

> 当前使用 Session 认证。后续如需前后端分离，可切换到 Laravel Sanctum Token 认证。

## 开发规范

### Git 提交规范
```
<type>(<scope>): <subject>

类型: feat / fix / docs / style / refactor / test / chore
示例: feat(task): 添加任务指派功能
```

### 分支策略
- `main` - 主分支，受保护
- `feature/*` - 功能分支（开发完成后合并到 main）

## 开发状态

> 详细待办清单请查看 [TODO.md](./TODO.md)

| Sprint | 内容 | 进度 |
|--------|------|------|
| Sprint 0 | 项目初始化 | 100% ✅ |
| Sprint 1 | Agent 管理 | 100% ✅ |
| Sprint 2 | 任务管理 | 100% ✅ |
| Sprint 3 | 消息聚合 | 70% 🟡 (前端就绪，后端待补) |
| Sprint 4a | UI/UX 优化 | 100% ✅ |
| Sprint 4b | 用户认证 | 100% ✅ |
| Sprint 4c | 其他优化 | 0% ⏳ |

### 已完成

- [x] **Docker 开发环境** - PHP 8.3 + PostgreSQL 16 + Redis 7 + Nginx
- [x] **Agent 管理** - CRUD API 及页面（列表/创建/详情）
- [x] **任务管理** - CRUD API + 指派 + 状态流转
- [x] **消息聚合面板** - MessagePanel 组件（消息展示/发送）
- [x] **活动日志** - ActivityLog 组件
- [x] **移动端适配** - 汉堡菜单 + 侧栏 + 卡片布局
- [x] **Toast 通知** - 全局操作弹框
- [x] **用户认证** - 登录 / 注册 / 登出

### 待进行

- [ ] **消息 API 后端** - MessageController + 发送消息路由
- [ ] **WebSocket 集成** - Laravel Reverb 实时推送
- [ ] **Agent 自动指派** - 按能力 + 负载均衡
- [ ] **单元测试** - PHPUnit + Pest
- [ ] **更多功能** - 心跳检测、数据统计、任务模板等

## License

MIT
