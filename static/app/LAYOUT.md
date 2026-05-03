# TwimiSNS 前端壳布局与样式约定

供核对「行为类命名 / 响应式 / 挂载差异」是否与预期一致。

## 前缀与命名

- 前缀 **`ts-`**：Twimi shell 全局样式，与业务模板区分。
- **优先用语义/行为**，而非页面名：`ts-main-stack`（主栏宽度约束）、`ts-visible-desktop`（视口可见性）、`ts-stack-gap`（纵向间距），避免 `ts-home-*` 这类除非多处复用且有稳定语义才用业务名。
- **DOM `id`**：仍为稳定钩子（如 `ts-sidebar-left`），与 `aria-controls` 对齐；类名逐步偏行为化。

## 壳层结构（所有 SPA 路由共用）

1. **`ts-shell`**：根，含顶栏高度变量、`--ts-tabbar-h`（移动底栏）。
2. **`ts-topnav`**：顶栏（移动为主题色条 + 三列 grid；桌面为 flex + 中性背景）。
3. **`ts-layout`**：三栏网格（桌面）/ 单列 flex（移动）。
4. **`ts-layout-main`**：中间主栏，`main` 元素。
5. **`ts-layout-side--left`**：左栏（桌面：卡片壳 + `ts-side-nav`；移动：抽屉面板）。
6. **`ts-layout-side--right`**：右栏（桌面：**无卡片壳**，扁平文案；移动：排到主内容下方，备案/页脚）。

## 主内容宽度（减轻路由切换横向抖动）

- **`ts-thread`**：帖子详情等已有 `max-width: var(--tw-max)` + 居中。
- **`ts-main-stack`**：其它路由的根容器应与此同宽（`--tw-max`，默认 600px），与 `.ts-thread` 对齐。
- **`ts-stack-gap`**：主栏内多块内容纵向 `gap`（可选修饰）。
- **`html { scrollbar-gutter: stable; }`**：减少滚动条出现/消失导致的布局偏移。

## 响应式断点

| 条件 | 行为摘要 |
|------|----------|
| **`max-width: 960px`** | 三栏改单列；左栏变抽屉；右栏沉底；顶栏三列 grid `1fr auto 1fr`；底栏 `ts-app-tabbar`；`.ts-header-nav-desktop` / `.ts-visible-desktop` 隐藏。 |
| **`min-width: 961px`** | 三栏网格；顶栏 flex；**仅 `ts-layout-side--left` 使用卡片边框**；顶栏中性背景。 |
| **`prefers-color-scheme: dark`（移动顶栏）** | 顶栏可切换为深色渐变条（与浅色「主题色顶栏」二选一策略）。 |

## 「挂载」与「媒体查询」分工

- **仅靠 CSS 显示/隐藏**：例如桌面侧栏导航加 **`ts-visible-desktop`**，由 `max-width: 960px` 统一隐藏；不必在 JS 里按路由删节点。
- **按路由挂载 DOM**：`mountShell` 每次重建侧栏内容（`populateSidebars`），但左右栏**角色固定**；顶栏 `headerActions` 由路由显式传入（未传则为空）。
- **抽屉打开时**：提升 **`z-index`**（侧栏高于顶栏），遮罩全屏；与媒体查询正交。

## 右侧栏文案（弱视觉）

- **`ts-aside-note`** + **`ts-aside-kicker`** + **`ts-text-muted`**：站点简介，无框线。
- **`ts-aside-foot`** + **`ts-aside-foot-inner`**：备案 / Powered by，与 `.ts-site-footer-inner` 组合。

## 文案色

- **`ts-text-muted`**：次要正文（原 `ts-side-muted` 已合并进此命名）。

---

若你希望把某条改成「纯配置」（例如断点 960 改为 768），可在 `app.css` 内全局替换 `@media` 条件并同步本文档。
