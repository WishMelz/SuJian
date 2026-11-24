<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('SUJIAN_THEME_NAME', 'SuJian');
define('SUJIAN_THEME_NAME_ZH', '素笺');
define('SUJIAN_THEME_DISPLAY_NAME', SUJIAN_THEME_NAME . ' · ' . SUJIAN_THEME_NAME_ZH);
define('SUJIAN_THEME_VERSION', '1.0.2');
define('SUJIAN_THEME_REPOSITORY', 'https://github.com/WishMelz/SuJian');
// 标题宋体网络字体：思源宋体（Noto Serif SC）按 unicode-range 分片，浏览器只下载页面用到的字；国内镜像，支持 CORS 与长期缓存。
define('SUJIAN_SERIF_FONT_ORIGIN', 'https://registry.npmmirror.com');
define('SUJIAN_SERIF_FONT_CSS', SUJIAN_SERIF_FONT_ORIGIN . '/@fontsource/noto-serif-sc/5.3.1/files/600.css');

function sujianAccent($options)
{
    $accent = sujianOption($options, 'accent', 'zinc');
    return in_array($accent, array('zinc', 'cinnabar', 'violet', 'blue', 'emerald', 'rose'), true) ? $accent : 'zinc';
}

function sujianHeadingFont($options)
{
    $font = sujianOption($options, 'headingFont', 'serif-web');
    return in_array($font, array('serif-web', 'serif', 'sans'), true) ? $font : 'serif-web';
}

function sujianSafeUrl($url, $fallback = '')
{
    $url = trim((string) $url);

    if ($url === '' || preg_match('/[\x00-\x1f\x7f]/', $url)) {
        return $fallback;
    }

    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        return $url;
    }

    // 绝对地址中的中文路径先做百分号编码再校验，避免 FILTER_VALIDATE_URL 误判。
    $candidate = preg_replace_callback('/[^\x20-\x7e]+/', function ($matches) {
        return rawurlencode($matches[0]);
    }, $url);
    if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
        return $fallback;
    }

    $scheme = strtolower((string) parse_url($candidate, PHP_URL_SCHEME));
    return in_array($scheme, array('http', 'https'), true) ? $candidate : $fallback;
}

function sujianOption($options, $name, $fallback = '')
{
    $value = isset($options->{$name}) ? trim((string) $options->{$name}) : '';
    return $value !== '' ? $value : $fallback;
}

function sujianCommentsEnabled($options)
{
    return sujianOption($options, 'commentsEnabled', '1') === '1';
}

function sujianLanguage($options)
{
    $language = trim((string) $options->lang);
    return $language !== '' ? str_replace('_', '-', $language) : 'zh-CN';
}

function sujianSiteDisplayTitle($options)
{
    $siteTitle = trim((string) $options->title);
    return strcasecmp($siteTitle, SUJIAN_THEME_NAME) === 0
        ? SUJIAN_THEME_DISPLAY_NAME
        : $siteTitle;
}

function sujianPlainText($text)
{
    $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
}

function sujianMarkdownPlainText($text, $sliceLength = 1800)
{
    $text = preg_replace('/<(script|style|pre|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', (string) $text);
    $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6]|\/blockquote|\/section|\/article)\b[^>]*>/i', "\n", (string) $text);
    $stripped = strip_tags((string) $text);
    if ($sliceLength > 0 && sujianTextLength($stripped) > $sliceLength) {
        $stripped = sujianTextSubstring($stripped, $sliceLength);
        $lastBreak = strrpos($stripped, "\n");
        if ($lastBreak !== false && $lastBreak > 200) {
            $stripped = substr($stripped, 0, $lastBreak);
        }
    }
    if (!preg_match('/[`*_#\[!>|~]/', $stripped)) {
        return sujianPlainText($stripped);
    }
    $stripped = preg_replace_callback('/(!?\[[^\]\r\n]*\])\((?<target>(?:[^()\r\n]++|\((?&target)\))*+)\)/u', function ($matches) {
        return $matches[1] . '(' . str_replace(array('(', ')'), array('%28', '%29'), $matches['target']) . ')';
    }, $stripped);
    $html = \Utils\Markdown::convert((string) $stripped);
    $html = preg_replace('/<(script|style|pre|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', (string) $html);
    $html = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6]|\/blockquote|\/section|\/article)\b[^>]*>/i', ' ', (string) $html);
    return sujianPlainText($html);
}

function sujianSeoDescription($text, $fallback = '', $length = 160)
{
    $description = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    if ($description === '') {
        $description = trim((string) preg_replace('/\s+/u', ' ', (string) $fallback));
    }

    if (sujianTextLength($description) > $length) {
        $description = preg_replace('/[\s，。；、]+$/u', '', sujianTextSubstring($description, max(1, $length - 1))) . '…';
    }

    return $description;
}

function sujianTextLength($text)
{
    if (function_exists('mb_strlen')) {
        return mb_strlen((string) $text, 'UTF-8');
    }

    $characters = preg_split('//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($characters) ? count($characters) : strlen((string) $text);
}

function sujianTextSubstring($text, $length)
{
    if (function_exists('mb_substr')) {
        return mb_substr((string) $text, 0, (int) $length, 'UTF-8');
    }

    $characters = preg_split('//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($characters)
        ? implode('', array_slice($characters, 0, (int) $length))
        : substr((string) $text, 0, (int) $length);
}

function sujianSeoContentText($html)
{
    $html = preg_replace('/<(script|style|pre|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', (string) $html);
    $html = preg_replace('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', ' ', (string) $html);
    $html = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6]|\/blockquote|\/section|\/article)\b[^>]*>/i', ' ', (string) $html);
    return sujianMarkdownPlainText($html);
}

function sujianAbsoluteUrl($url, $siteUrl)
{
    $url = trim((string) $url);
    $siteUrl = rtrim((string) $siteUrl, '/') . '/';
    if ($url === '') {
        return '';
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        return sujianSafeUrl($url);
    }

    if (strpos($url, '//') === 0) {
        $scheme = parse_url($siteUrl, PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $url;
    }

    if (strpos($url, '/') === 0) {
        $parts = parse_url($siteUrl);
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $origin = $parts['scheme'] . '://' . $parts['host'];
            if (!empty($parts['port'])) {
                $origin .= ':' . $parts['port'];
            }
            return $origin . $url;
        }
    }

    return $siteUrl . ltrim($url, '/');
}

function sujianHeaderLogo($options)
{
    $defaultLogo = rtrim((string) $options->themeUrl, '/') . '/favicon.svg';
    return sujianSafeUrl(
        sujianAbsoluteUrl(sujianOption($options, 'headerLogo'), (string) $options->siteUrl),
        $defaultLogo
    );
}

function sujianSeoImage($archive, $options)
{
    $siteUrl = (string) $options->siteUrl;
    if ($archive->is('post') || $archive->is('page')) {
        foreach (array('seoImage', 'cover', 'thumbnail') as $field) {
            $value = isset($archive->fields->{$field}) ? (string) $archive->fields->{$field} : '';
            $image = sujianSafeUrl(sujianAbsoluteUrl($value, $siteUrl));
            if ($image !== '') {
                return $image;
            }
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $archive->content, $matches)) {
            $image = sujianSafeUrl(sujianAbsoluteUrl(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), $siteUrl));
            if ($image !== '') {
                return $image;
            }
        }
    }

    return sujianSafeUrl(sujianAbsoluteUrl(sujianOption($options, 'seoDefaultImage'), $siteUrl));
}

function sujianJsonLd($data)
{
    echo '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
        . '</script>' . "\n";
}

function sujianTypechoHeader($archive, $rule = '')
{
    ob_start();
    $archive->header($rule);
    $header = ob_get_clean();
    echo preg_replace('/<link\s+rel=(["\'])canonical\1[^>]*\/?>\s*/i', '', (string) $header);
}

function sujianFooterHtml($html, $options)
{
    return strtr((string) $html, array(
        '{year}' => date('Y'),
        '{siteTitle}' => htmlspecialchars((string) $options->title, ENT_QUOTES, 'UTF-8'),
        '{siteUrl}' => htmlspecialchars((string) $options->siteUrl, ENT_QUOTES, 'UTF-8'),
        '{description}' => htmlspecialchars((string) $options->description, ENT_QUOTES, 'UTF-8'),
        '{themeName}' => SUJIAN_THEME_DISPLAY_NAME,
        '{themeNameEn}' => SUJIAN_THEME_NAME,
        '{themeNameZh}' => SUJIAN_THEME_NAME_ZH,
        '{themeUrl}' => SUJIAN_THEME_REPOSITORY
    ));
}

function sujianFriendsEnabled($options)
{
    return sujianOption($options, 'friendsEnabled', '0') === '1';
}

function sujianFriends($options)
{
    $friends = array();
    if (!sujianFriendsEnabled($options)) {
        return $friends;
    }

    $siteUrl = (string) $options->siteUrl;
    $defaultAvatar = rtrim((string) $options->themeUrl, '/') . '/favicon.svg';
    foreach (preg_split('/\r\n|\r|\n/', sujianOption($options, 'friendsData')) ?: array() as $line) {
        $parts = array_map('trim', explode('|', trim($line), 5));
        if (count($parts) !== 5 || $parts[0] === '') {
            continue;
        }

        $address = sujianSafeUrl(sujianAbsoluteUrl($parts[2], $siteUrl));
        $url = $parts[3] === '' ? $address : sujianSafeUrl(sujianAbsoluteUrl($parts[3], $siteUrl));
        if ($url === '') {
            continue;
        }

        $friends[] = array(
            'name' => $parts[0],
            'avatar' => sujianSafeUrl(sujianAbsoluteUrl($parts[1], $siteUrl), $defaultAvatar),
            'address' => $address !== '' ? $address : $url,
            'url' => $url,
            'description' => $parts[4]
        );
    }

    return $friends;
}

function sujianNavigation($options)
{
    $navigation = array();
    $rawNavigation = sujianOption($options, 'navigation');

    if ($rawNavigation !== '') {
        foreach (preg_split('/\r\n|\r|\n/', $rawNavigation) ?: array() as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));
            $label = isset($parts[0]) ? $parts[0] : '';
            $url = isset($parts[1]) ? $parts[1] : '';
            $target = isset($parts[2]) ? strtolower($parts[2]) : 'self';
            // 单独的“/”表示站点首页：网站装在子目录（如 /blog/）时也能回到正确的首页
            $url = $url === '/' ? rtrim((string) $options->siteUrl, '/') . '/' : sujianSafeUrl($url);
            if ($label !== '' && $url !== '') {
                $navigation[] = array(
                    'label' => $label,
                    'url' => $url,
                    'target' => in_array($target, array('blank', '_blank', '新标签', '新窗口'), true) ? 'blank' : 'self'
                );
            }
        }
    }

    if (!$navigation) {
        $navigation[] = array('label' => '首页', 'url' => (string) $options->siteUrl, 'target' => 'self');
    }

    return $navigation;
}

function sujianNavigationLinkAttributes($item)
{
    return isset($item['target']) && $item['target'] === 'blank'
        ? ' target="_blank" rel="noopener noreferrer"'
        : '';
}

function sujianContentTitle($archive, $options, $fallback = '页面')
{
    $title = sujianPlainText($archive->title);
    if ($title !== '') {
        return $title;
    }

    $siteUrl = (string) $options->siteUrl;
    $archiveUrl = rtrim((string) preg_replace(
        '/[?#].*$/',
        '',
        sujianAbsoluteUrl((string) $archive->getArchiveUrl(), $siteUrl)
    ), '/');
    foreach (sujianNavigation($options) as $item) {
        $itemUrl = rtrim((string) preg_replace(
            '/[?#].*$/',
            '',
            sujianAbsoluteUrl($item['url'], $siteUrl)
        ), '/');
        if ($archiveUrl !== '' && $itemUrl === $archiveUrl) {
            return sujianPlainText($item['label']);
        }
    }

    $slug = isset($archive->slug) ? sujianPlainText($archive->slug) : '';
    return $slug !== '' ? $slug : $fallback;
}

function sujianArticleLinkTarget($options)
{
    return sujianOption($options, 'articleLinkTarget', 'self') === 'blank' ? 'blank' : 'self';
}

function sujianArticleLinkAttributes($options)
{
    return sujianArticleLinkTarget($options) === 'blank'
        ? ' target="_blank" rel="noopener noreferrer"'
        : '';
}

function sujianBannerGradient($mode)
{
    $gradients = array(
        '135deg, #7c3aed 0%, #4f46e5 48%, #0ea5e9 100%',
        '135deg, #db2777 0%, #7c3aed 48%, #2563eb 100%',
        '135deg, #059669 0%, #0d9488 48%, #2563eb 100%',
        '135deg, #ea580c 0%, #e11d48 48%, #7c3aed 100%',
        '135deg, #18181b 0%, #3f3f46 48%, #7c3aed 100%'
    );

    if ($mode === 'random') {
        $key = array_rand($gradients);
        return $gradients[$key];
    }

    return $gradients[0];
}

function sujianSafeGradient($gradient, $fallback)
{
    $gradient = trim((string) $gradient);
    if ($gradient === '' || !preg_match('/^[#%,.()a-zA-Z0-9\s-]+$/', $gradient)) {
        return $fallback;
    }

    return $gradient;
}

function sujianExcerpt($archive, $length = 140)
{
    $customSummary = isset($archive->fields->customSummary)
        ? sujianMarkdownPlainText((string) $archive->fields->customSummary)
        : '';
    if ($customSummary !== '') {
        $summary = $customSummary;
    } else {
        $content = (string) $archive->content;
        $content = preg_replace('/<(script|style|pre|table|figure|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', $content);
        $content = preg_replace('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', ' ', (string) $content);
        $content = preg_replace('/<(img|svg|video|audio|iframe)\b[^>]*\/?\s*>/is', ' ', (string) $content);
        $summary = sujianMarkdownPlainText($content);
    }

    if ($summary === '') {
        return '';
    }

    if (sujianTextLength($summary) > $length) {
        $summary = preg_replace('/[\s，。；、]+$/u', '', sujianTextSubstring($summary, max(1, $length - 1))) . '…';
    }

    return $summary;
}

function themeInit($archive)
{
    if ($archive->is('page') && $archive->template === 'page-friends.php' && !sujianFriendsEnabled(\Widget\Options::alloc())) {
        throw new \Typecho\Widget\Exception('Not Found', 404);
    }
}

/* ==========================================================================
   后台设置保存时的服务端校验
   浏览器端脚本会先校验并规范化；这里在服务端再检查一次，禁用 JavaScript 或脚本出错时
   也能在保存时看到错误提示，而不是保存后前台静默忽略。规则与前台的实际取值逻辑保持一致。
   ========================================================================== */

function sujianSubmittedSetting($name)
{
    if (class_exists('\Typecho\Request')) {
        return trim((string) \Typecho\Request::getInstance()->get($name, ''));
    }

    return isset($_POST[$name]) ? trim((string) $_POST[$name]) : '';
}

function sujianSettingLines($raw)
{
    return preg_split('/\r\n|\r|\n/', (string) $raw) ?: array();
}

function sujianValidSettingAddress($value)
{
    $value = trim((string) $value);
    return $value === '' || sujianSafeUrl(sujianAbsoluteUrl($value, 'https://example.invalid/')) !== '';
}

function sujianValidBannerImage($value)
{
    return sujianSubmittedSetting('bannerMode') !== 'image' || sujianValidSettingAddress($value);
}

function sujianValidBannerGradient($value)
{
    if (sujianSubmittedSetting('bannerMode') !== 'gradient') {
        return true;
    }

    $value = trim((string) $value);
    return sujianSafeGradient($value, '') !== '' && strpos($value, ',') !== false;
}

function sujianInvalidNavigationLines($raw)
{
    // 按非空行计数，与后台导航编辑器中的行序号一致
    $invalid = array();
    $position = 0;
    foreach (sujianSettingLines($raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $position++;
        $parts = array_map('trim', explode('|', $line, 3));
        $url = isset($parts[1]) ? $parts[1] : '';
        if (count($parts) < 2 || $parts[0] === '' || ($url !== '/' && sujianSafeUrl($url) === '')) {
            $invalid[] = $position;
        }
    }

    return $invalid;
}

function sujianValidNavigation($value)
{
    return !sujianInvalidNavigationLines($value);
}

function sujianInvalidFriendLines($raw)
{
    // 按非空行计数，与后台友链编辑器中的 01、02… 序号一致
    $invalid = array();
    $position = 0;
    foreach (sujianSettingLines($raw) as $line) {
        if (trim($line) === '') {
            continue;
        }

        $position++;
        $parts = array_map('trim', explode('|', trim($line), 5));
        $probe = (object) array(
            'friendsEnabled' => '1',
            'friendsData' => $line,
            'siteUrl' => 'https://example.invalid/',
            'themeUrl' => 'https://example.invalid/theme/'
        );
        if (!sujianFriends($probe) || (isset($parts[1]) && $parts[1] !== '' && !sujianValidSettingAddress($parts[1]))) {
            $invalid[] = $position;
        }
    }

    return $invalid;
}

function sujianValidFriends($value)
{
    return sujianSubmittedSetting('friendsEnabled') !== '1' || !sujianInvalidFriendLines($value);
}

function sujianLineListMessage(array $lines, $template, $fallback)
{
    return $lines ? sprintf($template, implode('、', $lines)) : $fallback;
}

function themeConfig($form)
{
    // 字段按后台分组顺序添加：即使浏览器禁用 JavaScript、分组脚本没有运行，表单顺序也保持合理。
    // 每个字段都带有服务端校验规则，保存时由 Typecho 执行，出错会在对应字段下显示提示并保留填写内容。
    $addressMessage = _t('请填写有效的图片地址：以 http:// 或 https:// 开头的完整地址，或以“/”开头的站内路径。');
    $choiceMessage = _t('请从列表中选择有效的选项。');

    /* ---------- 基础外观 ---------- */
    $siteLogo = new Typecho_Widget_Helper_Form_Element_Text(
        'headerLogo',
        null,
        null,
        _t('网站图标地址'),
        _t('同时用于页头 Logo 和浏览器网站图标，建议使用正方形图片。支持完整的 http/https URL 或以“/”开头的站内相对路径；留空时使用主题默认图片。')
    );
    $siteLogo->addRule('sujianValidSettingAddress', $addressMessage);
    $form->addInput($siteLogo);

    $accent = new Typecho_Widget_Helper_Form_Element_Select(
        'accent',
        array(
            'zinc' => _t('墨色'),
            'cinnabar' => _t('朱砂'),
            'blue' => _t('黛蓝'),
            'emerald' => _t('竹青'),
            'violet' => _t('紫藤'),
            'rose' => _t('海棠')
        ),
        'zinc',
        _t('主题强调色'),
        _t('用于链接悬停、按钮和当前页码等少量点缀，深色模式下会自动换成更亮的同色系。')
    );
    $accent->addRule('enum', $choiceMessage, array('zinc', 'cinnabar', 'blue', 'emerald', 'violet', 'rose'));
    $form->addInput($accent);

    $headingFont = new Typecho_Widget_Helper_Form_Element_Select(
        'headingFont',
        array(
            'serif-web' => _t('宋体（推荐，Windows 自动加载思源宋体）'),
            'serif' => _t('宋体（仅用系统字体，Windows 显示为黑体）'),
            'sans' => _t('黑体')
        ),
        'serif-web',
        _t('标题字体'),
        _t('Mac、iOS、Android 使用系统自带宋体，不会下载字体。Windows 会从国内镜像按需加载页面用到的思源宋体字形，首次访问约 300–400 KB，之后由浏览器缓存；加载失败时自动回退为黑体。')
    );
    $headingFont->addRule('enum', $choiceMessage, array('serif-web', 'serif', 'sans'));
    $form->addInput($headingFont);

    $articleLinkTarget = new Typecho_Widget_Helper_Form_Element_Select(
        'articleLinkTarget',
        array(
            'self' => _t('当前标签页打开'),
            'blank' => _t('新标签页打开')
        ),
        'self',
        _t('文章链接打开方式'),
        _t('控制首页和归档列表中文章标题链接的打开方式。选择当前标签页时，文章详情顶部会显示“返回文章列表”。')
    );
    $articleLinkTarget->addRule('enum', $choiceMessage, array('self', 'blank'));
    $form->addInput($articleLinkTarget);

    /* ---------- 导航菜单 ---------- */
    $navigation = new Typecho_Widget_Helper_Form_Element_Textarea(
        'navigation',
        null,
        '首页|/|self',
        _t('导航菜单'),
        _t('每行一个导航，格式为“名称|地址|打开方式”。打开方式填写 self（当前标签页）或 blank（新标签页），省略时为 self。地址支持以 / 开头的站内路径或 http/https 地址；单独的“/”表示站点首页。')
    );
    $navigation->input->setAttribute('data-sujian-navigation', '');
    $navigation->addRule('sujianValidNavigation', sujianLineListMessage(
        sujianInvalidNavigationLines(sujianSubmittedSetting('navigation')),
        _t('导航菜单第 %s 项无效：每行格式为“名称|地址|打开方式”，名称不能为空，地址需以“/”开头或为 http/https 地址。'),
        _t('导航菜单中有无效的项：每行格式为“名称|地址|打开方式”，名称不能为空，地址需以“/”开头或为 http/https 地址。')
    ));
    $form->addInput($navigation);

    /* ---------- 首页题头 ---------- */
    $bannerMode = new Typecho_Widget_Helper_Form_Element_Select(
        'bannerMode',
        array(
            'text' => _t('纯文字（推荐）'),
            'image' => _t('文字 + 题图'),
            'gradient' => _t('文字 + 渐变装饰线'),
            'random' => _t('文字 + 随机渐变装饰线')
        ),
        'text',
        _t('首页题头样式'),
        _t('题头只在首页第一页显示，始终包含标题和描述。题图显示在文字下方；渐变色只用于标题下方的短装饰线。')
    );
    $bannerMode->addRule('enum', $choiceMessage, array('text', 'image', 'gradient', 'random'));
    $form->addInput($bannerMode);

    $bannerTitle = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerTitle',
        null,
        null,
        _t('题头标题'),
        _t('留空时使用站点标题。')
    );
    $form->addInput($bannerTitle);

    $bannerDescription = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerDescription',
        null,
        null,
        _t('题头描述'),
        _t('留空时使用站点描述。')
    );
    $form->addInput($bannerDescription);

    $bannerImage = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerImage',
        null,
        null,
        _t('题图地址'),
        _t('选择“文字 + 题图”时使用，建议使用宽度不小于 1440px 的横向图片；支持站内相对路径或 http/https 图片地址。留空或加载失败时只显示文字。')
    );
    $bannerImage->addRule('sujianValidBannerImage', $addressMessage);
    $form->addInput($bannerImage);

    $bannerGradient = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerGradient',
        null,
        '135deg, #7c3aed 0%, #4f46e5 48%, #0ea5e9 100%',
        _t('装饰线渐变色'),
        _t('填写 CSS linear-gradient 括号内的颜色，例如：135deg, #7c3aed 0%, #0ea5e9 100%。')
    );
    $bannerGradient->addRule('sujianValidBannerGradient', _t('请填写有效的渐变色，至少包含两个颜色，例如：135deg, #7c3aed 0%, #0ea5e9 100%。'));
    $form->addInput($bannerGradient);

    /* ---------- 友情链接 ---------- */
    $friendsEnabled = new Typecho_Widget_Helper_Form_Element_Radio(
        'friendsEnabled',
        array('1' => _t('开启'), '0' => _t('关闭')),
        '0',
        _t('友情链接页面'),
        _t('开启后，在后台新建独立页面，选择“友情链接”模板并发布。关闭后友链页面返回 404，已填写的友链数据仍会保留；导航中指向友链页的菜单项需要手动删除。')
    );
    $friendsEnabled->addRule('enum', $choiceMessage, array('1', '0'));
    $form->addInput($friendsEnabled);

    $friendsData = new Typecho_Widget_Helper_Form_Element_Textarea(
        'friendsData',
        null,
        null,
        _t('友链列表'),
        _t('名字、跳转地址为必填项。头像留空或加载失败时使用默认图片，简介可留空。')
    );
    $friendsData->input->setAttribute('data-sujian-friends', '');
    $friendsData->addRule('sujianValidFriends', sujianLineListMessage(
        sujianInvalidFriendLines(sujianSubmittedSetting('friendsData')),
        _t('友链列表第 %s 条无效：名字和跳转地址必填，地址需为 http/https 地址或站内路径。纯文本格式为“名字|头像地址||跳转地址|简介”。'),
        _t('友链列表中有无效的条目：名字和跳转地址必填，地址需为 http/https 地址或站内路径。纯文本格式为“名字|头像地址||跳转地址|简介”。')
    ));
    $form->addInput($friendsData);

    $friendsEditor = new \Typecho\Widget\Helper\Layout('');
    $friendsCss = is_file(__DIR__ . '/assets/css/friends-editor.css') ? (string) file_get_contents(__DIR__ . '/assets/css/friends-editor.css') : '';
    $friendsJs = is_file(__DIR__ . '/assets/js/friends-editor.js') ? (string) file_get_contents(__DIR__ . '/assets/js/friends-editor.js') : '';
    $friendsEditor->html('<style>' . $friendsCss . '</style><script>' . $friendsJs . '</script>');
    $form->addItem($friendsEditor);

    /* ---------- 评论 ---------- */
    $commentsEnabled = new Typecho_Widget_Helper_Form_Element_Radio(
        'commentsEnabled',
        array('1' => _t('开启'), '0' => _t('关闭')),
        '1',
        _t('全站评论'),
        _t('关闭后，文章、独立页面和友链页都不显示评论区，列表也不显示评论数；已有评论不会删除。此开关只控制主题显示，若要禁止提交评论，还需在文章或页面的编辑页中关闭“允许评论”。')
    );
    $commentsEnabled->addRule('enum', $choiceMessage, array('1', '0'));
    $form->addInput($commentsEnabled);

    /* ---------- 搜索与分享 ---------- */
    $seoSiteDescription = new Typecho_Widget_Helper_Form_Element_Textarea(
        'seoSiteDescription',
        null,
        null,
        _t('SEO 站点描述'),
        _t('建议用 80–160 字准确概括博客主题与内容；留空时使用 Typecho 基本设置中的站点描述。')
    );
    $form->addInput($seoSiteDescription);

    $seoDefaultImage = new Typecho_Widget_Helper_Form_Element_Text(
        'seoDefaultImage',
        null,
        null,
        _t('SEO 默认分享图片'),
        _t('用于 Open Graph、Twitter Card 和结构化数据，建议使用 1200×630 的图片。单篇文章可在编辑页“自定义字段”的“分享图片”中单独设置。')
    );
    $seoDefaultImage->addRule('sujianValidSettingAddress', $addressMessage);
    $form->addInput($seoDefaultImage);

    $seoLogo = new Typecho_Widget_Helper_Form_Element_Text(
        'seoLogo',
        null,
        null,
        _t('SEO 站点 Logo'),
        _t('用于结构化数据，建议填写正方形 Logo 的图片地址；留空时尝试使用默认分享图片。')
    );
    $seoLogo->addRule('sujianValidSettingAddress', $addressMessage);
    $form->addInput($seoLogo);

    /* ---------- 页脚 ---------- */
    $footerText = new Typecho_Widget_Helper_Form_Element_Textarea(
        'footerText',
        null,
        null,
        _t('页脚内容'),
        _t('显示在版权信息上方，支持纯文本或 HTML，留空则不显示。可用占位符：{year}、{siteTitle}、{siteUrl}、{description}、{themeName}、{themeNameEn}、{themeNameZh}、{themeUrl}。')
    );
    $form->addInput($footerText);

    $settingsEditor = new \Typecho\Widget\Helper\Layout('');
    $settingsCss = is_file(__DIR__ . '/assets/css/settings-editor.css') ? (string) file_get_contents(__DIR__ . '/assets/css/settings-editor.css') : '';
    $settingsJs = is_file(__DIR__ . '/assets/js/settings-editor.js') ? (string) file_get_contents(__DIR__ . '/assets/js/settings-editor.js') : '';
    $settingsEditor->html('<style>' . $settingsCss . '</style><script>' . $settingsJs . '</script>');
    $form->addItem($settingsEditor);
}

function themeFields($layout)
{
    // Typecho 把每个字段包在一个无 class 的 div 里，宽度随内容收缩；
    // 给输入框一个较大的 cols/size，再配合 w-100，使其像原生字段一样铺满整行（窄屏会自动收缩）。
    $customSummary = new Typecho_Widget_Helper_Form_Element_Textarea(
        'customSummary',
        null,
        null,
        _t('自定义摘要'),
        _t('列表卡片与 SEO 描述优先使用此摘要，留空时自动从正文提取。')
    );
    $customSummary->input->setAttribute('rows', '3');
    $customSummary->input->setAttribute('cols', '120');
    $customSummary->input->setAttribute('class', 'text-s w-100');
    $layout->addItem($customSummary);

    $seoDescription = new Typecho_Widget_Helper_Form_Element_Textarea(
        'seoDescription',
        null,
        null,
        _t('SEO 描述'),
        _t('仅用于页面 Meta Description 与社交分享描述；留空时使用自定义摘要或正文摘要。')
    );
    $seoDescription->input->setAttribute('rows', '2');
    $seoDescription->input->setAttribute('cols', '120');
    $seoDescription->input->setAttribute('class', 'text-s w-100');
    $layout->addItem($seoDescription);

    $seoImage = new Typecho_Widget_Helper_Form_Element_Text(
        'seoImage',
        null,
        null,
        _t('分享图片'),
        _t('用于社交平台分享卡片（Open Graph、Twitter Card）与结构化数据，不会显示在文章页面中。支持 http/https 地址或以 / 开头的站内路径；留空时自动使用正文第一张图片或主题设置中的默认分享图片。')
    );
    $seoImage->input->setAttribute('size', '120');
    $seoImage->input->setAttribute('class', 'text-s w-100');
    $layout->addItem($seoImage);
}
