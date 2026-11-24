# 优化方案可行性评审（optimization-plan.md 复核）

> **复核方式**：逐条对照主题源码、Typecho 1.3.0 核心代码（`var/Widget/Archive.php`、`var/Widget/Comments/Archive.php`、`var/Typecho/Common.php`），用 PHP 8.0.2 运行主题自带测试，并在浏览器中对有疑问的 UI 问题重新测量。
> **测试基线**：`tests/` 下 6 个脚本全部通过（comments 17、friends-editor 42、friends 54、header-logo 89、seo 12、summary 21，共 **235 PASS / 0 FAIL**）。之后每次改动都应保持这个结果。

---

## 一、结论速览

| 分类 | 数量 | 说明 |
| :--- | :--- | :--- |
| ✅ 成立且方案可行 | 20 项 | 可以按原方案实施（个别要补细节） |
| ⚠️ 方案本身有错，必须修改 | 6 项 | 照原写法实施会引入 Bug 或让现有测试失败 |
| 🔻 问题被夸大，需要降级 | 7 项 | 问题存在，但优先级或描述不准 |
| ❌ 问题不成立，应删除 | 3 项 | 复测或读代码后确认不存在 |

**总体判断**：方案的大方向可行，但有 **6 处写法必须改**，其中两处最关键：
1. `commentReply=0` / `antiSpam=0` 会让 Typecho 输出 `<script src="0">`。
2. “彻底去掉二次 Markdown 解析”会让 4 个现有测试失败。

---

## 二、❌ 不成立，应从方案中删除

| # | 原方案条目 | 复核结果 | 证据 |
| :--- | :--- | :--- | :--- |
| 1 | 1.5「搜索弹窗输入框 44px、按钮 32px 不齐」 | **不成立**。复测输入框和提交按钮都是 **44px**，`y` 坐标相同 | `.ui-submit{height:2.75rem}` 在编译后的 `sujian-ui.css` 里没有放进 `@layer`，所以优先级高于 `@layer utilities` 里的 `h-8` |
| 2 | 1.6「首页 DOM 存在空 `<img src="">`」 | **不成立**。首页只有 1 个 `<img>`，就是品牌 Logo，`src` 正常 | 之前的结果是浏览器工具在 `aria-hidden` 子树上读取属性时出错 |
| 3 | 1.4「`.brand-mark` 深色模式下是刺眼白块」 | **默认配置下不成立**。实测默认 `favicon.svg` 会铺满容器，看不到白底 | 只有上传**透明背景 Logo** 时才会露出 `var(--foreground)` 白底 → 降为 P3 可选 |

---

## 三、⚠️ 方案写法有错，必须修改

### 3.1 `commentReply=0` / `antiSpam=0` 会输出 `<script src="0">`（原 2.3）
- **Typecho 1.3 的逻辑**（`var/Widget/Archive.php:1078-1085`、`1190-1230`）：
  ```php
  if ('' != $allows['commentReply']) {
      if (1 == $allows['commentReply']) { /* 输出内联脚本 */ }
      else { $header .= '<script src="' . $allows['commentReply'] . '"></script>'; }
  }
  ```
  传入字符串 `'0'` 时，`'' != '0'` 成立，`1 == '0'` 不成立，于是走到 `else` 分支，输出 `<script src="0">`。
- **正确写法**：用**空值**关闭，即 `commentReply=` 和 `antiSpam=`（主题现在的 `antiSpam=` 就是这样写的）。
  ```php
  $rule = 'description=0&keywords=0&generator=0&template=0&social=0&antiSpam=';
  if (!sujianCommentsEnabled($this->options) || !$this->allow('comment')) {
      $rule .= '&commentReply=';
  }
  ```
  注意：`description=0` 这类写法能生效，是因为这些开关在核心里走的是 `if ($allows['xxx'])` 判断，和 `commentReply` / `antiSpam` 的判断方式不同。

### 3.2 “彻底去掉二次 Markdown 解析”与作者的设计意图冲突（原 2.2）
- `tests/summary.php` 明确要求能从**已经渲染的内容中再识别原始 Markdown**（例如 HTML 里混写的 Markdown，或插件输出的内容）：
  - `Markdown in plain HTML`：`<p>![image](…) [link](…) \`code\`</p>` 期望得到 `link code`
  - `exclude fenced code`、`balanced link parentheses`、`image-only summary`
- 如果按原方案只做 `strip_tags`，**这 4 个用例会失败**。
- **修改后的方案**（保持测试通过，同时减少开销）：
  1. **先截断再解析**：`strip_tags` 之后只取前约 1500 个字符（在换行处截断），再交给 `\Utils\Markdown::convert()`。摘要只需要 140–160 个字，没必要解析全文。
  2. **按特征决定是否解析**：文本里没有 `` ` * _ # [ ! > | ~ `` 这类 Markdown 字符时，直接跳过 `convert()`。
  3. 可选：在同一个请求内按 `cid` 缓存结果（`header.php` 的 SEO 描述和正文可能对同一篇文章各算一次）。
- 预期效果：列表页每篇文章的解析量从“全文”降到“前 1.5K 字符”，CPU 开销会明显下降，同时语义不变。

### 3.3 `themeFields()` 的字段清单与代码不符（原 3.1）
- 代码里**实际读取**的字段只有：`customSummary`、`seoDescription`（兼容 `seo_d`）、`seoImage` / `cover` / `thumbnail`（见 `functions.php:163`、`header.php:38`、`functions.php:360`）。
- 原方案列出的 **`seoTitle`、`seoKeywords` 在代码中不存在**（关键词取自文章标签）。只加后台字段而不加读取逻辑，作者填了也不会生效。
- **修改为**：
  - 第一步只暴露已有的 3 个字段：`customSummary`（摘要）、`seoDescription`（SEO 描述）、`seoImage`（分享图/封面）。`cover`、`thumbnail` 只保留兼容读取，不在后台显示。
  - 如果确实需要 `seoTitle`，要同时修改 `header.php:18-20` 的标题逻辑，并补充测试。

### 3.4 后台相对路径的修复方式在子目录安装下会出错（原 3.5）
- 原方案写的是“补一个前导 `/`”。如果站点装在子目录（例如 `siteUrl = https://x.com/blog/`），`friends.html` 会被改成 `/friends.html`，丢掉了 `/blog/`。
- 另外，前台 `sujianNavigation()` 只调用 `sujianSafeUrl()`，**不接受**不带 `/` 的相对路径。所以也**不能**原样保存 `friends.html`。
- **正确改法**：先按站点根目录解析，再保存为**以根路径开头的地址**：
  ```js
  // settings-editor.js / friends-editor.js
  return isAbsoluteHttp(trimmed) ? url.href : url.pathname + url.search + url.hash;
  ```
  这样子目录安装会得到 `/blog/friends.html`，更换域名后也不受影响。

### 3.5 暗色模式强调色提亮时，必须同时修改 `--primary-foreground`（原 1.4）
- 重新计算的对比度（背景 `#111113`）：violet **≈3.3:1**、blue **≈3.0:1**、rose **≈4.0:1**、emerald **≈5.0:1**（**已经达标，不用改**）。浅色模式 `--muted-foreground` 在 `--muted` 上 ≈4.4:1，在白底上 ≈4.8:1（达标）。
- 如果把 violet 提亮到 `#a78bfa`，**白色文字在它上面只有 ≈2.7:1**，会让 `.button-primary`、分页当前页（`.page-navigator li.current`）的文字反而看不清。
- **修改为**：在 `html[data-theme="dark"][data-accent="violet|blue|rose"]` 中同时设置 `--primary-foreground: #18181b`（做法和现有的 zinc 规则一致）。

### 3.6 文章页“智能返回”只判断 referrer 不够（原 2.1）
- 在新标签页打开文章时，`document.referrer` 可能是同源的，但 `history.length === 1`，这时 `history.back()` 什么也不会发生。
- **修改为**：同时满足“同源 referrer”和 `history.length > 1` 才调用 `history.back()`；否则保留现在跳回首页的链接。

---

## 四、🔻 被夸大的问题，需要降级或修正描述

| # | 原条目 | 原优先级 → 建议 | 原因 |
| :--- | :--- | :--- | :--- |
| 1 | 1.1 主题菜单“白底白字/黑底黑字” | P0 → **待复现** | 稳定状态下复测正常：深色高亮 `#1c1c1f` 底 + `#fafafa` 字，浅色高亮 `#f4f4f5` 底 + `#18181b` 字（规则是 `.focus\:bg-accent:focus`）。第一次截图里的白色高亮没能复现，原方案写的“动画不同步”根因**没有证据**，应删除。可能只出现在点击后切换主题的那一帧。如果选择方案 A，这个问题自然消失 |
| 2 | 2.3 评论反垃圾“安全漏洞” | P0 → **P2** | Typecho 原生方案也只是用 `shuffleScriptVar` 做简单拼接混淆，token 同样写在页面源码里；主题的做法机制一样（同样等到用户滚动、按键等交互后才注入）。恢复原生只是**少维护一段自写代码**，并不能修复安全问题。真正值得做的是关闭评论时去掉 `commentReply` 脚本（写法见 3.1） |
| 3 | 2.5 查询分页 canonical 坍缩 | P1 → **删除或 P3** | Typecho 的分页和搜索都是路径形式（`/page/2/`、`/search/关键词/`），`?s=` 请求会被重定向到路径形式。实测搜索页是 `/search/Electron/`，原方案描述的场景基本遇不到 |
| 4 | 2.4 `'date'` 归档“缺失标题” | P1 → **P3（文案统一）** | `archiveTitle()` 找不到对应类型时会退回 `'%s'`（`Archive.php:1281`），日期归档**有标题**，只是没有“xx 归档”这样的前缀 |
| 5 | 2.4 `Typecho_Widget_Helper_*` “已废弃” | P1 → **P3（代码风格）** | Typecho 1.3 通过 `__TYPECHO_CLASS_ALIASES__` 自动做别名映射（`Common.php:80-123`），旧类名完全可用。迁移时要同步删除 `tests/friends-editor.php` 里的兼容代码 |
| 6 | 2.6 Web 根目录卫生 | P1 → **P3** | `node_modules/` 已经写在 `.gitignore` 里，当前目录是**本地开发目录**，不是线上部署目录；只要保证发布包不包含这些文件即可 |
| 7 | 2.5 删除 Microdata | P1 → **P3 可选** | Google 支持 JSON-LD 和 Microdata 同时存在，这不算错误；Typecho 的评论列表本身也会输出 Microdata |

**其他需要修正的描述**：
- **“~480 KB”是未压缩体积**。实测 gzip 后首页约 **150 KB**（`sujian-ui.js` 一个文件就占 131.6 KB）。文档里应写明“未压缩”。
- **Gravatar 镜像**：Typecho 原生支持在 `config.inc.php` 中定义 `__TYPECHO_GRAVATAR_PREFIX__`（`Common.php:853`）。主题设置项可以在 `themeInit()` 里写 `if (!defined('__TYPECHO_GRAVATAR_PREFIX__') && $mirror) define(...)`。方案里应写明这两种做法，并优先推荐写进文档。
- **评论区相关问题全部来自读代码**：当前站点关闭了全站评论，前台看不到评论区。Typecho 的子评论结构已经核实确实带 `comment-list` 类（`Comments/Archive.php:282, 373`），所以样式冲突成立，但改完后需要**开启评论实测**。
- **后台分组导航吸顶**：需要先实测 Typecho 后台容器有没有 `overflow` 会让 `sticky` 失效；背景色以实测为准，不要直接写死 `#f6f6f3`。

---

## 五、方案 A（去掉 React）的可行性专项评估

**技术可行性：高。** `assets/js/sujian-script.php` 里已经有一整套原生实现：搜索面板、浅/深色切换、移动菜单、Esc 关闭、顶栏滚动阴影、评论 token。实施方案 A 只需要：
1. 把浅/深色切换扩展为三态菜单（浅色 / 深色 / 跟随系统），继续沿用 `sujian-color-scheme` 这个存储键，兼容已有用户设置。
2. 补上返回顶部按钮（约 30 行）和 `/` 快捷键的完整判断（`SELECT`、`contenteditable`、`isComposing`、修饰键）。
3. 删除所有 `root.dataset.uiReady === 'true'` 分支，移除 `header.php:220` 和 `footer.php:18-20` 对 `sujian-ui.*` 的引用，删除 `#sujian-controls`。
4. 把 `sujian-script.php` 改成静态 `assets/js/sujian.js`，用 `defer` 外链加载，版本号用 `filemtime()`。

**需要作者决定的点（不是技术问题）：**
- `README.md` 明确写着“搜索、移动菜单和外观选择通过 React 与 shadcn/ui（Base UI）组件增强”，这是主题的**设计定位**。改成方案 A 等于换掉这个卖点，README 也要同步修改。
- 交互形态会变：搜索从**居中弹窗**变成**顶栏下拉面板**（也可以改用原生 `<dialog>` 保留弹窗形态，多写约 40 行），移动导航从**侧边抽屉**变成**顶栏下拉**。
- 如果选方案 B，原方案里“1.1 菜单反色”和“1.5 按钮高度”两项都已确认不需要修，B 的实际工作量会比原文描述的小（详见下面的 5.2）。

### 5.2 方案 B（保留 React + shadcn/ui）的可行性专项评估

**实测体积（gzip，首页）**：`sujian-ui.js` 131.6 KB、`sujian-ui.css` 6.1 KB、`style.css` 5.8 KB、`instantpage.js` 4.1 KB、内联脚本 2.0 KB，**合计约 150 KB**。其中 React 那一部分占了 **约 92%**。

**技术可行性：高。** 本地已有 Node 22.23、pnpm 10.33、Vite 7，`pnpm build` 会先做类型检查，再把结果输出到 `assets/ui/`，构建链路完整可用。

**原方案对 B 的描述有 2 处要改：**
1. **“裁剪未使用的 shadcn 导出”几乎不能减小 JS 体积。** Vite（Rollup）打包时已经会自动丢弃没用到的导出函数。裁剪只对 Tailwind 有效：`@source "./"` 会扫描所有组件文件，把没用到的类名也编进 CSS。即使裁掉，最多也只能省下 `sujian-ui.css` 6 KB gzip 中的 1–2 KB。**结论：方案 B 下，JS 体积基本就是 React + Base UI 的最低值，约 130 KB gzip，没有大幅压缩的空间。**
2. **不要把 `style.css` 整体放进 `@layer`。** 放进 layer 的样式会输给**所有**没放进 layer 的样式，包括 `ghmd.css`、插件 CSS 和 Typecho 自带样式，可能引出新的覆盖问题。改为：
   - **最小做法（推荐）**：阶段 1 把全局 `svg` 规则限定作用域后，主要冲突就没有了；`src/ui.css:44` 那些已有的补丁保持不动。
   - **进阶做法（可选）**：把 `src/ui.css` 里的 `@layer utilities { @tailwind utilities … }` 改成不放进 layer 的写法。这样工具类（类选择器）会按优先级赢过 `style.css` 里的元素选择器（`svg`、`a`、`button`），之后就可以删掉 `!important` 和补丁。这一步需要重新构建，并逐个检查 4 个组件的样式。

**方案 B 真正值得做的改进：**

| 改进项 | 做法 | 需要重新构建？ |
| :--- | :--- | :--- |
| 消除首屏图标闪烁 | 让原生兜底按钮和 React 按钮**完全一致**：在 `<head>` 的内联脚本里加 `root.dataset.themePref = t \|\| 'system'`，兜底按钮放太阳/月亮/显示器 3 个图标，用 CSS 按 `data-theme-pref` 只显示其中一个；兜底图标尺寸从 `1.1rem` 统一成 `1rem`（与 lucide `size-4` 一致）。这样替换时看不出变化 | 否（只改 PHP 和 CSS） |
| 返回顶部覆盖全站 | 把 `<div id="sujian-back-to-top">` 从 `post.php` 移到 `footer.php`；所有模板的 `<main>` 补上 `tabindex="-1"` | 否 |
| 让 React 不占首屏 | 页面 `load` 之后或浏览器空闲时（`requestIdleCallback`），再动态插入 `sujian-ui.js`。在它加载之前，由原生兜底负责交互（和上一条配合，替换过程看不出来） | 否（只改 `footer.php`） |
| 去掉启动时多余的样式检查 | `main.tsx:199` 用 `getComputedStyle` 检查 CSS 是否加载，可以改成判断 `<link data-sujian-ui-css>` 的 `sheet` 是否存在 | 是 |

**方案 B 保留下来的优势（方案 A 需要自己重写的部分）**：Base UI 自带弹窗焦点锁定、打开时锁定页面滚动、`aria` 属性、键盘导航和动画，搜索**居中弹窗**、移动端**侧边抽屉**的交互形态不变，README 也不用改。

**方案 B 的长期成本**：每次修改 `src/` 都要执行 `pnpm build`，并提交 `assets/ui/` 下的产物；React 19、Base UI 1.x、Tailwind 4 需要持续跟进升级；移动端每次访问仍要下载约 130 KB gzip 的 JS 并执行约 400 KB 的代码（缓存后不用重复下载，但仍要执行）。

### 5.3 A / B 对比与建议

| 维度 | 方案 A：原生重构 | 方案 B：保留 React（按 5.2 改进） |
| :--- | :--- | :--- |
| 首页 JS+CSS（gzip） | 约 150 KB → **约 10–12 KB** | 约 150 KB → **约 148 KB**（体积基本不变，但可以移出首屏） |
| 首屏闪烁 | 根除 | 让兜底按钮和 React 按钮一致后，看不出替换 |
| 交互形态 | 搜索改成顶栏下拉（或用原生 `<dialog>` 保留弹窗），移动导航改成下拉 | **完全保留** |
| 无障碍（焦点锁定、滚动锁定等） | 要自己实现（原生 `<dialog>` 自带一部分） | Base UI 自带 |
| 工作量 | 中：重写约 250 行原生 JS，删除 `src/`、构建配置和 `assets/ui/` | **小**：主要改 PHP 和 CSS，进阶做法才需要重新构建 |
| 维护 | 不再依赖 Node 构建 | 需要 Node、pnpm 构建，并持续升级依赖 |
| README / 定位 | 需要修改“React + shadcn/ui”的介绍 | 不用改 |

**建议**：
- 想要**体积最小、最好维护**（目标是“极简博客主题”）→ 选 **A**。
- 想要**保留现在的弹窗和抽屉体验，以及 README 里的技术定位，并且改动最少** → 选 **B**，同时完成 5.2 表格里的前 3 项（都不需要重新构建）。

---

## 六、✅ 已确认成立、可以直接实施的条目

| 板块 | 条目 | 已核实的位置 / 备注 |
| :--- | :--- | :--- |
| UI | 全局 `svg` 规则影响正文 SVG | `style.css:95` |
| UI | 嵌套评论样式冲突（`.comments-list > .comment-list > li` 的写法可行） | `style.css:311-315`，核心 `Comments/Archive.php:282,373` |
| UI | 友链头像加载失败没有兜底（`onerror` 换成 `favicon.svg`，加 `this.onerror=null`） | `page-friends.php`，实测 `lik.cc` 拒绝连接 |
| UI | 短页面页脚不沉底（`body` 用 flex 纵向布局 + `main{flex:1 0 auto}`，不影响 `.container` 居中） | 实测 `/friends.html`、404 |
| UI | `≤359px` 搜索图标错位（改成 `top:1.35rem; transform:translateY(-50%)`） | `style.css:226,385-389`，在 350px 下实测 |
| UI | `ghmd.css` 配色和字体没跟随主题变量；缺少 `@media print`、`-webkit-backdrop-filter` | 读代码确认 |
| UI | 首页和归档页卡片的元信息位置不一致；只有 1 页时仍输出空分页 `<nav>` | `index.php:41-45` 对比 `archive.php` |
| 交互 | 返回顶部只在 `post.php` 有；`tabindex="-1"` 只在 `post.php` 有 | grep 确认 |
| 交互 | 原生兜底脚本的 `/` 快捷键判断不全 | `sujian-script.php:168` |
| 逻辑 | 关闭评论时仍输出 `TypechoComment` 脚本（按 3.1 的写法修） | `header.php:223` + `Archive.php:1077` |
| 逻辑 | `sujianSafeUrl()` 会拒绝含中文的绝对 URL | `functions.php:22` |
| 逻辑 | `bannerImage` 默认用第三方图床 | `functions.php:471` |
| 逻辑 | PHP 8 防御：`preg_split ?: []`、`filemtime` / `file_get_contents` 前先判断 `is_file` | `functions.php:225,255,422,555`、`header.php:170` |
| 逻辑 | 文章面包屑缺少分类层级 | `header.php:148-157` |
| 后台 | 文章编辑页没有 `themeFields()` | grep 确认（字段清单按 3.3 修正） |
| 后台 | 关闭友链后残留标题和说明（改成 `source.closest('ul').hidden = !enabled`，隐藏后字段值仍会随表单提交） | `friends-editor.js:197-206`，实测 |
| 后台 | 桌面端友链一行太挤；同时混用 `@container` 和 `@media` | `friends-editor.css:145-185`，实测 |
| 后台 | “高级 CSS” 每输入一个字符就重建色标控件；多处缺少判空 | `settings-editor.js:273,291-355` |
| 后台 | “友联”错别字（`functions.php:407`、`friends-editor.js:20,152,153,160,175`，`README.md` 里还有 5 处以上） | grep 确认 |
| 工程 | 版本号不一致（`style.css:5` 写的是 1.0.0） | 读代码确认 |

---

## 七、修订后的实施顺序

**阶段 1（低风险，每步改完都跑 `tests/`）**
1. CSS：限定 `svg` 作用域、嵌套评论样式、暗色强调色（同时改 `--primary-foreground`）、页脚沉底、窄屏搜索图标、`ghmd.css` 改用主题变量、打印样式、友链头像兜底。
2. PHP：关闭评论时传 `commentReply=`（**空值**）；Markdown 解析改成“先截断 + 按特征判断”；`themeFields()` 暴露已有的 3 个字段；面包屑加分类；PHP 8 防御判断；`sujianSafeUrl` 支持非 ASCII 字符。
3. 后台：友链关闭时整组隐藏、友链行改为 2×2 布局、相对路径保存为根路径（`pathname`）、高级 CSS 改成 `change` 时才重建、判空保护、“友联”→“友链”、导航吸顶（先实测）。

**阶段 2（需要你先决定）**
- 选**方案 A**：按第五节的 4 步实施，并同步修改 README。
- 选**方案 B**：按 5.2 实施，**不要**把 `style.css` 放进 `@layer`；优先做“兜底按钮与 React 一致”“返回顶部覆盖全站”“空闲时再加载 React”三项。

**阶段 3（可选 / 代码风格）**
- 迁移到命名空间版表单类、Microdata 去重、日期归档文案、发布打包规则、压缩 `screenshot.png`。
