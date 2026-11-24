<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$footerHtml = trim(sujianFooterHtml(sujianOption($this->options, 'footerText'), $this->options));
$footerPlain = sujianPlainText($footerHtml);
// 页脚自定义内容里已经写了主题名时，不再重复显示主题署名
$showThemeCredit = stripos($footerPlain, SUJIAN_THEME_NAME) === false && strpos($footerPlain, SUJIAN_THEME_NAME_ZH) === false;
$scriptVersion = is_file(__DIR__ . '/assets/js/sujian.js') ? (string) filemtime(__DIR__ . '/assets/js/sujian.js') : SUJIAN_THEME_VERSION;
$instantVersion = is_file(__DIR__ . '/assets/js/instantpage.js') ? (string) filemtime(__DIR__ . '/assets/js/instantpage.js') : SUJIAN_THEME_VERSION;
?>
<footer class="site-footer">
    <div class="container footer-cont">
        <?php if ($footerPlain !== '' || strpos($footerHtml, '<img') !== false): ?>
        <div class="footer-message"><?php echo $footerHtml; ?></div>
        <?php endif; ?>
        <div class="footer-meta">
            <span>© <?php echo date('Y'); ?> <?php echo htmlspecialchars(sujianSiteDisplayTitle($this->options), ENT_QUOTES, 'UTF-8'); ?></span>
            <span>由 <a href="https://typecho.org" target="_blank" rel="noopener noreferrer">Typecho</a> 驱动<?php if ($showThemeCredit): ?> · 主题 <a href="<?php echo htmlspecialchars(SUJIAN_THEME_REPOSITORY, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars(SUJIAN_THEME_NAME_ZH, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?></span>
        </div>
    </div>
</footer>
<button class="sj-back-to-top" type="button" data-back-to-top aria-label="回到顶部" title="回到顶部">
    <svg class="sj-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"></path></svg>
</button>
<?php $this->footer(); ?>
<script defer src="<?php $this->options->themeUrl('assets/js/sujian.js'); ?>?v=<?php echo $scriptVersion; ?>"></script>
<?php if (!$this->is('post') && !$this->is('page') && !$this->is('archive', '404') && is_file(__DIR__ . '/assets/js/instantpage.js')): ?>
<script type="module" src="<?php $this->options->themeUrl('assets/js/instantpage.js'); ?>?v=<?php echo $instantVersion; ?>"></script>
<?php endif; ?>
</body>
</html>
