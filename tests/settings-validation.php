<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// 后台“设置外观”保存时的服务端校验：模拟提交数据，直接运行 Typecho 的表单规则（不写数据库、不发 Cookie）。
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
    if (!class_exists('Typecho_Widget_Helper_Form_Element_' . $element, false)) {
        class_alias('Typecho\\Widget\\Helper\\Form\\Element\\' . $element, 'Typecho_Widget_Helper_Form_Element_' . $element);
    }
}
require dirname(__DIR__) . '/functions.php';

function checkSettings($condition, $label)
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS ', $label, PHP_EOL;
}

/**
 * 以给定的提交数据构建设置表单并运行全部规则，返回 [字段 => 错误信息]。
 * 未提交的字段沿用表单默认值，与 Typecho 保存时“提交整个表单”的情况一致。
 */
function validateSettings(array $submitted)
{
    $_POST = array();
    $_GET = array();
    ob_start();
    $form = new \Typecho\Widget\Helper\Form();
    themeConfig($form);
    $defaults = $form->getValues();
    ob_end_clean();

    $_POST = array_merge(array_map('strval', array_map(function ($value) {
        return $value ?? '';
    }, $defaults)), $submitted);

    // 重新构建一次：导航、友链的错误提示会根据提交内容生成具体的行号
    ob_start();
    $form = new \Typecho\Widget\Helper\Form();
    themeConfig($form);
    ob_end_clean();

    $rules = array();
    foreach ($form->getInputs() as $name => $input) {
        $rules[$name] = $input->rules;
    }
    $result = (new \Typecho\Validate())->run($_POST, $rules);
    $_POST = array();
    return $result;
}

checkSettings(validateSettings(array()) === array(), 'default settings pass validation');

// 图片地址：与前台实际可用的地址规则一致
foreach (array('headerLogo', 'seoDefaultImage', 'seoLogo') as $name) {
    foreach (array('https://cdn.example.test/logo.png', '//cdn.example.test/logo.png', '/usr/uploads/logo.png', 'uploads/logo.png', 'https://cdn.example.test/图片.png', '') as $value) {
        checkSettings(!isset(validateSettings(array($name => $value))[$name]), $name . ' accepts ' . ($value === '' ? '(empty)' : $value));
    }
    foreach (array('javascript:alert(1)', 'data:image/png;base64,AAAA', 'ftp://example.test/logo.png', 'https:broken', 'https://bad host/logo.png') as $value) {
        $errors = validateSettings(array($name => $value));
        checkSettings(isset($errors[$name]) && strpos($errors[$name], '图片地址') !== false, $name . ' rejects ' . $value);
    }
}

// 题图：只在“文字 + 题图”模式下检查，隐藏的旧值不阻止保存
checkSettings(isset(validateSettings(array('bannerMode' => 'image', 'bannerImage' => 'javascript:alert(1)'))['bannerImage']), 'banner image validated in image mode');
checkSettings(!isset(validateSettings(array('bannerMode' => 'text', 'bannerImage' => 'javascript:alert(1)'))['bannerImage']), 'hidden banner image does not block saving');
checkSettings(!isset(validateSettings(array('bannerMode' => 'image', 'bannerImage' => ''))['bannerImage']), 'empty banner image falls back to text');

// 渐变：只在“文字 + 渐变装饰线”模式下检查
checkSettings(!isset(validateSettings(array('bannerMode' => 'gradient', 'bannerGradient' => '90deg, #000 0%, #fff 100%'))['bannerGradient']), 'valid gradient accepted');
foreach (array('red;}body{display:none', '#fff', 'url(x)' . "\n") as $value) {
    checkSettings(isset(validateSettings(array('bannerMode' => 'gradient', 'bannerGradient' => $value))['bannerGradient']), 'gradient rejects ' . json_encode($value));
}
checkSettings(!isset(validateSettings(array('bannerMode' => 'random', 'bannerGradient' => 'red;}'))['bannerGradient']), 'hidden gradient does not block saving');

// 导航：错误提示带出无效的项（按非空行计数）
checkSettings(!isset(validateSettings(array('navigation' => "首页|/|self\n关于|/about.html\nGitHub|https://github.com|blank"))['navigation']), 'valid navigation accepted');
$errors = validateSettings(array('navigation' => "首页|/|self\n\n只有名称\n|/empty-name|self\n相对|about.html|self\n脚本|javascript:alert(1)|self"));
checkSettings(isset($errors['navigation']) && strpos($errors['navigation'], '第 2、3、4、5 项') !== false, 'navigation error lists invalid items: ' . ($errors['navigation'] ?? ''));

// 友链：只在开启时检查，错误提示带出无效的条目
$validFriends = "Ldo|https://cdn.example.test/a.png||https://linux.do/|社区\nLocal|||/friends/|";
checkSettings(!isset(validateSettings(array('friendsEnabled' => '1', 'friendsData' => $validFriends))['friendsData']), 'valid friends accepted');
$badFriends = "Ldo|||https://linux.do/|ok\n缺地址||||无地址\n|||https://no-name.test/|\n坏地址|||javascript:alert(1)|\n坏头像|javascript:alert(1)||https://ok.test/|";
$errors = validateSettings(array('friendsEnabled' => '1', 'friendsData' => $badFriends));
checkSettings(isset($errors['friendsData']) && strpos($errors['friendsData'], '第 2、3、4、5 条') !== false, 'friends error lists invalid entries including avatar: ' . ($errors['friendsData'] ?? ''));
checkSettings(!isset(validateSettings(array('friendsEnabled' => '0', 'friendsData' => $badFriends))['friendsData']), 'disabled friends do not block saving');

// 下拉框与单选：拒绝被篡改的值
foreach (array('accent' => 'neon', 'headingFont' => 'comic', 'articleLinkTarget' => '_top', 'bannerMode' => 'video', 'friendsEnabled' => 'yes', 'commentsEnabled' => '2') as $name => $value) {
    $errors = validateSettings(array($name => $value));
    checkSettings(isset($errors[$name]), $name . ' rejects unknown value ' . $value);
}
foreach (array('accent' => 'cinnabar', 'headingFont' => 'sans', 'articleLinkTarget' => 'blank', 'bannerMode' => 'random', 'friendsEnabled' => '1', 'commentsEnabled' => '0') as $name => $value) {
    checkSettings(!isset(validateSettings(array($name => $value))[$name]), $name . ' accepts ' . $value);
}

// 页脚允许任意 HTML，不做限制
checkSettings(!isset(validateSettings(array('footerText' => '<a href="/about">关于</a> · {year}'))['footerText']), 'footer HTML accepted');
