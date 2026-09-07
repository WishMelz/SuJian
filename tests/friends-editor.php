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
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/I18n.php';
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
foreach (array('Text', 'Textarea', 'Radio', 'Select') as $element) {
    class_alias('Typecho\\Widget\\Helper\\Form\\Element\\' . $element, 'Typecho_Widget_Helper_Form_Element_' . $element);
}
require dirname(__DIR__) . '/functions.php';

function checkFriendsEditor($condition, $label)
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS ', $label, PHP_EOL;
}

$form = new \Typecho\Widget\Helper\Form();
ob_start();
themeConfig($form);
$earlyOutput = ob_get_clean();
$stored = "Existing|/avatar.png|https://friend.test||One | Two\r\nSecond||/second/||";
$form->getInput('friendsData')->value($stored);
$submit = new \Typecho\Widget\Helper\Form\Element\Submit(null, null, 'Save');
$submit->input->setAttribute('class', 'btn primary');
$form->addItem($submit);
ob_start();
$form->render();
$html = ob_get_clean();

if (in_array('--render', $argv, true)) {
    define('__TYPECHO_ADMIN__', true);
    ob_start();
    require __TYPECHO_ROOT_DIR__ . '/admin/form-js.php';
    $formScript = ob_get_clean();
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Friends editor test</title><style>'
        . file_get_contents(__TYPECHO_ROOT_DIR__ . '/admin/css/normalize.css')
        . file_get_contents(__TYPECHO_ROOT_DIR__ . '/admin/css/grid.css')
        . file_get_contents(__TYPECHO_ROOT_DIR__ . '/admin/css/style.css')
        . '</style></head><body><main class="main"><div class="body container">'
        . '<div class="row typecho-page-main"><div class="col-mb-12 col-tb-8 col-tb-offset-2">'
        . $html . '</div></div></div></main><script>'
        . file_get_contents(__TYPECHO_ROOT_DIR__ . '/admin/js/jquery.js')
        . '</script>' . $formScript . '</body></html>';
    exit;
}

checkFriendsEditor($earlyOutput === '', 'building settings emits no premature markup');
checkFriendsEditor(isset($form->getInputs()['friendsData']), 'native save field stays registered');
checkFriendsEditor($form->getValues()['friendsData'] === $stored, 'stored multiline data is unchanged');
checkFriendsEditor(substr_count($html, 'name="friendsData"') === 1, 'one canonical save field');
checkFriendsEditor(strpos($html, 'data-sujian-friends=""') !== false, 'editor mount is present');
checkFriendsEditor(strpos($html, htmlspecialchars($stored)) !== false, 'stored values are filled before rendering');
checkFriendsEditor(strpos($html, '<script>') > strpos($html, '</textarea>'), 'editor script follows native input');
checkFriendsEditor(strpos($html, '.sujian-friend-row') !== false, 'editor styles included in settings');
checkFriendsEditor(strpos($html, 'textarea[data-sujian-friends]') !== false, 'editor script included in settings');
checkFriendsEditor($form->getInput('friendsData')->input->getAttribute('hidden') === null, 'native fallback remains visible without JavaScript');
checkFriendsEditor(strpos($html, '网站图标地址') !== false, 'shared site logo setting is exposed');
checkFriendsEditor(strpos($html, '页头 Logo 图片地址') === false, 'separate header logo setting is removed');
checkFriendsEditor(strpos($html, '站点地址') === false, 'editor no longer exposes the display address field');
checkFriendsEditor(strpos($html, '跳转地址') !== false, 'editor exposes the destination field');

$unsafe = '</textarea><script>alert(1)</script>';
$form->getInput('friendsData')->value($unsafe);
ob_start();
$form->render();
$escapedHtml = ob_get_clean();
checkFriendsEditor(strpos($escapedHtml, $unsafe) === false, 'stored text cannot escape textarea');
checkFriendsEditor(strpos($escapedHtml, htmlspecialchars($unsafe)) !== false, 'stored text is safely escaped');