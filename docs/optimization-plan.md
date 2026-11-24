# SuJian（素笺）Typecho 主题全面优化方案

> **适用版本**：SuJian `1.0.1`（目标环境：Typecho `1.3.0`，PHP `7.4+ / 8.x`）  
> **巡检依据**：结合本地站点 `http://sujian.itsse.cn` 前端实测（桌面 `1440×900` / 移动 `390×844` / 极窄 `350×844` / 深浅色模式）、Typecho 后台管理实测（主题设置页、文章编辑页、主题列表页）与主题全量源码审计整理。

> [!IMPORTANT]
> **修订说明（已复核）**：本方案已经逐条对照源码、Typecho 核心代码和浏览器复测做过可行性评审，详见 [optimization-plan-review.md](optimization-plan-review.md)。下文中的错误写法已直接改正（标记为「〔已修订〕」）。如果两份文档有冲突，以评审文档为准。

---

## 目录

1. [总体评估与核心目标](#一总体评估与核心目标)
2. [第一部分：前端 UI 与视觉交互整理](#二第一部分前端-ui-与视觉交互整理)
3. [第二部分：项目代码逻辑、性能、安全与工程整理](#三第二部分项目代码逻辑性能安全与工程整理)
4. [第三部分：Typecho 后台管理问题整理](#四第三部分typecho-后台管理问题整理)
5. [第四部分：分阶段实施路线图与验收清单](#五第四部分分阶段实施路线图与验收清单)

---

## 一、总体评估与核心目标

### 当前优势
- 前后台实测 **0 个控制台报错**，主页面在 `1440px` / `390px` / `350px` 视口下均为 **0px 横向溢出**。
- 文章页代码块支持容器内横向滚动，移动端导航抽屉与返回顶部按钮均符合 `44×44px` 触控热区标准。
- 后台外观配置已具备分组导航、可视化导航行编辑、Banner 渐变预览、友链行编辑与页脚变量插入等良好基础。

### 核心优化目标
| 维度 | 现状问题 | 优化目标 |
| :--- | :--- | :--- |
| **前端 UI / 对比度** | 主题下拉菜单白底白字/黑底黑字、全局 `svg` 破坏正文图表、嵌套评论错位、深色强调色对比度不足、友链裂图无兜底、短页面页脚悬空 | 全组件满足 WCAG AA（≥4.5:1），消除层叠冲突与视觉错位，深浅色/移动端/打印视图一致 |
| **前端资源体积** | 仅 4 个轻量控件却加载 ~480 KB（**未压缩**；gzip 后实测约 150 KB）JS/CSS + 首屏按钮闪烁 | 轻量化后总负载压至 `< 40 KB`（降幅 >90%），消除挂载闪烁 |
| **代码逻辑与安全** | 列表/SEO 双重 Markdown 解析、关闭原生反垃圾混淆明文泄 Token、混用 Typecho 1.1 废弃 API、查询分页 Canonical 丢失 | 单次纯文本提取、恢复原生反垃圾、全面适配 Typecho 1.3 与 PHP 8.x、SEO 结构化数据无冗余 |
| **后台管理体验** | 文章页缺少 `themeFields()`、关闭友链残留孤立标签、桌面端友链输入框过窄、分组导航不吸顶、相对路径被写死域名 | 补齐可视化文章字段、重构宽敞友链表单、导航吸顶、修正 URL 逻辑与文案错别字 |

---

## 二、第一部分：前端 UI 与视觉交互整理

### 1.1 〔已修订：降级为“待复现”〕主题切换下拉菜单切换/激活态不可读（白底白字 & 黑底黑字）
> 复测结论：稳定状态下高亮配色正常（深色 `#1c1c1f`/`#fafafa`，浅色 `#f4f4f5`/`#18181b`）。首次截图中的现象无法复现，下面写的“动画不同步”根因没有证据。仅作为观察项保留；选择方案 A 后此问题自然消失。

- **位置**：`src/components/ui/dropdown-menu.tsx:201`、`src/ui.css:23-24`、`style.css:88`
- **实测现象**：深色模式下激活「浅色」出现亮白底+白字；浅色模式下激活「深色」出现深黑底+深字。
- **根因**：`@base-ui/react` 菜单的关闭动画（`duration-100`）与 `style.css:88` 全局 `0.2s` 背景色过渡不同步，同时无层 `style.css` 与 Tailwind `@layer utilities` 存在层叠优先级冲突。
- **优化方案**：
  - **推荐（结合阶段 2 方案 A）**：改为原生轻量下拉菜单，切换主题时立即关闭菜单并同步色板变量，不再产生跨帧反色残留。
  - **若保留 React（方案 B）**：为 `[role="menuitemradio"]` 显式声明高优先级 `:hover` / `[data-highlighted]` / `:focus-visible` 的背景与前景色，并在切换主题时禁用菜单退出过渡动画。

### 1.2 【P0】全局 `svg` 样式破坏正文内联 SVG 并覆盖 Tailwind 工具类
- **位置**：`style.css:95`、`src/ui.css:44-58`
- **问题**：`svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: currentColor; ... }` 命中页面所有 `<svg>`，导致 `.markdown-body` 中的 Mermaid 图表、数学公式、自定义矢量插画或徽章全部被压缩成 `1.1rem` 线框；同时无层规则高于 `@layer utilities`，破坏组件内的 `size-3` / `size-4`。
- **优化方案**：
  - 删除裸 `svg { ... }` 规则，将图标默认样式精确限定在主题图标作用域（如 `.sujian-icon` 或 `.icon-button svg, .search-form svg, .archive-search > svg, .read-more svg, .empty-icon svg, .article-back-button svg`）。
  - 为 `.markdown-body svg` 保持 `max-width: 100%; height: auto;` 不干预其内部 `fill` / `stroke`。

### 1.3 【P0】嵌套评论样式冲突、原生元素缺失样式与 Gravatar 国内可用性
- **位置**：`style.css:311-319`、`comments.php:10`
- **问题**：
  - Typecho 默认输出的嵌套评论容器同样带有 `.comment-list`，导致子评论 `<li>` 同时命中 `.comment-list > li`（四周 1px 边框 + 圆角 + 卡片底色）与 `.comment-list ol li`（仅覆盖 `border-left: 2px` 和 `padding-right: 0`），出现左侧粗边框叠加其余三边细边框、右侧文字贴边的错位效果。
  - `.comment-author .avatar` 缺少尺寸与圆角；`<cite class="fn">` 保持浏览器默认斜体；`.comment-content` 文字色过淡（`var(--muted-foreground)`）且缺少段落、链接、行内代码与引用块样式；`.comment-awaiting-moderation` 完全未定义样式；回复链接移动端触控高度不足 `2.75rem`。
  - 默认 Gravatar 域名在国内常超时裂图。
- **优化方案**：
  - 修复选择器：将外层卡片限定在 `.comments-list > .comment-list > li`，并对 `.comment-children .comment-list > li` 重置 `border: 0; border-left: 2px solid var(--border); border-radius: 0; background: transparent; padding: .8rem 0 .2rem 1rem;`（或提供定制 `threadedComments()` 回调以彻底掌控 DOM 结构）。
  - 补全 `.comment-author .avatar { width: 2.25rem; height: 2.25rem; border-radius: 999px; object-fit: cover; flex-shrink: 0; }`、`cite.fn { font-style: normal; }`、`.comment-content { color: var(--foreground); }` 及内部排版、`.comment-awaiting-moderation` 提示徽标样式、移动端 `.comment-reply` 触控热区。
  - 在主题设置中增加「Gravatar 头像镜像源」配置项（支持留空走默认或填如 `https://cravatar.cn/avatar/`）。

### 1.4 【P0】深浅色模式 WCAG AA 对比度修复与暗色组件补齐
- **位置**：`style.css:21-74`、`style.css:120`、`style.css:276-277`、`src/components/ui/tooltip.tsx:52`
- **问题**：
  - 深色模式下仅 `zinc` 提亮了 `--primary`，而 `violet` (`#7c3aed`, ~3.7:1)、`blue` (`#4f46e5`, ~3.2:1)、`rose` (`#e11d48`, ~4.4:1) 在 `#09090b`/`#111113` 背景上均低于 WCAG AA `4.5:1`。
  - 浅色模式下 `--muted-foreground: #71717a` 在 `--muted: #f4f4f5` 背景上对比度约 `4.35:1`，小字号略淡。
  - 〔已修订：降为 P3〕实测默认 Logo 会铺满容器，看不到白底；只有上传透明背景 Logo 时，`.brand-mark` 的 `background: var(--foreground)` 才会露出白底。
  - 加密文章密码框 `.article-content .protected input[type="password"]` 缺失暗色 `background`/`color`，提交按钮缺 `cursor: pointer` 与悬停态。
  - 深色模式下按钮悬停 Tooltip 使用 `bg-foreground text-background` 形成突兀纯白块。
- **优化方案**：
  - 为暗色模式下对比度不足的强调色补充提亮色值（复测对比度：violet ≈3.3:1、blue ≈3.0:1、rose ≈4.0:1；emerald ≈5.0:1 **已达标，不用改**）。示例：`violet: #a78bfa`、`blue: #818cf8`、`rose: #fb7185`。〔已修订〕**必须同时**把这些强调色的 `--primary-foreground` 设为 `#18181b`，否则白字在 `#a78bfa` 上只有 ≈2.7:1，按钮和分页当前页的文字会看不清。
  - 浅色模式下将 `--muted-foreground` 微调深至 `#52525b` 或 `#5c5c66`（在 `#f4f4f5` 上 ≥5.5:1）。
  - `.brand-mark` 改为 `background: var(--muted); border: 1px solid var(--border);`（或深色下使用深灰底座）。
  - 补齐 `.article-content .protected input[type="password"]` 的 `background: var(--background); color: var(--foreground);` 及提交按钮 `cursor: pointer` / 悬停态。
  - 调整暗色模式 Tooltip 底色为深灰高对比卡片样式（`var(--popover)` + `var(--border)`），避免刺眼白块。

### 1.5 【P1】搜索弹窗控件对齐、友链裂图兜底与短页面粘性页脚
- **搜索弹窗对齐**〔已修订：不成立，删除〕：复测输入框和提交按钮都是 `44px`（`.ui-submit` 没有放进 `@layer`，所以覆盖了 `h-8`），不需要修改。
- **友链裂图兜底**（`page-friends.php:24`）：
  - 实测外链头像加载失败时呈现 `48×48` 空白区域。补充 `onerror` 回退至主题默认头像（或内联 SVG 占位图），并防止死循环（`this.onerror=null; this.src='...';`）。
- **短页面粘性页脚**（`style.css:78-87`）：
  - 实测 `/friends.html` 与 `404` 页在 `1440×900` 下底部留白 ~360px。
  - 为 `body` 增加 `min-height: 100dvh; display: flex; flex-direction: column;`，为 `main` 增加 `flex: 1 0 auto; width: 100%;`（保持 `.container` 居中宽度），确保页脚始终沉底。

### 1.6 【P1】响应式边界、正文排版统一与样式清理
- **极窄屏搜索图标错位**（`style.css:226, 385-389`）：
  - `350px` 实测确认 `.archive-search` 转为纵向堆叠后，绝对定位放大镜图标仍相对整个容器垂直居中。改为设置 `top: 1.35rem; transform: translateY(-50%);`（锚定首行输入框中线）或用独立包裹层包裹 `<input>` 与 `<svg>`。
- **平板顶栏拥挤与副标题撑高**（`style.css:116-127`、`header.php:233`）：
  - `721px–900px` 区间收紧 `.header-inner` 的 `gap` 与导航项 `padding`，为 `.brand-copy small` 添加单行省略（`white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 14rem;`）。
- **正文 `ghmd.css` 风格统一与打印样式**（`ghmd.css:13-48`）：
  - 将 `ghmd.css` 中硬编码的 GitHub 蓝灰暗色（`#151b23`、`#3d444d`、`#4493f8`）替换为 SuJian CSS 变量（`--muted`、`--border`、`--primary`、`--foreground`），并继承 `body` 的 `font-family` 消除 Windows 中文字体割裂。
  - 为 `.site-header` 补全 `-webkit-backdrop-filter: blur(16px);`。
  - 将非标准字重 `550/650/750/850` 规范为标准 `500/600/700/800`。
  - 新增 `@media print`：隐藏吸顶导航、搜索、返回顶部、评论表单，强制白底黑字并避免代码块分页截断。
- **卡片、分页与冗余 CSS 清理**：
  - 统一 `index.php` 与 `archive.php` 的文章卡片元信息（分类、日期）排布位置，避免不同列表页视觉割裂。
  - 当总页数 ≤ 1 时不输出空的 `<nav class="pagination-wrap" aria-label="文章分页"></nav>`（或用 `:empty { display: none; }` 兜底并在 PHP 侧判断）。
  - 清理 `style.css` 中的重复定义（`.post-card-footer`、`.archive-search`、`.article`）及未使用选择器（`.comments-closed`、`.button-outline`、`.post-card h3`、`.post-excerpt p`、`.section-heading .text-link`）。
  - 〔已修订：不成立，删除〕首页不存在空 `<img>`，唯一的 `<img>` 是品牌 Logo，`src` 正常。

---

## 三、第二部分：项目代码逻辑、性能、安全与工程整理

### 2.1 【P0】前端资源极致轻量化与消除首屏闪烁（架构决策）
- **现状问题**：
  - `assets/ui/sujian-ui.js` (**399 KB**) + `assets/ui/sujian-ui.css` (**34.7 KB**) + 内联未压缩 `assets/js/sujian-script.php` (**7.9 KB**) + 未压缩 `assets/js/instantpage.js` (**12 KB**) + 未压缩 `style.css` (**25.6 KB**) ≈ **480 KB**。
  - 首屏先展示 `[data-ui-fallback]`，待 React 挂载后隐藏并替换为 `#sujian-controls`（`src/main.tsx:47-51`），导致明显图标闪烁与 `getComputedStyle()` 同步重排（`src/main.tsx:199`）。

| 对比维度 | 方案 A：原生轻量重构（强烈推荐） | 方案 B：保留 React + Vite 精简优化 |
| :--- | :--- | :--- |
| **实现方式** | 原生 `<dialog>`（搜索弹窗）+ 轻量 Vanilla JS（主题下拉菜单、移动端抽屉、返回顶部、`/` 快捷键），合并为单个可缓存外链 `assets/js/sujian.min.js` | 〔已修订〕保留 `src/`；**不要**把 `style.css` 放进 `@layer`（否则会输给 `ghmd.css` 和插件 CSS）；裁剪 shadcn 导出只能减少 CSS（Rollup 已自动去掉没用到的 JS）。重点改为：兜底按钮与 React 一致、返回顶部覆盖全站、空闲时再加载 React（详见评审文档 5.2） |
| **前端负载** | JS `< 8 KB`，CSS 无需 35 KB Tailwind（总降幅 **>90%**） | 〔已修订〕实测 gzip 约 150 KB，基本不变（React + Base UI 约 130 KB 是最低值），但可以移出首屏 |
| **首屏闪烁** | 彻底消失（无双套 UI 替换） | 需统一服务端与客户端图标并在渲染前隐藏 fallback |
| **维护成本** | 零构建依赖，彻底移除 `node_modules/` 与打包链路 | 需持续维护 Node / pnpm / Vite / Tailwind v4 升级 |

- **同步交互增强**（无论方案 A 或 B 均应实施）：
  - 将内联脚本 `assets/js/sujian-script.php` 改为外链可缓存 JS 文件，压缩 `instantpage.js`（~2.5 KB），统一用 `filemtime()` 生成版本号，并移除已废弃的 `linkElement.as = 'document'`（`assets/js/instantpage.js:315`）。
  - 将返回顶部按钮移至 `footer.php`，使首页、归档页、独立页、友链页等长页面也能使用。
  - 为全站所有模板（`index.php`、`archive.php`、`page.php`、`page-friends.php`、`404.php`）的 `<main id="main">` 补全 `tabindex="-1"`，修复跳至正文与返回顶部焦点转移。
  - 完善 `/` 快捷键边界判断：排除 `SELECT`、`[contenteditable]`、`event.isComposing` 及 `Ctrl`/`Meta`/`Alt` 组合键。
  - 激活 `post.php:6` 的 `data-article-back`〔已修订〕：只有同时满足“`document.referrer` 与当前站点同源”和 `history.length > 1` 时才调用 `history.back()`（新标签页打开时历史记录只有一条），否则保留现在跳回首页的链接。

### 2.2 【P0】消除服务端双重 Markdown 解析（列表页与 SEO 提速）
- **位置**：`functions.php:62-73, 358-382`、`index.php:37`、`archive.php:26`、`header.php:39`
- **根因**：`$archive->content` 已被 Typecho 转为 HTML；`sujianExcerpt()` 与 `sujianSeoContentText()` 调用 `sujianMarkdownPlainText()` 先 `strip_tags()` 后又对整篇正文**二次调用 `\Utils\Markdown::convert()`** 与 6 条正则（含递归正则）。
- **优化方案**〔已修订〕：`tests/summary.php` 中有 4 个用例（`Markdown in plain HTML`、`exclude fenced code`、`balanced link parentheses`、`image-only summary`）要求**从已渲染的内容中再识别原始 Markdown**，所以**不能直接去掉**第二次 `convert()`。改为：
  1. `strip_tags` 之后**先截断**到约 1500 个字符（在换行处截断），再调用 `\Utils\Markdown::convert()`。
  2. 文本里没有 `` ` * _ # [ ! > | ~ `` 等 Markdown 特征字符时，直接跳过 `convert()`。
  3. 可选：在同一个请求内按 `cid` 缓存结果。
  4. 验收：`tests/summary.php` 的 21 项全部通过。

### 2.3 【P0】修复评论反垃圾安全漏洞与关闭评论时的脚本冗余
- **位置**：`header.php:223`、`comments.php:19`、`assets/js/sujian-script.php:145-163`
- **问题**：
  - `header.php:223` 传入 `antiSpam=` 关闭了 Typecho 内置 JS 混淆保护，转而在 HTML 属性 `data-comment-token` 明文输出 Token，基础爬虫直接正则即可绕过。
  - 当 `!sujianCommentsEnabled($this->options) || !$this->allow('comment')` 时未传 `commentReply=`（空值），导致 `<head>` 仍每页输出约 100 行 `window.TypechoComment` 脚本。
- **优化方案**〔已修订：降为 P2，并修正参数写法〕：
  - ⚠️ **不能传 `commentReply=0` / `antiSpam=0`**。Typecho 的判断是 `'' != $v` 成立后再判断 `1 == $v`，值为 `'0'` 时会走 else 分支，输出 `<script src="0">`（`var/Widget/Archive.php:1078-1085,1190-1230`）。**必须用空值关闭**：`commentReply=`、`antiSpam=`。
  - 整站关闭评论或当前页面不允许评论时，在规则后追加 `&commentReply=`。
  - 恢复原生 `antiSpam` 只是少维护一段自写代码：原生方案同样只是简单混淆，token 也在页面源码里，所以**这不是安全修复**。如果要恢复，需要同时删除 `comments.php` 的 `data-comment-token` 属性和 `sujian-script.php:145-163` 的注入逻辑。

### 2.4 【P1】Typecho 1.3 API 现代化与 PHP 8.x 防御性加固
- **位置**：`functions.php:22, 225, 255, 393-556`、`header.php:23-28, 66, 170`、`archive.php:6-11`
- **优化项**：
  1. 将 `Typecho_Widget_Helper_Form_Element_{Text,Textarea,Radio,Select}` 统一升级为 Typecho 1.2+/1.3 标准命名空间 `\Typecho\Widget\Helper\Form\Element\*`（同步简化 `tests/friends-editor.php` 中的 polyfill）。
  2. 〔已修订：降为 P3 文案统一〕`archiveTitle()` 找不到对应类型时会退回 `'%s'`，日期归档有标题，只是没有前缀。可以补上 `'date' => _t('%s 归档')`，并把这份映射提取到 `functions.php` 的公共函数里，消除两处重复维护。
  3. 改进 `sujianSafeUrl()`：先验证协议白名单（`http`/`https` 或以 `/` 开头相对路径），再对非 ASCII 路径段做百分号编码后校验，避免中文文件名图片（封面图、文章内图）被 `FILTER_VALIDATE_URL` 静默丢弃。
  4. 去除 `bannerImage` 对第三方图床 `https://bing.xzxo.cn/img` 的硬编码默认值（默认留空，默认 `bannerMode` 保持 `gradient`）。
  5. PHP 8.x 防御：`preg_split(...) ?: []`、`is_file()` 校验后再调用 `filemtime()` / `file_get_contents()`、访问 `$this->categories[0]['name'] ?? ''` 前判空。

### 2.5 【P1】SEO 与无障碍（A11y）专项整理
- **修复带查询参数的分页 Canonical**〔已修订：降为 P3 / 可删除。Typecho 的分页和搜索都是路径形式（`/page/2/`、`/search/关键词/`），下面描述的场景基本遇不到〕（`header.php:12-16`）：
  - 仅剥离 `#fragment` 与无意义的跟踪参数（如 `utm_*`），保留搜索词 `s` 与分页参数 `page`（或基于当前路由正确构造分页规范 URL），防止 `/?s=xxx&page=2` 的 `canonical`/`og:url` 坍缩回首页 `/`。
- **消除重复结构化数据 & 补全面包屑**：
  - 保留并完善 `<head>` 中的 JSON-LD `@graph`，删除模板（`index.php`/`archive.php`/`post.php`/`page.php`）中残缺的 `itemscope itemtype="https://schema.org/BlogPosting"` Microdata 属性，避免富媒体结果校验冲突。
  - 在 `header.php:148-157` 文章页 `BreadcrumbList` 中插入文章主分类层级（`首页 → 分类 → 文章`）。
- **清理过时 Meta 与提升 LCP**：
  - 移除 `X-UA-Compatible`、`meta[name="keywords"]` 以及非标准 `theme-name` / `theme-name-zh` / `theme-repository` meta（可改为 HTML 注释）。
  - `bannerMode === 'image'` 时在 `<head>` 输出 `<link rel="preload" as="image" href="...">`（或使用 `<img fetchpriority="high">`），并适当加深右侧渐变遮罩（如 `rgba(9,9,11,.22)`）保证浅色照片上文字可读。
- **表单与语义无障碍**：
  - `comments.php` 称呼/邮箱/网站输入框补全 `autocomplete="name|email|url"`，为必填项添加视觉 `*` 标识；移除 `<form>` 上多余的 `role="form"` 与 `<footer>` 上多余的 `role="contentinfo"`。

### 2.6 【P2】模板去重、版本统一与发布目录卫生
- **抽离局部模板**：将重复的文章卡片抽离为 `partials/post-card.php`，将重复的搜索表单抽离为 `partials/search-form.php`；主题持久化读写收拢至单一脚本入口。
- **清理版本与文件不一致**：
  - 同步 `style.css:5` 版本号与 `index.php` / `functions.php` / `package.json` / `README.md` 一致。
  - 删除未使用的 `src/lib/utils.ts`；统一文档与测试中的目录名大小写提示（规避 Linux 大小写敏感问题）；压缩 `screenshot.png`（470 KB → 目标 `< 120 KB`）。
- **Web 根目录卫生**：
  - 添加 `.gitattributes`（`export-ignore`）或打包脚本，在生产主题目录与发布压缩包中排除 `node_modules/`、`src/`、`tests/`、`docs/`、`package.json`、`pnpm-lock.yaml`、`tsconfig.json`、`vite.config.ts`。

---

## 四、第三部分：Typecho 后台管理问题整理

### 3.1 【P1】文章编辑页缺失 `themeFields()` 可视化自定义字段
- **位置**：`functions.php`（对应后台 `/admin/write-post.php`）
- **实测问题**：外观设置页说明文章支持 `seoImage` / `cover` / `thumbnail`（源码同时支持 `customSummary`、`seoTitle`、`seoDescription`、`seoKeywords`），但写文章页面展开「自定义字段」只见 Typecho 默认空白键值对，作者必须背诵字段名手动录入。
- **优化方案**〔已修订：字段清单改为与代码一致〕：代码里实际读取的只有 `customSummary`、`seoDescription`（兼容 `seo_d`）、`seoImage`（兼容 `cover`/`thumbnail`）。`seoTitle`、`seoKeywords` **在代码中不存在**，只加后台字段不会生效。第一步只在 `themeFields($layout)` 中暴露这 3 个字段：
  - `customSummary`（Textarea）：自定义文章摘要（列表页和 SEO 描述优先使用）
  - `seoDescription`（Textarea）：自定义 SEO 描述（留空时自动提取摘要）
  - `seoImage`（Text）：文章封面 / 社交分享图地址（覆盖主题默认分享图）
  - 如果需要 `seoTitle`，要同时修改 `header.php:18-20` 的标题逻辑并补充测试。

### 3.2 【P1】关闭“友情链接页面”后残留孤立标题与说明
- **位置**：`assets/js/friends-editor.js:197-206`
- **实测问题**：将「友情链接页面」切换为「关闭」时，仅设置 `editor.hidden = true`，未隐藏外层 `<ul class="typecho-option">`，页面留下孤立的「友情链接」标题与填表提示。
- **优化方案**：与 `settings-editor.js:318-321` 的 Banner 切换逻辑保持一致，切换为关闭时直接隐藏 `source.closest('ul').hidden = !enabled;`（保留 textarea 值，提交后数据不丢失）。

### 3.3 【P1】桌面端友链编辑器单行过挤与媒体查询混用
- **位置**：`assets/css/friends-editor.css:15-56, 145-185`
- **实测问题**：在 `1440×900` 后台默认内容列宽下，`@container (min-width: 720px)` 强制单行塞入「序号 + 4 输入框 + 3 按钮」，每个输入框仅 `114px ~ 171px`，URL 与简介被严重截断；且文件混用了 `@container (min-width: 720px)` 与 `@media (max-width: 540px)`。
- **优化方案**：
  - 取消过窄的 4 列单行布局，桌面端采用 **2×2 卡片网格**（第一行：名字 + 跳转地址；第二行：头像地址 + 简介；左侧序号、右侧操作按钮），让 URL 与简介宽度翻倍。
  - 将 `@media (max-width: 540px)` 统一改为 `@container (max-width: 520px)`，使其严格响应容器宽度而非浏览器窗口宽度。
  - 可选增强：在头像地址旁增加 `28×28` 实时缩略预览图，方便站长当场确认头像链接是否有效。

### 3.4 【P1】主题设置页分组导航栏不吸顶与长表单操作不便
- **位置**：`assets/css/settings-editor.css:10-20`
- **实测问题**：`.sujian-settings-nav` 随页面滚出视口，在较长的主题设置页中无法随时跳转分组；保存按钮也位于页面最底部。
- **优化方案**：
  - 为 `.sujian-settings-nav` 增加 `position: sticky; top: 0; z-index: 10; background: #f6f6f3;`（匹配 Typecho 后台背景色，加底部浅分隔线/微阴影），滚动到任意分组均可一键切换。
  - 高亮当前所在分组锚点（轻量 `IntersectionObserver`），并可为底部提交栏增加粘性底栏样式，减少上下反复滚动。

### 3.5 【P1】后台编辑器脚本健壮性与 URL 规范化漏洞
- **相对路径硬编码域名问题**（`assets/js/settings-editor.js:27-38`、`assets/js/friends-editor.js:35-43`）：
  - 当前使用 `new URL(trimmed, new URL('../', location.href))`，当用户填写不带前导 `/` 的相对路径（如 `friends.html`）时，会被展开并保存成当前站点完整地址（如 `http://sujian.itsse.cn/friends.html`），本地调试完迁移线上域名后链接直接失效。
  - **修复**〔已修订〕：不能简单“补前导 `/`”，否则子目录安装（`siteUrl=/blog/`）会丢掉 `/blog/`；也不能原样保存 `friends.html`，因为前台 `sujianNavigation()` 只接受以 `/` 开头的路径或绝对 URL。正确做法是：显式的 `http(s)://` 地址保留 `url.href`，其余一律保存为 `url.pathname + url.search + url.hash`（按站点根目录解析，包含子目录）。
- **高级 CSS 输入抖动**（`assets/js/settings-editor.js:273`）：
  - `source.addEventListener('input', load)` 在每次敲击键盘时重建所有色标 DOM 并反复切显隐。改为：`input` 事件仅实时校验并更新预览条背景；`change` / `blur` 时再同步重建可视化色标控件。
- **空值防御**（`assets/js/settings-editor.js:291, 313, 317, 355`）：
  - 对 `form.elements.*`、`querySelector()`、`closest('ul')` 全部补充判空保护，防止单项缺失拖垮整个设置页增强脚本。
- **内联资源加载优化**（`functions.php:421-424, 554-557`）：
  - `file_get_contents()` 前增加 `is_file()` 判断；优先考虑改为 `<link>`/`<script>` 外链加载（附 `filemtime()` 版本号），减少后台设置页 HTML 体积并利用缓存。

### 3.6 【P2】后台文案错别字与重复标签清理
- **错别字修正（友联 → 友链）**：
  - `functions.php:407`：“关闭后友联页面返回 404，已填写的友联数据仍会保留” → “友链”
  - `assets/js/friends-editor.js:20, 152, 153, 160, 175`：“+ 添加友联 / 删除此友联 / 已删除空白友联 / 已添加友联” → “友链”
  - `README.md` 同步修正。
- **去重冗余标签**：
  - 导航菜单分组下 `<h3>导航菜单</h3>` 与其下唯一字段 `<label>导航菜单</label>` 连续重复，可在单字段分组中隐藏冗余 `<label>`（或改为更具体的子标题，如“菜单项列表”）。
  - 顺手核查「评论 / 全站评论」「页脚 / 页脚内容」的标题层次，使表单视觉更利落。

---

## 五、第四部分：分阶段实施路线图与验收清单

### 阶段 1：高收益零风险修复（推荐优先落地）
- [ ] **UI / CSS**：限定 `svg` 作用域、修复嵌套评论样式与头像/文字排版、补齐暗色主题色对比度、修加密文章密码框暗色、补短页面粘性页脚、修 `≤359px` 搜索图标错位、统一 `ghmd.css` 变量字体与 `@media print`、友链裂图 `onerror` 兜底、统一卡片元信息与空分页隐藏。
- [ ] **后台管理**：在 `functions.php` 新增 `themeFields($layout)`、关闭友链隐藏整组 `ul`、重构桌面端 2×2 友链编辑器与 `@container`、`.sujian-settings-nav` 吸顶、修复相对路径写死域名与高级 CSS 输入抖动、修正全部“友联”错别字与空值保护、新增 Gravatar 镜像配置项。
- [ ] **PHP / 安全 / SEO**〔已修订〕：Markdown 摘要改为“先截断 + 按特征判断”（`tests/summary.php` 保持全部通过）、关闭评论时传 `commentReply=`（**空值，不能写 0**）、完善面包屑分类、修复 `sujianSafeUrl()` 对中文路径的支持；分页 Canonical、`'date'` 文案、Microdata 去重降为阶段 3 可选。

### 阶段 2：前端交互轻量化与菜单/弹窗根治
- [ ] 执行选定架构方案（**推荐方案 A**：用原生 `<dialog>` + `< 8 KB` Vanilla JS/CSS 替代 399 KB React/Base UI + 35 KB Tailwind），彻底根治主题下拉菜单白底白字/黑底黑字、搜索框按钮高度错位与首屏图标闪烁。
- [ ] 压缩 `instantpage.js` 并移除废弃 `as = 'document'`；外链缓存前端交互脚本。
- [ ] 返回顶部覆盖全站长页面；全模板 `<main id="main">` 补齐 `tabindex="-1"`；激活文章页智能返回来源页；补齐评论表单 `autocomplete` 与必填标记、完善 `/` 快捷键边界判断。

### 阶段 3：Typecho 1.3 现代化与发布工程规范
- [ ] `functions.php` 全部升级至 `\Typecho\Widget\Helper\Form\Element\*` 命名空间类，补全 PHP 8.x 防御性检查。
- [ ] 抽离 `partials/post-card.php` 与 `partials/search-form.php`；统一版本号（含 `style.css`）；清理 `src/lib/utils.ts`；压缩 `screenshot.png`。
- [ ] 配置 `.gitattributes` 或发布构建脚本，确保线上部署包干净不含 `node_modules/`、`src/`、`tests/`、`docs/`。

### 最终验收标准
1. **自动化测试**：运行 `tests/` 下全部 PHP 测试脚本（`seo.php`、`summary.php`、`comments.php`、`friends.php`、`header-logo.php`、`friends-editor.php`）100% 通过。
2. **浏览器实机复核**：在 `http://sujian.itsse.cn` 验证桌面（`1440×900`）、移动（`390×844`）、极窄（`350×844`）下浅色/深色/全强调色无对比度缺陷、无菜单反色、无闪烁、无横向溢出；后台外观设置与写文章自定义字段全流程无报错。
3. **性能指标**：前端静态 JS/CSS 未压缩总体积较优化前（~480 KB）下降 90% 以上（方案 A），首页与归档页无冗余内联脚本与二次 Markdown 解析。
