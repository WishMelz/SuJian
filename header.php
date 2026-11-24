<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$siteTitle = sujianSiteDisplayTitle($this->options);
$siteDescription = sujianPlainText(sujianOption($this->options, 'seoSiteDescription', (string) $this->options->description));
$siteUrl = rtrim((string) $this->options->siteUrl, '/') . '/';
$language = sujianLanguage($this->options);
$pageTitle = $siteTitle;
$currentPage = max(1, (int) $this->getCurrentPage());
$is404 = $this->is('archive', '404');
$pageUrl = (string) $this->getArchiveUrl() ?: $siteUrl;

if ($currentPage > 1 || $is404) {
    $pageUrl = (string) $this->request->getRequestUrl();
    $pageUrl = preg_replace('/[?#].*$/', '', $pageUrl);
}
$pageUrl = sujianAbsoluteUrl($pageUrl, $siteUrl);

if ($this->is('post') || $this->is('page')) {
    $singleTitle = sujianContentTitle($this, $this->options);
    $pageTitle = $singleTitle ? $singleTitle . ' · ' . $siteTitle : $siteTitle;
} elseif (!$this->is('index')) {
    ob_start();
    $this->archiveTitle(array(
        'category' => _t('分类：%s'),
        'search' => _t('搜索：%s'),
        'tag' => _t('标签：%s'),
        'author' => _t('%s 的文章'),
        'date' => _t('%s 归档')
    ), '', '');
    $archiveTitle = trim(ob_get_clean());
    $pageTitle = $archiveTitle ? $archiveTitle . ' · ' . $siteTitle : $siteTitle;
}
if ($currentPage > 1) {
    $pageTitle .= ' · 第 ' . $currentPage . ' 页';
}

$seoDescription = sujianSeoDescription($siteDescription, $siteTitle);
if ($this->is('post') || $this->is('page')) {
    $customDescription = $this->fields->seoDescription ?: $this->fields->seo_d ?: $this->fields->customSummary;
    $descriptionSource = $customDescription ? sujianPlainText($customDescription) : sujianSeoContentText($this->content);
    $seoDescription = sujianSeoDescription($descriptionSource, $siteDescription);
} elseif ($this->is('category') || $this->is('tag')) {
    $archiveDescription = method_exists($this, 'getArchiveDescription')
        ? $this->getArchiveDescription()
        : $this->getDescription();
    if ($archiveDescription) {
        $seoDescription = sujianSeoDescription(sujianPlainText($archiveDescription), $siteDescription);
    }
}
$seoTitle = sujianPlainText($pageTitle);
$robots = ($this->is('search') || $this->is('author') || $is404)
    ? 'noindex, follow, max-image-preview:large'
    : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$seoImage = sujianSeoImage($this, $this->options);
$seoImageAlt = $this->is('post') || $this->is('page') ? sujianContentTitle($this, $this->options) : $siteTitle;
$articleAuthorName = $this->is('post') ? sujianPlainText($this->author->screenName) : '';
$seoLogo = sujianSafeUrl(sujianAbsoluteUrl(
    sujianOption($this->options, 'seoLogo', sujianOption($this->options, 'seoDefaultImage')),
    $siteUrl
));
$publishedTime = $this->is('post') ? date(DATE_W3C, (int) $this->created) : '';
$modifiedTime = $this->is('post') ? date(DATE_W3C, (int) ($this->modified ?: $this->created)) : '';
$articleSection = '';
$articleTags = array();
if ($this->is('post')) {
    if (!empty($this->categories)) {
        $articleSection = sujianPlainText($this->categories[0]['name']);
    }
    foreach ((array) $this->tags as $tag) {
        if (!empty($tag['name'])) {
            $articleTags[] = sujianPlainText($tag['name']);
        }
    }
}
$seoKeywords = $this->is('post')
    ? array_values(array_unique($articleTags))
    : array();

$publisherSchema = null;
if ($seoLogo !== '') {
    $publisherSchema = array(
        '@type' => 'Organization',
        '@id' => $siteUrl . '#publisher',
        'name' => $siteTitle,
        'url' => $siteUrl,
        'logo' => array('@type' => 'ImageObject', 'url' => $seoLogo)
    );
}

$websiteSchema = array(
    '@type' => 'WebSite',
    '@id' => $siteUrl . '#website',
    'url' => $siteUrl,
    'name' => $siteTitle,
    'description' => sujianSeoDescription($siteDescription, $siteTitle),
    'inLanguage' => $language,
    'potentialAction' => array(
        '@type' => 'SearchAction',
        'target' => array('@type' => 'EntryPoint', 'urlTemplate' => $siteUrl . '?s={search_term_string}'),
        'query-input' => 'required name=search_term_string'
    )
);
if ($publisherSchema) {
    $websiteSchema['publisher'] = array('@id' => $siteUrl . '#publisher');
}

$webPageSchema = array(
    '@type' => $is404 ? 'WebPage' : ($this->is('search') ? 'SearchResultsPage' : 'WebPage'),
    '@id' => $pageUrl . '#webpage',
    'url' => $pageUrl,
    'name' => $seoTitle,
    'description' => $seoDescription,
    'isPartOf' => array('@id' => $siteUrl . '#website'),
    'inLanguage' => $language
);
if ($seoImage !== '' && !$is404) {
    $webPageSchema['primaryImageOfPage'] = array('@type' => 'ImageObject', 'url' => $seoImage);
}

$schemaGraph = $publisherSchema ? array($publisherSchema, $websiteSchema) : array($websiteSchema);
if (!$is404) {
    $schemaGraph[] = $webPageSchema;
}
if ($this->is('post')) {
    $articleAuthorSchema = array('@type' => 'Person', 'name' => $articleAuthorName ?: $siteTitle);
    $articleSchema = array(
        '@type' => 'BlogPosting',
        '@id' => $pageUrl . '#article',
        'mainEntityOfPage' => array('@id' => $pageUrl . '#webpage'),
        'headline' => sujianPlainText($this->title),
        'description' => $seoDescription,
        'datePublished' => $publishedTime,
        'dateModified' => $modifiedTime,
        'author' => $articleAuthorSchema,
        'publisher' => $publisherSchema ? array('@id' => $siteUrl . '#publisher') : $articleAuthorSchema,
        'inLanguage' => $language
    );
    if ($seoImage !== '') {
        $articleSchema['image'] = array($seoImage);
    }
    if ($articleSection !== '') {
        $articleSchema['articleSection'] = $articleSection;
    }
    if ($articleTags) {
        $articleSchema['keywords'] = $articleTags;
    }
    $schemaGraph[] = $articleSchema;
}
if ($this->is('post') || $this->is('page')) {
    $breadcrumbItems = array(
        array('@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => $siteUrl)
    );
    if ($this->is('post') && !empty($this->categories) && !empty($this->categories[0]['name']) && !empty($this->categories[0]['permalink'])) {
        $breadcrumbItems[] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => sujianPlainText($this->categories[0]['name']),
            'item' => sujianAbsoluteUrl((string) $this->categories[0]['permalink'], $siteUrl)
        );
    }
    $breadcrumbItems[] = array(
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => sujianContentTitle($this, $this->options),
        'item' => $pageUrl
    );
    $schemaGraph[] = array(
        '@type' => 'BreadcrumbList',
        '@id' => $pageUrl . '#breadcrumb',
        'itemListElement' => $breadcrumbItems
    );
}
$navigation = sujianNavigation($this->options);
foreach ($navigation as &$navigationItem) {
    $itemUrl = sujianAbsoluteUrl($navigationItem['url'], $siteUrl);
    $itemComparableUrl = rtrim((string) preg_replace('/[?#].*$/', '', $itemUrl), '/');
    $pageComparableUrl = rtrim((string) preg_replace('/[?#].*$/', '', $pageUrl), '/');
    $siteComparableUrl = rtrim($siteUrl, '/');
    $navigationItem['current'] = $this->is('index')
        ? $itemComparableUrl === $siteComparableUrl
        : $itemComparableUrl === $pageComparableUrl;
}
unset($navigationItem);
$accent = sujianAccent($this->options);
$headingFont = sujianHeadingFont($this->options);
$themeVersion = is_file(__DIR__ . '/style.css') ? (string) filemtime(__DIR__ . '/style.css') : SUJIAN_THEME_VERSION;
$markdownVersion = is_file(__DIR__ . '/ghmd.css') ? (string) filemtime(__DIR__ . '/ghmd.css') : $themeVersion;
$typechoHeaderRule = 'description=0&keywords=0&generator=0&template=0&social=0&antiSpam=';
if (!sujianCommentsEnabled($this->options) || (method_exists($this, 'allow') && !$this->allow('comment'))) {
    $typechoHeaderRule .= '&commentReply=';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language, ENT_QUOTES, 'UTF-8'); ?>" data-accent="<?php echo htmlspecialchars($accent, ENT_QUOTES, 'UTF-8'); ?>" data-heading-font="<?php echo htmlspecialchars($headingFont, ENT_QUOTES, 'UTF-8'); ?>" data-theme-pref="system">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f8f6f1">
    <script>(function(){var r=document.documentElement,p='system',d;try{var t=localStorage.getItem('sujian-color-scheme')||localStorage.getItem('wish-color-scheme');if(t==='light'||t==='dark')p=t;}catch(e){}d=p!=='system'?p:(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');r.setAttribute('data-theme',d);r.setAttribute('data-theme-pref',p);var m=document.querySelector('meta[name="theme-color"]');if(m)m.setAttribute('content',d==='dark'?'#171614':'#f8f6f1');})();</script>
    <title><?php echo htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($articleAuthorName !== ''): ?><meta name="author" content="<?php echo htmlspecialchars($articleAuthorName, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <?php if ($seoKeywords): ?><meta name="keywords" content="<?php echo htmlspecialchars(implode(', ', $seoKeywords), ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <meta name="application-name" content="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="generator" content="Typecho with <?php echo htmlspecialchars(SUJIAN_THEME_DISPLAY_NAME, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-name" content="<?php echo htmlspecialchars(SUJIAN_THEME_DISPLAY_NAME, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-name-zh" content="<?php echo htmlspecialchars(SUJIAN_THEME_NAME_ZH, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-repository" content="<?php echo htmlspecialchars(SUJIAN_THEME_REPOSITORY, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="<?php echo $robots; ?>">
    <meta name="googlebot" content="<?php echo $robots; ?>">
    <meta name="bingbot" content="<?php echo $robots; ?>">
    <?php if (!$is404): ?><link rel="canonical" href="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <meta property="og:type" content="<?php echo $this->is('post') ? 'article' : 'website'; ?>">
    <meta property="og:locale" content="<?php echo htmlspecialchars(str_replace('-', '_', $language), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!$is404): ?><meta property="og:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <?php if ($seoImage !== ''): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($seoImageAlt, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($this->is('post')): ?>
    <meta property="article:published_time" content="<?php echo $publishedTime; ?>">
    <meta property="article:modified_time" content="<?php echo $modifiedTime; ?>">
    <?php if ($articleSection !== ''): ?><meta property="article:section" content="<?php echo htmlspecialchars($articleSection, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <?php foreach ($articleTags as $articleTag): ?><meta property="article:tag" content="<?php echo htmlspecialchars($articleTag, ENT_QUOTES, 'UTF-8'); ?>"><?php endforeach; ?>
    <?php endif; ?>
    <meta name="twitter:card" content="<?php echo $seoImage !== '' ? 'summary_large_image' : 'summary'; ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($seoImage !== ''): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($seoImageAlt, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php $siteLogo = sujianHeaderLogo($this->options); ?>
    <link rel="icon" href="<?php echo htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>?v=<?php echo $themeVersion; ?>">
    <?php if ($headingFont === 'serif-web'): ?>
    <link rel="preconnect" href="<?php echo htmlspecialchars(SUJIAN_SERIF_FONT_ORIGIN, ENT_QUOTES, 'UTF-8'); ?>" crossorigin>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SUJIAN_SERIF_FONT_CSS, ENT_QUOTES, 'UTF-8'); ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo htmlspecialchars(SUJIAN_SERIF_FONT_CSS, ENT_QUOTES, 'UTF-8'); ?>"></noscript>
    <?php endif; ?>
    <?php if ($this->is('post') || $this->is('page')): ?><link rel="stylesheet" href="<?php $this->options->themeUrl('ghmd.css'); ?>?v=<?php echo $markdownVersion; ?>"><?php endif; ?>
    <?php sujianJsonLd(array('@context' => 'https://schema.org', '@graph' => $schemaGraph)); ?>
    <?php sujianTypechoHeader($this, $typechoHeaderRule); ?>
</head>
<body>
<a class="skip-link" href="#main">跳至正文</a>
<header class="site-header" id="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?> 首页">
            <span class="brand-mark" aria-hidden="true"><img src="<?php echo htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="36" height="36"></span>
            <span class="brand-copy">
                <strong><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if ((string) $this->options->description !== ''): ?><small><?php echo htmlspecialchars((string) $this->options->description, ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
            </span>
        </a>
        <nav class="desktop-nav" aria-label="主导航">
            <?php foreach ($navigation as $item): ?><a<?php if ($item['current']): ?> class="is-active" aria-current="page"<?php endif; ?> href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo sujianNavigationLinkAttributes($item); ?>><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <button class="icon-button" type="button" data-modal-open="sujian-search" aria-haspopup="dialog" aria-expanded="false" aria-controls="sujian-search" aria-label="打开搜索" title="搜索 (/)">
                <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
            </button>
            <div class="sj-theme-switch" data-theme-switch>
                <button class="icon-button" type="button" data-theme-trigger aria-haspopup="menu" aria-expanded="false" aria-controls="sujian-theme-menu" aria-label="选择外观" title="选择外观">
                    <svg class="sj-icon theme-icon-light" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
                    <svg class="sj-icon theme-icon-dark" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg>
                    <svg class="sj-icon theme-icon-system" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>
                </button>
                <div class="sj-menu" id="sujian-theme-menu" role="menu" aria-label="选择外观" hidden>
                    <button class="sj-menu-item" type="button" role="menuitemradio" aria-checked="false" data-theme-value="light">
                        <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
                        <span>浅色</span>
                        <svg class="sj-icon sj-menu-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                    </button>
                    <button class="sj-menu-item" type="button" role="menuitemradio" aria-checked="false" data-theme-value="dark">
                        <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg>
                        <span>深色</span>
                        <svg class="sj-icon sj-menu-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                    </button>
                    <button class="sj-menu-item" type="button" role="menuitemradio" aria-checked="false" data-theme-value="system">
                        <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>
                        <span>跟随系统</span>
                        <svg class="sj-icon sj-menu-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                    </button>
                </div>
            </div>
            <button class="icon-button mobile-menu-button" type="button" data-modal-open="sujian-drawer" aria-haspopup="dialog" aria-expanded="false" aria-controls="sujian-drawer" aria-label="打开菜单">
                <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
            </button>
        </div>
    </div>
</header>
<div class="sj-modal sj-modal-dialog" id="sujian-search" hidden>
    <div class="sj-backdrop" data-modal-close aria-hidden="true"></div>
    <div class="sj-dialog" role="dialog" aria-modal="true" aria-labelledby="sujian-search-title" tabindex="-1">
        <div class="sj-dialog-header">
            <h2 id="sujian-search-title">搜索文章</h2>
            <button class="icon-button sj-close" type="button" data-modal-close aria-label="关闭搜索">
                <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <form class="sj-search-form" method="get" action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>" role="search">
            <label class="sr-only" for="sujian-search-input">关键词</label>
            <input id="sujian-search-input" type="search" name="s" placeholder="搜索文章、代码或关键词…" autocomplete="off" required data-autofocus>
            <button class="button button-primary sj-search-submit" type="submit">
                <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
                搜索
            </button>
        </form>
        <p class="sj-search-hint">按 <kbd>/</kbd> 或 <kbd data-shortcut-mod>Ctrl+K</kbd> 快速打开，<kbd>Esc</kbd> 关闭</p>
    </div>
</div>
<div class="sj-modal sj-modal-drawer" id="sujian-drawer" hidden>
    <div class="sj-backdrop" data-modal-close aria-hidden="true"></div>
    <div class="sj-drawer" role="dialog" aria-modal="true" aria-labelledby="sujian-drawer-title" tabindex="-1">
        <div class="sj-drawer-header">
            <h2 id="sujian-drawer-title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <button class="icon-button sj-close" type="button" data-modal-close aria-label="关闭菜单">
                <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <nav class="sj-drawer-nav" aria-label="移动端导航">
            <?php foreach ($navigation as $item): ?><a<?php if ($item['current']): ?> class="is-active" aria-current="page"<?php endif; ?> href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo sujianNavigationLinkAttributes($item); ?> data-modal-link><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?>
        </nav>
    </div>
</div>
