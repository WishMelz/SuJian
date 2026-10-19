<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('SUJIAN_THEME_NAME', 'SuJian');
define('SUJIAN_THEME_NAME_ZH', '素笺');
define('SUJIAN_THEME_DISPLAY_NAME', SUJIAN_THEME_NAME . ' · ' . SUJIAN_THEME_NAME_ZH);
define('SUJIAN_THEME_VERSION', '1.0.1');
define('SUJIAN_THEME_REPOSITORY', 'https://github.com/WishMelz/SuJian');

function sujianSafeUrl($url, $fallback = '')
{
    $url = trim((string) $url);

    if ($url === '') {
        return $fallback;
    }

    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        return $url;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return $fallback;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, array('http', 'https'), true) ? $url : $fallback;
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

function sujianMarkdownPlainText($text)
{
    $text = preg_replace('/<(script|style|pre|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', (string) $text);
    $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6]|\/blockquote|\/section|\/article)\b[^>]*>/i', "\n", (string) $text);
    $text = preg_replace_callback('/(!?\[[^\]\r\n]*\])\((?<target>(?:[^()\r\n]++|\((?&target)\))*+)\)/u', function ($matches) {
        return $matches[1] . '(' . str_replace(array('(', ')'), array('%28', '%29'), $matches['target']) . ')';
    }, (string) $text);
    $html = \Utils\Markdown::convert(strip_tags((string) $text));
    $html = preg_replace('/<(script|style|pre|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', $html);
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
    foreach (preg_split('/\r\n|\r|\n/', sujianOption($options, 'friendsData')) as $line) {
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
        foreach (preg_split('/\r\n|\r|\n/', $rawNavigation) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));
            $label = isset($parts[0]) ? $parts[0] : '';
            $url = isset($parts[1]) ? $parts[1] : '';
            $target = isset($parts[2]) ? strtolower($parts[2]) : 'self';
            $url = sujianSafeUrl($url);
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

function themeConfig($form)
{
    $siteLogo = new Typecho_Widget_Helper_Form_Element_Text(
        'headerLogo',
        null,
        null,
        _t('网站图标地址'),
        _t('同时用于页头 Logo 和浏览器网站图标，建议使用正方形图片。支持完整的 http/https URL 或以“/”开头的站内相对路径；留空时使用主题默认图片。')
    );
    $form->addInput($siteLogo);

    $friendsEnabled = new Typecho_Widget_Helper_Form_Element_Radio(
        'friendsEnabled',
        array('0' => _t('关闭'), '1' => _t('开启')),
        '0',
        _t('友情链接页面'),
        _t('开启后，在后台新建独立页面，选择“友情链接”模板并发布。关闭后友联页面返回 404，已填写的友联数据仍会保留。')
    );
    $form->addInput($friendsEnabled);

    $friendsData = new Typecho_Widget_Helper_Form_Element_Textarea(
        'friendsData',
        null,
        null,
        _t('友情链接'),
        _t('名字、跳转地址为必填项。头像留空使用默认图片，简介可留空。')
    );
    $friendsData->input->setAttribute('data-sujian-friends', '');
    $form->addInput($friendsData);

    $friendsEditor = new \Typecho\Widget\Helper\Layout('');
    $friendsEditor->html('<style>' . file_get_contents(__DIR__ . '/assets/css/friends-editor.css') . '</style>'
        . '<script>' . file_get_contents(__DIR__ . '/assets/js/friends-editor.js') . '</script>');
    $form->addItem($friendsEditor);

    $navigation = new Typecho_Widget_Helper_Form_Element_Textarea(
        'navigation',
        null,
        "首页|/|self\nWish|https://blog.itsse.cn|blank\n关于|https://github.com/WishMelz/SuJian|blank",
        _t('导航菜单'),
        _t('每行一个导航，格式为“名称|地址|打开方式”。打开方式填写 self（当前标签页）或 blank（新标签页）；省略时默认为 self。地址支持以 / 开头的站内路径或 http/https 地址。')
    );
    $navigation->input->setAttribute('data-sujian-navigation', '');
    $form->addInput($navigation);

    $articleLinkTarget = new Typecho_Widget_Helper_Form_Element_Select(
        'articleLinkTarget',
        array(
            'self' => _t('当前标签页打开'),
            'blank' => _t('新标签页打开')
        ),
        'self',
        _t('文章链接打开方式'),
        _t('控制首页和归档列表中的文章标题、阅读全文链接。选择当前标签页时，文章详情顶部会显示返回按钮。')
    );
    $form->addInput($articleLinkTarget);

    $commentsEnabled = new Typecho_Widget_Helper_Form_Element_Radio(
        'commentsEnabled',
        array('1' => _t('开启'), '0' => _t('关闭')),
        '1',
        _t('全站评论')
    );
    $form->addInput($commentsEnabled);

    $bannerMode = new Typecho_Widget_Helper_Form_Element_Select(
        'bannerMode',
        array(
            'image' => _t('固定图片'),
            'gradient' => _t('固定渐变色'),
            'random' => _t('随机渐变色')
        ),
        'gradient',
        _t('顶部 Banner 样式')
    );
    $form->addInput($bannerMode);

    $bannerImage = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerImage',
        null,
        'https://bing.xzxo.cn/img',
        _t('Banner 固定图片地址'),
        _t('选择固定图片时使用；默认使用 Bing 随机图片，支持站内相对路径或 http/https 图片地址。')
    );
    $form->addInput($bannerImage);

    $bannerGradient = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerGradient',
        null,
        '135deg, #7c3aed 0%, #4f46e5 48%, #0ea5e9 100%',
        _t('Banner 固定渐变色'),
        _t('填写 CSS linear-gradient 括号内的颜色，例如：135deg, #7c3aed 0%, #0ea5e9 100%。')
    );
    $form->addInput($bannerGradient);

    $bannerTitle = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerTitle',
        null,
        null,
        _t('Banner 标题'),
        _t('留空时使用站点标题。')
    );
    $form->addInput($bannerTitle);

    $bannerDescription = new Typecho_Widget_Helper_Form_Element_Text(
        'bannerDescription',
        null,
        null,
        _t('Banner 描述'),
        _t('留空时使用站点描述。')
    );
    $form->addInput($bannerDescription);

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
        _t('用于 Open Graph、Twitter Card 和结构化数据，建议使用 1200×630 的绝对图片地址。文章可用自定义字段 seoImage、cover 或 thumbnail 覆盖。')
    );
    $form->addInput($seoDefaultImage);

    $seoLogo = new Typecho_Widget_Helper_Form_Element_Text(
        'seoLogo',
        null,
        null,
        _t('SEO 站点 Logo'),
        _t('用于结构化数据，建议填写正方形 Logo 的绝对图片地址；留空时尝试使用默认分享图片。')
    );
    $form->addInput($seoLogo);

    $footerText = new Typecho_Widget_Helper_Form_Element_Textarea(
        'footerText',
        null,
        'Theme SuJian · 素笺',
        _t('页脚第一行 HTML'),
        _t('支持 HTML。可用占位符：{year}、{siteTitle}、{siteUrl}、{description}、{themeName}、{themeNameEn}、{themeNameZh}、{themeUrl}。')
    );
    $form->addInput($footerText);

    $accent = new Typecho_Widget_Helper_Form_Element_Select(
        'accent',
        array(
            'zinc' => _t('素笺灰'),
            'violet' => _t('紫罗兰'),
            'blue' => _t('靛蓝'),
            'emerald' => _t('翡翠绿'),
            'rose' => _t('玫瑰红')
        ),
        'zinc',
        _t('主题强调色')
    );
    $form->addInput($accent);

    $settingsEditor = new \Typecho\Widget\Helper\Layout('');
    $settingsEditor->html('<style>' . file_get_contents(__DIR__ . '/assets/css/settings-editor.css') . '</style>'
        . '<script>' . file_get_contents(__DIR__ . '/assets/js/settings-editor.js') . '</script>');
    $form->addItem($settingsEditor);
}
