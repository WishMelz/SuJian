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
        'author' => _t('%s 的文章')
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
    sujianOption($this->options, 'seoLogo'),
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
    $schemaGraph[] = array(
        '@type' => 'BreadcrumbList',
        '@id' => $pageUrl . '#breadcrumb',
        'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => $siteUrl),
            array('@type' => 'ListItem', 'position' => 2, 'name' => sujianContentTitle($this, $this->options), 'item' => $pageUrl)
        )
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
$accent = sujianOption($this->options, 'accent', 'zinc');
$themeVersion = (string) filemtime(__DIR__ . '/style.css');
$markdownVersion = is_file(__DIR__ . '/ghmd.css') ? (string) filemtime(__DIR__ . '/ghmd.css') : $themeVersion;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language, ENT_QUOTES, 'UTF-8'); ?>" data-accent="<?php echo htmlspecialchars($accent, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <script>(function(){try{var t=localStorage.getItem('sujian-color-scheme')||localStorage.getItem('wish-color-scheme');var d=t==='light'||t==='dark'?t:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=d;document.querySelector('meta[name="theme-color"]').content=d==='dark'?'#09090b':'#ffffff';}catch(e){}})();</script>
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
    <?php if (is_file(__DIR__ . '/assets/ui/sujian-ui.js') && is_file(__DIR__ . '/assets/ui/sujian-ui.css')): ?><link rel="stylesheet" href="<?php $this->options->themeUrl('assets/ui/sujian-ui.css'); ?>?v=<?php echo filemtime(__DIR__ . '/assets/ui/sujian-ui.css'); ?>" data-sujian-ui-css><?php endif; ?>
    <?php if ($this->is('post') || $this->is('page')): ?><link rel="stylesheet" href="<?php $this->options->themeUrl('ghmd.css'); ?>?v=<?php echo $markdownVersion; ?>"><?php endif; ?>
    <?php sujianJsonLd(array('@context' => 'https://schema.org', '@graph' => $schemaGraph)); ?>
    <?php sujianTypechoHeader($this, 'description=0&keywords=0&generator=0&template=0&social=0&antiSpam='); ?>
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
        <div class="header-actions" data-ui-fallback>
            <button class="icon-button" type="button" data-search-toggle aria-label="打开搜索" aria-controls="site-search-panel" aria-expanded="false">
                <svg class="search-icon-search" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
                <svg class="search-icon-close" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
            <button class="icon-button" type="button" data-theme-toggle aria-label="切换深色模式" aria-pressed="false">
                <svg class="theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
                <svg class="theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg>
            </button>
            <button class="icon-button mobile-menu-button" type="button" data-menu-toggle aria-label="打开菜单" aria-controls="mobile-navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
            </button>
        </div>
        <div class="header-actions" id="sujian-controls" hidden></div>
    </div>
    <div class="search-panel" id="site-search-panel" data-search-panel hidden>
        <form class="container search-form" method="get" action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
            <label class="sr-only" for="header-search">搜索文章</label>
            <input id="header-search" type="search" name="s" placeholder="搜索文章、代码或关键词…" autocomplete="off">
            <kbd>Enter</kbd>
        </form>
    </div>
    <nav class="mobile-nav" id="mobile-navigation" data-mobile-nav hidden aria-label="移动端导航">
        <div class="container">
            <?php foreach ($navigation as $item): ?><a<?php if ($item['current']): ?> class="is-active" aria-current="page"<?php endif; ?> href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo sujianNavigationLinkAttributes($item); ?>><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?>
        </div>
    </nav>
</header>
