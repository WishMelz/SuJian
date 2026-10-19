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

if (in_array('--render', $argv, true) || in_array('--render-checks', $argv, true)) {
    define('__TYPECHO_ADMIN__', true);
    ob_start();
    require __TYPECHO_ROOT_DIR__ . '/admin/form-js.php';
    $formScript = ob_get_clean();
    $checks = '';
    if (in_array('--render-checks', $argv, true)) {
        $checks = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const form = document.querySelector('.sujian-settings');
    const result = document.createElement('pre');
    result.id = 'settings-check-results';
    document.body.prepend(result);
    const preventSubmit = function (event) { event.preventDefault(); };
    form.addEventListener('submit', preventSubmit);
    let passed = 0;
    function check(condition, label) {
        if (!condition) throw new Error(label);
        passed++;
    }
    try {
        const mode = form.elements.bannerMode;
        mode.value = 'image';
        mode.dispatchEvent(new Event('change', { bubbles: true }));
        const encodedPath = '/uploads/%E5%9B%BE%E6%A0%87.png';
        const cases = [
            ['https://cdn.example.test/\u56fe\u6807.png', 'https://cdn.example.test/%E5%9B%BE%E6%A0%87.png'],
            ['https://\u4f8b\u5b50.\u6d4b\u8bd5/\u56fe\u7247.png', 'https://xn--fsqu00a.xn--0zwm56d/%E5%9B%BE%E7%89%87.png'],
            ['/uploads/\u56fe\u6807.png', encodedPath],
            ['uploads/\u56fe\u6807.png', new URL('uploads/%E5%9B%BE%E6%A0%87.png', new URL('../', location.href)).href],
            [encodedPath + '?v=1&size=32#icon', encodedPath + '?v=1&size=32#icon'],
            ['', ''],
            ['   ', '']
        ];
        for (const name of ['headerLogo', 'seoDefaultImage', 'seoLogo', 'bannerImage']) {
            const field = form.elements[name];
            for (const [raw, expected] of cases) {
                field.value = raw;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                check(field.checkValidity(), name + ': valid input');
                check(new FormData(form).get(name) === expected, name + ': normalized form data ' + raw);
                check(new FormData(form).getAll(name).length === 1, name + ': single canonical field');
            }
            for (const unsafe of ['javascript:alert(1)', 'data:image/png;base64,AAAA', 'ftp://example.test/icon.png', 'https:broken']) {
                field.value = unsafe;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                check(!field.checkValidity(), name + ': reject ' + unsafe);
            }
            field.value = '/uploads/\u56fe\u6807.png';
            field.dispatchEvent(new Event('change', { bubbles: true }));
            check(field.value === encodedPath && field.checkValidity(), name + ': change normalizes and clears error');
            field.value = '/uploads/\u56fe\u6807.png';
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            check(field.value === encodedPath, name + ': submit normalizes without network');
        }
        form.elements.bannerImage.value = 'javascript:alert(1)';
        mode.value = 'random';
        mode.dispatchEvent(new Event('change', { bubbles: true }));
        check(form.elements.bannerImage.checkValidity(), 'hidden banner does not block saving');
        mode.value = 'image';
        mode.dispatchEvent(new Event('change', { bubbles: true }));
        check(!form.elements.bannerImage.checkValidity(), 'visible banner validates again');
        form.reset();
        await new Promise(resolve => setTimeout(resolve, 0));
        for (const name of ['headerLogo', 'seoDefaultImage', 'seoLogo', 'bannerImage']) {
            check(form.elements[name].value === form.elements[name].defaultValue, name + ': reset restores value');
            check(form.elements[name].checkValidity(), name + ': reset clears errors');
        }
        result.dataset.status = 'passed';
        result.textContent = 'PASS ' + passed + ' browser assertions';
    } catch (error) {
        result.dataset.status = 'failed';
        result.textContent = 'FAIL ' + error.message;
    } finally {
        form.reset();
        form.removeEventListener('submit', preventSubmit);
    }
}, { once: true });
</script>
HTML;
    }
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
        . '</script>' . $formScript . $checks . '</body></html>';
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
checkFriendsEditor(strpos($html, "label: '站点地址'") === false, 'editor no longer exposes the display address field');
checkFriendsEditor(strpos($html, '跳转地址') !== false, 'editor exposes the destination field');
checkFriendsEditor(substr_count($html, 'name="navigation"') === 1, 'one canonical navigation field');
checkFriendsEditor(strpos($html, 'data-sujian-navigation=""') !== false, 'navigation editor mount is present');
checkFriendsEditor($form->getInput('navigation')->input->getAttribute('hidden') === null, 'navigation remains editable without JavaScript');
checkFriendsEditor(strpos($html, 'function initializeNavigation') > strpos($html, 'name="navigation"'), 'navigation enhancement follows native fields');
foreach (array('headerLogo', 'friendsEnabled', 'friendsData', 'navigation', 'articleLinkTarget', 'commentsEnabled', 'bannerMode', 'bannerImage', 'bannerGradient', 'bannerTitle', 'bannerDescription', 'seoSiteDescription', 'seoDefaultImage', 'seoLogo', 'footerText', 'accent') as $name) {
    checkFriendsEditor(isset($form->getInputs()[$name]), 'native setting remains registered: ' . $name);
}
checkFriendsEditor($form->getValues()['commentsEnabled'] === '1', 'global comments default to enabled');
checkFriendsEditor(strpos($html, "['comments', '评论', ['commentsEnabled']]") !== false, 'comments setting has its own group');
checkFriendsEditor(substr_count($html, 'name="bannerGradient"') === 1, 'one canonical gradient field');
checkFriendsEditor(strpos($html, 'function initializeGradient') !== false, 'gradient editor is included');
checkFriendsEditor(strpos($html, 'sujian-settings-section') !== false, 'settings grouping is included');
checkFriendsEditor(strpos($html, "addEventListener('formdata'") !== false, 'enhanced controls synchronize form payloads');

$unsafe = '</textarea><script>alert(1)</script>';
$form->getInput('friendsData')->value($unsafe);
ob_start();
$form->render();
$escapedHtml = ob_get_clean();
checkFriendsEditor(strpos($escapedHtml, $unsafe) === false, 'stored text cannot escape textarea');
checkFriendsEditor(strpos($escapedHtml, htmlspecialchars($unsafe)) !== false, 'stored text is safely escaped');