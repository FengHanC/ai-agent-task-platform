# AI Agent 任务派发平台 - 项目待办清单

> 最后更新: 2026-05-24

---

## 🏗 架构说明

当前使用 **Inertia.js 全栈架构**，Vue 前端代码在 `backend/resources/js/` 内，由 Laravel 直接渲染。这不是前后端分离架构。

**后续可考虑重构为前后端分离：** 将前端拆为独立 Vite 项目，使用 Vue Router + Axios + Laravel Sanctum（JWT 认证）。但建议待 MVP 功能稳定后再做。

---

## Sprint 0: 项目初始化 ✅

| # | 任务 | 负责人 | 状态 | 提交 |
|---|------|--------|------|------|
| 0.1 | Docker 开发环境 (PHP 8.3 + PG + Redis + Node) | Coordinator | ✅ | `7a9fe72` |
| 0.2 | Laravel 11 后端初始化 + Inertia.js + Reverb | Coordinator | ✅ | `67a96d0` |
| 0.3 | Vue 3 + Tailwind CSS v4 前端初始化 | Coordinator | ✅ | `6f3bf25` |
| 0.4 | 数据库设计 (agents/tasks/messages) + Eloquent 模型 | Coordinator | ✅ | `fe9f8f6` |
| 0.5 | 项目 README 文档 | Coordinator | ✅ | `f094294` |

---

## Sprint 1: Agent 管理功能 ✅

| # | 任务 | 负责人 | 状态 | PR |
|---|------|--------|------|-----|
| 1.1 | Agent CRUD API (Controller + Resource + Request) | Agent-B | ✅ | #1 |
| 1.2 | Agent 列表页 (状态筛选 + 表格展示) | Agent-F | ✅ | #2 |
| 1.3 | Agent 创建页 (表单 + 能力标签多选) | Agent-F | ✅ | #2 |
| 1.4 | Agent 详情页 | Agent-F | ✅ | #2 |
| 1.5 | Dashboard 添加 Agent 管理入口 | Agent-F | ✅ | #2 |
| 1.6 | 修复: 前后端集成问题 (路由 + API 路径) | Coordinator | ✅ | - |
| 1.7 | 修复: Agent 列表页白屏 (prop default) | Coordinator | ✅ | - |

---

## Sprint 2: 任务管理功能 ✅

| # | 任务 | 负责人 | 状态 | PR |
|---|------|--------|------|-----|
| 2.1 | Task CRUD API + 指派 + 状态流转 | Agent-B | ✅ | #3 |
| 2.2 | 任务列表页 (按状态分组 + 筛选) | Agent-F | ✅ | #4 |
| 2.3 | 任务创建页 (标题/描述/类型/优先级) | Agent-F | ✅ | #4 |
| 2.4 | 任务详情页 (指派 Agent + 状态操作) | Agent-F | ✅ | #4 |
| 2.5 | Dashboard 激活任务管理入口 | Agent-F | ✅ | #4 |
| 2.6 | AppLayout 导航栏新增任务管理 | Agent-F | ✅ | #4 |
| 2.7 | 修复: PR #3 review 修复 (8 项) | Agent-B | ✅ | - |

---

## Sprint 3: 消息聚合与实时推送 ✅

| # | 任务 | 负责人 | 状态 | 备注 |
|---|------|--------|------|------|
| 3.1 | 消息 API (MessageController + Resource) | Agent-B | ✅ | `965ad14` |
| 3.2 | WebSocket 广播事件 (TaskStatusChanged) | Agent-B | ⏳ | 后续补充 |
| 3.3 | 私有频道授权 (channels.php) | Agent-B | ⏳ | 后续补充 |
| 3.4 | 消息聚合面板组件 (MessagePanel.vue) | Agent-F | ✅ | `b6314b1` |
| 3.5 | 活动日志组件 (ActivityLog.vue) | Agent-F | ✅ | `b6314b1` |
| 3.6 | 任务详情页集成消息面板 | Agent-F | ✅ | `b6314b1` |
| 3.7 | Dashboard 集成活动日志 | Agent-F | ✅ | `b6314b1` |

---

## Sprint 4: 优化与完善 ⏳

| # | 任务 | 负责人 | 状态 | 优先级 |
|---|------|--------|------|--------|
| 4.1 | 用户认证系统 (登录/注册/登出) | Agent-B | ✅ | P0 |
| 4.2 | Agent 自动指派策略 (按能力 + 负载均衡) | Agent-B | ⏳ | P1 |
| 4.3 | WebSocket 前端实时集成 (Echo + Reverb) | Agent-F | ⏳ | P1 |
| 4.4 | 单元测试 (PHPUnit + Pest) | Agent-B | ⏳ | P1 |
| 4.5 | UI/UX 优化 (响应式 + Toast 通知) | Agent-F | ✅ | P2 |
| 4.6 | 国际化 (i18n 中英文切换) | Agent-F | ⏳ | P2 |
| 4.7 | CI/CD (GitHub Actions 自动测试 + 部署) | Coordinator | ⏳ | P2 |
| 4.8 | API 文档 (Swagger/OpenAPI) | Agent-B | ⏳ | P3 |
| 4.9 | 错误监控 (Sentry 集成) | Coordinator | ⏳ | P3 |

---

## Sprint 5: 高级功能 ⏳

| # | 任务 | 负责人 | 状态 | 优先级 |
|---|------|--------|------|--------|
| 5.1 | Agent 心跳检测 (自动上线/下线) | Agent-B | ⏳ | P1 |
| 5.2 | 任务超时自动失败 | Agent-B | ⏳ | P1 |
| 5.3 | 消息通知 (浏览器通知 + 邮件) | Agent-F | ⏳ | P2 |
| 5.4 | 数据统计面板 (任务完成率/Agent 效率) | Agent-F | ⏳ | P2 |
| 5.5 | 任务模板 (常用任务快速创建) | Agent-F | ⏳ | P3 |
| 5.6 | Webhook 回调 (任务状态变更通知外部系统) | Agent-B | ⏳ | P3 |

---

## Sprint 6: 架构优化 (未来计划)

| # | 任务 | 说明 | 优先级 |
|---|------|------|--------|
| 6.1 | 前后端拆分 | 前端独立为 Vite 项目，Vue Router 替代 Inertia | P3 |
| 6.2 | JWT 认证 | Laravel Sanctum Token 替代 Session | P3 |
| 6.3 | CORS 配置 | 前端独立部署时的跨域配置 | P3 |

---

## 总进度

```
Sprint 0 (初始化):       ████████████████████ 100% ✅
Sprint 1 (Agent 管理):   ████████████████████ 100% ✅
Sprint 2 (任务管理):     ████████████████████ 100% ✅
Sprint 3 (消息推送):     ████████████████████ 100% ✅
Sprint 4a (UI 优化):     ████████████████████ 100% ✅
Sprint 4b (用户认证):    ████████████████████ 100% ✅
Sprint 4c (其他优化):    ░░░░░░░░░░░░░░░░░░░░   0% ⏳
Sprint 5 (高级功能):     ░░░░░░░░░░░░░░░░░░░░   0% ⏳
```

**MVP 完成度: 80%** (核心功能 Agent + 任务管理 + 消息聚合已完成)
