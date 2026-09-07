<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 4));
spl_autoload_register(function ($class) {
    $file = __TYPECHO_ROOT_DIR__ . '/var/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
require dirname(__DIR__) . '/functions.php';

function checkSame($expected, $actual, $label)
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    echo 'PASS ', $label, PHP_EOL;
}

foreach (array(140, 160) as $length) {
    $text = str_repeat('a', $length - 2) . "\xE6\x9C\x80" . 'tail';
    $archive = (object) array('fields' => (object) array('customSummary' => $text));
    $result = $length === 140 ? sujianExcerpt($archive, $length) : sujianSeoDescription($text, '', $length);
    checkSame(1, preg_match('//u', $result), 'valid UTF-8 ' . $length);
    checkSame($length, sujianTextLength($result), 'character limit ' . $length);
    checkSame(false, htmlspecialchars($result, ENT_QUOTES, 'UTF-8') === '', 'HTML output ' . $length);
    ob_start();
    sujianJsonLd(array('description' => $result));
    $json = preg_replace('/^<script[^>]*>|<\/script>\s*$/', '', ob_get_clean());
    checkSame($result, json_decode($json, true)['description'], 'JSON-LD output ' . $length);
}

$archive = (object) array('fields' => (object) array(), 'content' => '<p>![image](https://example.test/img) [link](https://example.test) `code`</p>');
checkSame('link code', sujianExcerpt($archive), 'Markdown in plain HTML');
$archive->content = '<p>First</p><p>Second</p>';
checkSame('First Second', sujianExcerpt($archive), 'paragraph spacing');
$archive->content = "```php\nsecret_code();\n```\n\nVisible **text**";
checkSame('Visible text', sujianExcerpt($archive), 'exclude fenced code');
$archive->content = '[nested](https://example.test/path_(one))';
checkSame('nested', sujianExcerpt($archive), 'balanced link parentheses');
$archive->content = '![image](https://example.test/img)';
checkSame('', sujianExcerpt($archive), 'image-only summary');
checkSame('fallback', sujianSeoDescription('', 'fallback'), 'SEO fallback');
$archive->content = '<p>Use &lt;span&gt; here.</p>';
checkSame('Use <span> here.', sujianExcerpt($archive), 'preserve escaped tags in excerpt');
$archive->fields->customSummary = 'Use &lt;span&gt; here.';
checkSame('Use <span> here.', sujianExcerpt($archive), 'preserve escaped tags in custom summary');
$description = sujianSeoDescription(sujianSeoContentText($archive->content));
checkSame('Use <span> here.', $description, 'preserve escaped tags in SEO content');
checkSame('Use &lt;span&gt; here.', htmlspecialchars($description, ENT_QUOTES, 'UTF-8'), 'escape SEO description at HTML output');
checkSame('Use <span> here.', sujianSeoDescription(sujianPlainText('<b>Use</b> &lt;span&gt; here.')), 'normalize raw description once');
checkSame('Use <span> here.', sujianSeoDescription('', sujianPlainText('Use &lt;span&gt; here.')), 'preserve escaped tags in SEO fallback');
checkSame('Keep &copy; literal.', sujianSeoDescription(sujianSeoContentText('<p>Keep &amp;copy; literal.</p>')), 'decode entities once');
echo 'mbstring: ', extension_loaded('mbstring') ? 'enabled' : 'disabled', PHP_EOL;