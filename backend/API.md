# API 参考文档

> 最后更新: 2026-05-24

## 基础信息

- **Base URL**: `http://localhost:8000`
- **API 前缀**: `/api/v1`
- **认证方式**: Session（Laravel 内置 `auth` 中间件）
- **请求头**: `Content-Type: application/json`, `Accept: application/json`, `X-Requested-With: XMLHttpRequest`
- **响应格式**: JSON

### 通用响应结构

**成功响应**（单条）:
```json
{
  "data": { ... }
}
```

**成功响应**（列表）:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

**错误响应**:
```json
{
  "message": "错误描述",
  "errors": {
    "field_name": ["验证错误信息"]
  }
}
```

### HTTP 状态码

| 状态码 | 含义 |
|--------|------|
| 200 | 成功 |
| 201 | 创建成功 |
| 204 | 删除成功（无内容） |
| 401 | 未认证 |
| 409 | 冲突（如有进行中任务无法删除） |
| 422 | 验证失败 |

---

## Agent API

### 列表 `GET /api/v1/agents`

查询参数:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 筛选: `online`, `offline`, `busy` |
| per_page | int | 否 | 每页条数（默认 15，最大 100） |

响应:
```json
{
  "data": [
    {
      "id": 1,
      "name": "代码助手",
      "description": "擅长代码生成",
      "capabilities": ["code", "review"],
      "status": "online",
      "max_capacity": 3,
      "current_tasks": 0,
      "is_available": true,
      "last_heartbeat_at": "2026-05-24T10:00:00Z",
      "metadata": {},
      "created_at": "2026-05-24T10:00:00Z",
      "updated_at": "2026-05-24T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

### 详情 `GET /api/v1/agents/{id}`

响应包含 `tasks_count` 字段（loadCount）。

### 创建 `POST /api/v1/agents`

请求体:

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 唯一，最大 255 字符 |
| description | string | 否 | 最大 1000 字符 |
| capabilities | array | 否 | 能力标签数组，如 `["code", "review"]` |
| max_capacity | int | 否 | 最大并发任务数（默认 5，范围 1-100） |
| metadata | object | 否 | 扩展字段 |

> 创建时默认 status 为 `offline`。

### 更新 `PUT /api/v1/agents/{id}`

字段同创建，均为可选。

### 删除 `DELETE /api/v1/agents/{id}`

软删除。如果 Agent 有待处理或进行中的任务，返回 409。

### 心跳 `POST /api/v1/agents/{id}/heartbeat`

标记 Agent 为 online 并更新时间戳。供 Agent Worker 进程调用。

### 上线 `POST /api/v1/agents/{id}/online`

将 Agent 设为 online 状态并记录心跳时间。

```json
{
  "message": "Agent 已上线",
  "data": { /* AgentResource */ }
}
```

### 下线 `POST /api/v1/agents/{id}/offline`

将 Agent 设为 offline，释放其正在处理的所有任务（回退为 pending），重置 current_tasks 为 0。

```json
{
  "message": "Agent 已下线，正在处理的任务已释放",
  "data": { /* AgentResource */ }
}
```

---

## Task API

### 列表 `GET /api/v1/tasks`

查询参数:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 筛选: `pending`, `processing`, `completed`, `failed`, `cancelled` |
| type | string | 否 | 筛选: `code`, `analysis`, `design`, `review`, `other` |
| priority | string | 否 | 筛选: `low`, `medium`, `high`, `urgent` |
| per_page | int | 否 | 每页条数（默认 15，最大 100） |

响应:
```json
{
  "data": [
    {
      "id": 1,
      "title": "写一个 Hello World",
      "description": "用 Python 打印 Hello World",
      "type": "code",
      "priority": "high",
      "status": "pending",
      "assigned_agent_id": null,
      "agent": null,
      "messages": [],
      "started_at": null,
      "completed_at": null,
      "created_at": "2026-05-24T10:00:00Z",
      "updated_at": "2026-05-24T10:00:00Z"
    }
  ],
  "meta": { /* 分页信息 */ }
}
```

> 当 `agent` 被 eager load 时，包含 `id`, `name`, `status`, `current_tasks`, `max_capacity`, `is_available`。
> 当 `messages` 被 eager load 时，包含消息列表。

### 详情 `GET /api/v1/tasks/{id}`

### 创建 `POST /api/v1/tasks`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| title | string | 是 | 最大 255 字符 |
| description | string | 否 | |
| type | string | 否 | `code`, `analysis`, `design`, `review`, `other`（默认 `other`） |
| priority | string | 否 | `low`, `medium`, `high`, `urgent`（默认 `medium`） |
| auto_assign | bool | 否 | 是否自动指派 Agent（默认 false） |

> 当 `auto_assign=true` 时，自动调用 AgentAssignmentStrategy 选一个在线且有空余容量的 Agent 指派。

### 更新 `PUT /api/v1/tasks/{id}`

字段同创建，均为可选。

**特别注意**: 状态和指派变更请使用专用端点（`/assign`, `/status`），不要在 PUT 中直接修改。

### 删除 `DELETE /api/v1/tasks/{id}`

软删除。

### 指派 Agent `POST /api/v1/tasks/{id}/assign`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| agent_id | int | 是 | 要指派的 Agent ID |

执行流程:
1. 验证 Agent 可用（online + 有余量）
2. 将任务状态设为 `processing`，记录 `started_at`，设置 `assigned_agent_id`
3. Agent 的 `current_tasks` +1
4. 广播 `TaskStatusChanged` 事件
5. 写入系统消息

### 更新状态 `POST /api/v1/tasks/{id}/status`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 是 | 目标状态 |

**允许的状态流转**:

| 当前状态 | 允许流转到 |
|----------|-----------|
| pending | `processing`, `cancelled` |
| processing | `completed`, `failed` |
| completed, failed, cancelled | 不可变 |

状态变更时自动处理 Agent 任务计数增减。

### 自动指派 `POST /api/v1/tasks/{id}/auto-assign`

不传参，系统自动按策略选 Agent 指派。策略: 能力匹配（权重 100）+ 负载均衡（权重 1），取总分最高。

---

## Message API

### 列表 `GET /api/v1/tasks/{task_id}/messages`

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（默认 50，最大 200） |

按时间倒序排列（最新的在前）。

### 创建 `POST /api/v1/tasks/{task_id}/messages`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | 是 | 消息内容，最大 10000 字符 |
| type | string | 否 | `system`, `agent`, `user`, `error`（默认 `user`） |
| agent_id | int | 否 | 关联的 Agent ID |

**自动回复**: 当消息类型为 `user`，且任务状态为 `processing` 且有指派的 Agent 时，系统自动调用 `AgentWorkerService::processReply()` 触发 LLM 回复。

---

## Auth API

### 注册 `POST /api/v1/register`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | |
| email | string | 是 | 唯一邮箱 |
| password | string | 是 | 需和 password_confirmation 一致 |
| password_confirmation | string | 是 | |

返回 `{ user, token }`。

### 登录 `POST /api/v1/login`

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| email | string | 是 | |
| password | string | 是 | |

返回 `{ user, token }`。失败返回 401。

### 登出 `POST /api/v1/logout`

需认证。返回 `{ message: "已退出登录" }`。

---

## 页面路由

| 路径 | 方法 | 说明 | 认证 |
|------|------|------|------|
| `/` | GET | 欢迎页（已登录则跳转 Dashboard） | 公开 |
| `/login` | GET | 登录页 | 访客 |
| `/login` | POST | 登录提交 | 访客 |
| `/register` | GET | 注册页 | 访客 |
| `/register` | POST | 注册提交 | 访客 |
| `/logout` | POST | 登出 | 已登录 |
| `/dashboard` | GET | 仪表盘（统计 + 活动日志） | 已登录 |
| `/agents` | GET | Agent 列表页 | 已登录 |
| `/agents/create` | GET | 创建 Agent 页 | 已登录 |
| `/agents/{id}` | GET | Agent 详情页 | 已登录 |
| `/tasks` | GET | 任务列表页 | 已登录 |
| `/tasks/create` | GET | 创建任务页 | 已登录 |
| `/tasks/{id}` | GET | 任务详情页（含消息面板） | 已登录 |

---

## Artisan 命令

### `agents:work` — Agent Worker

自动拉取待处理任务，指派给在线 Agent，调用 LLM 处理，写回结果消息。

```bash
# 单次运行（默认，适合 scheduler）
php artisan agents:work --max=3

# 调度配置（routes/console.php）
Schedule::command('agents:work --max=3')->everyMinute()->withoutOverlapping();
```

| 选项 | 默认值 | 说明 |
|------|--------|------|
| `--max` | 3 | 本次最多处理的任务数 |

**Worker 流程**:
1. 查找 `online` 且有余量 (`current_tasks < max_capacity`) 的 Agent
2. 按能力匹配待处理任务（`type in capabilities`）
3. 指派任务 → 构建系统提示词 + 用户提示词 → 调 LLM → 写 agent 消息 → 标记完成
4. LLM 失败时标记 `failed`，写错误消息

### `agents:check-heartbeats` — 心跳检测

```bash
php artisan agents:check-heartbeats --timeout=120
```

| 选项 | 默认值 | 说明 |
|------|--------|------|
| `--timeout` | 120 | 心跳超时秒数 |

调度: 每分钟运行，超时 Agent 自动 offline。

### `tasks:check-timeouts` — 任务超时

```bash
php artisan tasks:check-timeouts --timeout=30
```

| 选项 | 默认值 | 说明 |
|------|--------|------|
| `--timeout` | 30 | 处理超时分钟数 |

调度: 每分钟运行，`processing` 超过 30 分钟的任务自动标记 `failed`。

---

## AI Agent Worker 配置

所有配置通过 `.env` 设置:

| 环境变量 | 默认值 | 说明 |
|----------|--------|------|
| `AGENT_LLM_API_KEY` | — | LLM API Key（必填） |
| `AGENT_LLM_BASE_URL` | `https://api.openai.com/v1` | API 端点（兼容 OpenAI 格式） |
| `AGENT_LLM_MODEL` | `gpt-4o-mini` | 模型名称 |
| `AGENT_LLM_MAX_TOKENS` | 4096 | 最大 tokens |
| `AGENT_LLM_TEMPERATURE` | 0.7 | 温度参数 |
| `AGENT_WORKER_MAX_TASKS` | 3 | 每次调度最多处理任务数 |

兼容的 LLM 提供商:

| 提供商 | 示例 BASE_URL |
|--------|--------------|
| OpenAI | `https://api.openai.com/v1` |
| OpenRouter | `https://openrouter.ai/api/v1` |
| Ollama (本地) | `http://localhost:11434/v1` |
| vLLM (自建) | `http://your-server:8000/v1` |
| 任何 OpenAI 兼容 API | 自定义 |

---

## 数据库模型

### agents

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| name | string | 名称（唯一） |
| description | text | 描述 |
| capabilities | json | 能力标签数组 |
| status | enum | `online`, `offline`, `busy` |
| max_capacity | int | 最大并发任务数（默认 5） |
| current_tasks | int | 当前任务数（默认 0） |
| last_heartbeat_at | timestamp | 最后心跳时间 |
| metadata | json | 扩展字段 |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | 软删除 |

### tasks

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| title | string | 标题 |
| description | text | 描述 |
| type | enum | `code`, `analysis`, `design`, `review`, `other` |
| priority | enum | `low`, `medium`, `high`, `urgent` |
| status | enum | `pending`, `processing`, `completed`, `failed`, `cancelled` |
| assigned_agent_id | bigint FK | 关联 agents.id |
| created_by | bigint FK | 关联 users.id |
| started_at | timestamp | 开始处理时间 |
| completed_at | timestamp | 完成/失败时间 |
| metadata | json | 扩展字段 |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | 软删除 |

### messages

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| task_id | bigint FK | 关联 tasks.id |
| agent_id | bigint FK | 关联 agents.id（可空） |
| type | enum | `system`, `agent`, `user`, `error` |
| content | text | 消息内容 |
| metadata | json | 扩展字段 |
| created_at | timestamp | |

> messages 表不包含 `updated_at`（`$timestamps = false`）。

---

## 状态流转图

```
Agent:  offline ──→ online ──→ busy ──→ online
              ←─────        ←────

Task:   pending ──→ processing ──→ completed
          │            │             failed
          │            │             cancelled
          └── cancelled └── failed
```

---

## 事件广播（WebSocket）

通过 Laravel Reverb 广播到私有频道 `tasks.{taskId}`。

| 事件 | 广播名 | 触发时机 |
|------|--------|----------|
| `TaskStatusChanged` | `TaskStatusChanged` | 任务状态变更（指派、完成、失败、取消） |
| `MessageSent` | `MessageSent` | 新消息发送 |

频道授权: `routes/channels.php` — 开发阶段允许所有已认证用户订阅。
