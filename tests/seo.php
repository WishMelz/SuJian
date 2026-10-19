<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 4));
require dirname(__DIR__) . '/functions.php';

function checkSeoSame($expected, $actual, $label)
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    echo 'PASS ', $label, PHP_EOL;
}

$siteUrl = 'http://example.test:8080/blog/';
foreach (array('data:image/png;base64,AAAA', 'javascript:alert(1)', 'ftp://example.test/image.png', 'https:broken') as $url) {
    checkSeoSame('', sujianAbsoluteUrl($url, $siteUrl), 'reject unsupported or invalid URL ' . $url);
}
foreach (array(
    'https://cdn.example.test/image.png' => 'https://cdn.example.test/image.png',
    '//cdn.example.test/image.png' => 'http://cdn.example.test/image.png',
    '/image.png' => 'http://example.test:8080/image.png',
    'image.png' => 'http://example.test:8080/blog/image.png'
) as $url => $expected) {
    checkSeoSame($expected, sujianAbsoluteUrl($url, $siteUrl), 'resolve image URL ' . $url);
}

$archive = new class {
    public $fields;
    public $content = '<img src="data:image/png;base64,AAAA">';

    public function is($type)
    {
        return $type === 'post';
    }
};
$archive->fields = (object) array('seoImage' => 'data:image/png;base64,AAAA');
$options = (object) array('siteUrl' => $siteUrl, 'seoDefaultImage' => '/share.png');
checkSeoSame('http://example.test:8080/share.png', sujianSeoImage($archive, $options), 'invalid image uses default share image');
$options->seoDefaultImage = 'data:image/png;base64,AAAA';
checkSeoSame('', sujianSeoImage($archive, $options), 'invalid default image is omitted');
foreach (array(
    '/uploads/%E5%9B%BE%E6%A0%87.png' => 'http://example.test:8080/uploads/%E5%9B%BE%E6%A0%87.png',
    'https://xn--fsqu00a.xn--0zwm56d/%E5%9B%BE%E7%89%87.png' => 'https://xn--fsqu00a.xn--0zwm56d/%E5%9B%BE%E7%89%87.png'
) as $url => $expected) {
    $options->seoDefaultImage = $url;
    checkSeoSame($expected, sujianSeoImage($archive, $options), 'normalized settings image survives PHP validation');
}