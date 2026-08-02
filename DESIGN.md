# Epay Design System

> 项目界面的唯一设计基准。新增或改造页面先遵守本文件，再实现具体业务；组件实现必须继续使用仓库内的原生 shadcn/ui 组件与 Tailwind 工具类。
>
> 本规范参考 [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 的 DESIGN.md 结构，并结合其中的 [Stripe](https://github.com/VoltAgent/awesome-design-md/tree/main/design-md/stripe)、[Vercel](https://github.com/VoltAgent/awesome-design-md/tree/main/design-md/vercel)、[Wise](https://github.com/VoltAgent/awesome-design-md/tree/main/design-md/wise) 分析，按彩虹易支付的支付运营场景重新取舍。外部品牌只提供方法与启发，不复制其商标、文案或完整视觉身份。

## Visual Theme & Atmosphere

彩虹易支付的界面要让用户感到「清晰、可信、可操作」：

- 交易与运营页面优先使用中性浅色画布、清晰的卡片层级和低噪声边框；深色区域只用于明确的导航/品牌对比，不把装饰放在关键表单旁边。
- 首页可以使用很轻的渐变或几何氛围作为品牌层，登录、收银台、支付状态和结算确认页保持克制，优先保证金额、状态和下一步动作。
- 信息密度按任务分层：营销首页有呼吸感，工作台有稳定的网格，支付表单只保留完成付款所需的内容。
- 所有成功、失败、待处理状态都必须有文字或图标辅助，不能只用颜色表达。

## Color Palette & Roles

`frontend/src/index.css` 的 shadcn 语义 token 是唯一运行时来源。组件中使用 token，不直接写新的一次性颜色。

| Role | Token | Epay 用法 |
| --- | --- | --- |
| Canvas | `--background` | 页面主画布 |
| Surface | `--card` / `--popover` | 卡片、弹层、表单容器 |
| Ink | `--foreground` / `--card-foreground` | 正文、金额、标题 |
| Primary action | `--primary` / `--primary-foreground` | 登录、提交、支付等当前主动作；每个区块最多一个视觉主 CTA |
| Secondary | `--secondary` / `--secondary-foreground` | 次要操作、柔和标签 |
| Muted | `--muted` / `--muted-foreground` | 辅助说明、占位信息、背景分区 |
| Hairline | `--border` / `--input` | 卡片和控件边框，保持 1px |
| Focus | `--ring` | 键盘焦点和错误定位 |
| Success | `--chart-2` 或语义化绿色工具类 | 支付成功、渠道正常；不要拿品牌主色代替成功色 |
| Destructive | `--destructive` | 删除、退款、失败和高风险操作 |

规则：

- 正文不能使用低对比度的品牌色；在深色背景上使用对应的 `*-foreground` token。
- 金额、订单号、统计数字使用 `font-variant-numeric: tabular-nums`，保证纵向对齐。
- 旧 PHP 页面仍会加载 Bootstrap；新壳内的颜色和字号必须限定在 `#epay-react-root`，不要用全局规则污染旧页面。

## Typography Rules

字体使用项目已经打包的 **Geist Variable**，回退到 `system-ui, -apple-system, "Segoe UI", sans-serif`。中文不强行使用英文品牌字体。

| Role | Mobile | Desktop | Weight | Line height |
| --- | ---: | ---: | ---: | ---: |
| Hero / page opener | 36px | 48–56px | 600 | 1.05–1.15 |
| Section title | 24px | 28–32px | 600 | 1.15–1.25 |
| Card title | 20–24px | 24px | 600 | 1.25 |
| Body | 14–16px | 14–16px | 400 | 1.5–1.75 |
| Label / control | 14px | 14px | 500 | 1.4 |
| Caption | 12–13px | 12–13px | 400 | 1.4 |
| Money / numeric | 跟随上下文 | 跟随上下文 | 500–600 | `tabular-nums` |

- 标题使用句首大写/自然中文句式，不使用无意义的全大写装饰。
- 展示标题可使用轻微负字距，但不能压缩中文可读性；正文和表格保持正常字距。
- 页面必须通过层级、间距和字重组织内容，不能依赖大量阴影或颜色。

## Layout Principles

- 基础间距采用 4px/8px 节奏：常用 `p-4`、`p-5`、`p-6`、`p-8`，区块间距优先 `gap-4`、`gap-6`、`gap-8`。
- 公共首页内容容器最大约 1200px；工作台内容容器最大约 1440px；认证/支付表单使用 `w-full` 加 `max-w-md` 或业务规定的窄列。
- 卡片内部边距桌面 24–32px，手机 16–24px；卡片宽度不能因固定像素挤出视口。
- 网格先定义最小可用列宽，再用 `minmax(0, 1fr)`；长表格进入 `.table-responsive`，不要让整个页面横向滚动。
- 页面主体必须允许内容自然变高，不能用固定高度裁切错误提示、验证码或辅助说明。

### Responsive Breakpoints

以内容能力而不是设备名称决定断点：

| Range | Layout behavior |
| --- | --- |
| `< 640px` | 单列；隐藏非必要侧栏；导航进入 shadcn `Sheet`；按钮和输入控件全宽或可换行 |
| `640–767px` | 单列增强；卡片保留两侧 16–24px 安全边距；操作组允许换行 |
| `768–1023px` | 平板双列/两栏内容可用，但不强制显示营销侧栏；表格保持容器内滚动 |
| `>= 1024px` | 认证页恢复品牌侧栏；工作台使用稳定的侧栏 + 内容网格；公共首页使用左右分栏 |
| `>= 1440px` | 增加留白，不无限放大正文或表单；装饰可延展到边缘 |

硬性响应式验收：

- 390×844、768×1024、1024×768、1440×900 至少各验一次。
- `document.documentElement.scrollWidth === window.innerWidth`，除明确的表格滚动区外，不允许横向溢出。
- 手机登录卡片、支付金额键盘、确认按钮必须可见并能在首屏完成主要操作；内容超出时只能纵向滚动。
- 触控目标不小于 44×44px；输入框高度不小于 40px；焦点环不能被 `overflow: hidden` 截断。
- 断点切换不应改变数据、表单字段 name/id、旧 PHP 回调或支付协议行为。

## Elevation & Shapes

- 默认使用 1px hairline 边框 + 极轻阴影；只有弹层、浮动面板和重点支付卡允许更高层级阴影。
- 卡片使用 `rounded-xl` / `rounded-2xl`；标签和状态使用 `rounded-full`；不要为每个元素叠加不同的圆角。
- CTA 使用 `rounded-xl`，营销标签可以使用 pill；支付键盘按钮保持足够的按压面积和清晰的按下状态。
- 装饰圆环、渐变和模糊只能服务层级，不得盖住文字、金额、二维码或表单。

## Component Standards

新增 UI 必须优先组合现有 shadcn/ui：

- 操作：`Button`、`ToggleGroup`、`DropdownMenu`、`Sheet`、`Dialog`。
- 表单：`Input`、`Textarea`、`Label`、`Checkbox`、`Select`、`RadioGroup`。
- 信息容器：`Card`、`Badge`、`Separator`、`Table`、`Tabs`、`Alert`。
- 不手工仿造 shadcn 组件，不引入第二套按钮、卡片、弹层或表单语义。
- 保留旧 PHP 表单的 `name`、`id`、提交地址、回调和支付协议；通过 `#epay-react-legacy-slot` 的适配层调整外观，不重写业务脚本。
- `Brand`、认证卡、支付键盘等复合模式应继续复用原子组件，不复制一份“看起来像”的 HTML/CSS。

### State & Accessibility

- 每个可操作控件具备默认、hover、focus-visible、pressed、disabled、loading、error（适用时）状态。
- 表单错误紧邻字段，使用 `aria-describedby`；图标按钮必须有 `aria-label`。
- 颜色对比至少达到 WCAG AA；键盘用户可以访问登录、支付、导航和弹层全部路径。
- 尊重 `prefers-reduced-motion`；动画不能成为完成支付或登录的必要条件。

## Do's and Don'ts

### Do

- 先画信息层级，再选组件；把金额、状态、主动作放在用户视线和拇指可达区域。
- 使用 token、原生 shadcn 组件和 Tailwind 响应式工具类。
- 在桌面和手机都检查真实文本长度、中文换行、按钮可点击区域和滚动宽度。
- 对数字和金额启用等宽数字；对长订单号、密钥和表格提供可读的截断/复制策略。
- 用截图 + DOM 指标复核首屏比例、居中、溢出和对比度。

### Don't

- 不用全局 `html/body/h1/p` 样式覆盖旧页面或 shadcn 设计系统。
- 不把固定 `width`、固定高度、负 margin 当作响应式方案。
- 不用 emoji、随意渐变或随机颜色代替图标、状态 token 和组件状态。
- 不让营销装饰挤压支付表单；不把次要说明做成比主动作更醒目的按钮。
- 不修改 `assets/vendor` 或手写一套 Radix/shadcn 的替代组件。

## Visual QA Checklist

每次 UI 变更至少记录：

1. 入口和真实旧 CSS 加载顺序（Bootstrap、主题 CSS、`epay-ui.css`）。
2. 390×844、768×1024、1024×768、1440×900 的截图。
3. 根字号、主要容器/卡片矩形、关键控件最小尺寸和横向滚动宽度。
4. 登录、注册、找回密码、首页、收款页、支付状态、结算确认等相邻入口是否仍能沿原路径跳转。
5. 仅在视觉与 DOM 验收通过后，再进行 PHP lint、前端 typecheck/build 和 Git 交付。

## Agent Prompt Guide

实现新页面时使用以下约束：

> 使用 Epay 的 `DESIGN.md` 和现有 shadcn/ui 组件。保持 Geist + 中性 token 的可信支付运营风格，使用 4/8px 间距、低噪声边框、清晰的金额/状态层级。所有布局必须在 390、768、1024、1440 宽度下响应式工作，不出现页面横向溢出。保留旧 PHP 表单协议与 DOM hooks；不要手工仿造 shadcn 组件，不新增第二套视觉语言。完成后提供截图和 DOM 指标证明卡片比例、内容居中、触控尺寸、键盘焦点和断点折叠正确。
