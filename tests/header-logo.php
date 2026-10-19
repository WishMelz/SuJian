<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 4));
require dirname(__DIR__) . '/functions.php';

function checkHeaderLogoSame($expected, $actual, $label)
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    echo 'PASS ', $label, PHP_EOL;
}

$options = (object) array(
    'siteUrl' => 'https://example.test:8443/blog/',
    'themeUrl' => 'https://example.test:8443/blog/usr/themes/Sujian',
    'seoLogo' => 'https://cdn.example.test/seo-logo.png'
);
$defaultLogo = $options->themeUrl . '/favicon.svg';
checkHeaderLogoSame($defaultLogo, sujianHeaderLogo($options), 'missing option uses theme default independently of SEO logo');

foreach (array(
    array(null, $defaultLogo, 'null option uses default'),
    array('', $defaultLogo, 'empty option uses default'),
    array('   ', $defaultLogo, 'whitespace uses default'),
    array(' https://cdn.example.test/logo.png ', 'https://cdn.example.test/logo.png', 'custom HTTPS URL is trimmed'),
    array('http://cdn.example.test/logo.png', 'http://cdn.example.test/logo.png', 'custom HTTP URL'),
    array('https://cdn.example.test/favicon.ico', 'https://cdn.example.test/favicon.ico', 'custom ICO URL'),
    array('https://cdn.example.test/%E5%9B%BE%E6%A0%87.png', 'https://cdn.example.test/%E5%9B%BE%E6%A0%87.png', 'encoded Unicode image from settings'),
    array('https://xn--fsqu00a.xn--0zwm56d/%E5%9B%BE%E7%89%87.png', 'https://xn--fsqu00a.xn--0zwm56d/%E5%9B%BE%E7%89%87.png', 'normalized international domain from settings'),
    array('/uploads/logo.svg', 'https://example.test:8443/uploads/logo.svg', 'root-relative SVG URL'),
    array('/uploads/favicon.ICO?v=2&size=32', 'https://example.test:8443/uploads/favicon.ICO?v=2&size=32', 'uppercase extension and query are preserved'),
    array('https://cdn.example.test/icon?id=1&size=32', 'https://cdn.example.test/icon?id=1&size=32', 'extensionless image URL'),
    array('//cdn.example.test/logo.png', 'https://cdn.example.test/logo.png', 'protocol-relative URL'),
    array('/uploads/logo.png', 'https://example.test:8443/uploads/logo.png', 'root-relative URL preserves port'),
    array('uploads/logo.png', 'https://example.test:8443/blog/uploads/logo.png', 'site-relative URL preserves subdirectory'),
    array('javascript:alert(1)', $defaultLogo, 'unsafe scheme uses default'),
    array('data:image/png;base64,AAAA', $defaultLogo, 'data URL uses default'),
    array('ftp://example.test/logo.png', $defaultLogo, 'unsupported scheme uses default'),
    array('https:broken', $defaultLogo, 'malformed URL uses default')
) as $case) {
    $options->headerLogo = $case[0];
    checkHeaderLogoSame($case[1], sujianHeaderLogo($options), $case[2]);
}

$options->headerLogo = '';
$options->themeUrl .= '/';
checkHeaderLogoSame($defaultLogo, sujianHeaderLogo($options), 'default handles trailing theme URL slash');

class HeaderLogoTestOptions extends stdClass
{
    public function charset()
    {
        echo 'UTF-8';
    }

    public function themeUrl($path)
    {
        echo htmlspecialchars(rtrim($this->themeUrl, '/') . '/' . $path, ENT_QUOTES, 'UTF-8');
    }
}

class HeaderLogoTestArchive
{
    public $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function is($type, $value = null)
    {
        return $type === 'index';
    }

    public function getCurrentPage()
    {
        return 1;
    }

    public function getArchiveUrl()
    {
        return $this->options->siteUrl;
    }

    public function header($rule)
    {
    }

    public function render()
    {
        ob_start();
        require dirname(__DIR__) . '/header.php';
        return ob_get_clean();
    }
}

$renderOptions = new HeaderLogoTestOptions();
foreach (get_object_vars($options) as $key => $value) {
    $renderOptions->{$key} = $value;
}
$renderOptions->title = 'Logo test';
$renderOptions->description = 'Site description';
$renderOptions->lang = 'en-US';
$archive = new HeaderLogoTestArchive($renderOptions);
foreach (array(
    array('', $defaultLogo, 'default'),
    array('/images/site.ico', 'https://example.test:8443/images/site.ico', 'ICO'),
    array('https://cdn.example.test/site.png', 'https://cdn.example.test/site.png', 'PNG'),
    array('/images/site.svg', 'https://example.test:8443/images/site.svg', 'SVG'),
    array('/uploads/%E5%9B%BE%E6%A0%87.png', 'https://example.test:8443/uploads/%E5%9B%BE%E6%A0%87.png', 'encoded Unicode path'),
    array('/icon?id=1&size=32', 'https://example.test:8443/icon?id=1&size=32', 'extensionless query'),
    array('https://cdn.example.test/icon?v="test"&size=32', 'https://cdn.example.test/icon?v="test"&size=32', 'quoted query'),
    array('javascript:alert(1)', $defaultLogo, 'unsafe fallback')
) as $case) {
    $renderOptions->headerLogo = $case[0];
    $html = $archive->render();
    $document = new DOMDocument();
    $previousErrors = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
    $xpath = new DOMXPath($document);
    $icons = $xpath->query('//link[@rel="icon"]');
    $logos = $xpath->query('//span[@class="brand-mark"]/img');
    checkHeaderLogoSame(1, $icons->length, $case[2] . ' renders one favicon');
    checkHeaderLogoSame(1, $logos->length, $case[2] . ' renders one header logo');
    checkHeaderLogoSame($case[1], $icons->item(0)->getAttribute('href'), $case[2] . ' favicon URL');
    checkHeaderLogoSame($case[1], $logos->item(0)->getAttribute('src'), $case[2] . ' header URL matches favicon');
    checkHeaderLogoSame(false, $icons->item(0)->hasAttribute('type'), $case[2] . ' does not assume an image MIME type');
    checkHeaderLogoSame(true, strpos($html, '<link rel="icon" href="' . htmlspecialchars($case[1], ENT_QUOTES, 'UTF-8') . '">') !== false, $case[2] . ' favicon attribute is escaped');
    checkHeaderLogoSame(true, strpos($html, '<img src="' . htmlspecialchars($case[1], ENT_QUOTES, 'UTF-8') . '" alt="" width="36" height="36">') !== false, $case[2] . ' header attribute is escaped');
}

foreach (array(
    array('', '/share.png', 'https://example.test:8443/share.png', 'empty SEO logo uses default share image'),
    array('   ', '/share.png', 'https://example.test:8443/share.png', 'whitespace SEO logo uses default share image'),
    array(null, '/share.png', 'https://example.test:8443/share.png', 'missing SEO logo uses default share image'),
    array('/publisher.svg', '/share.png', 'https://example.test:8443/publisher.svg', 'explicit SEO logo takes priority'),
    array('', '/uploads/%E5%9B%BE%E6%A0%87.png', 'https://example.test:8443/uploads/%E5%9B%BE%E6%A0%87.png', 'normalized default image remains usable'),
    array('', '', '', 'empty images omit publisher'),
    array('', 'javascript:alert(1)', '', 'unsafe default image is rejected'),
    array('data:image/png;base64,AAAA', '/share.png', '', 'unsafe explicit logo is rejected')
) as $case) {
    $renderOptions->seoLogo = $case[0];
    $renderOptions->seoDefaultImage = $case[1];
    $document = new DOMDocument();
    $previousErrors = libxml_use_internal_errors(true);
    $document->loadHTML($archive->render());
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
    $xpath = new DOMXPath($document);
    $schema = json_decode($xpath->query('//script[@type="application/ld+json"]')->item(0)->textContent, true);
    $publishers = array_values(array_filter($schema['@graph'], function ($item) {
        return $item['@type'] === 'Organization';
    }));
    checkHeaderLogoSame($case[2] === '' ? 0 : 1, count($publishers), $case[3] . ' publisher count');
    if ($case[2] !== '') {
        checkHeaderLogoSame($case[2], $publishers[0]['logo']['url'], $case[3] . ' logo URL');
    }
}