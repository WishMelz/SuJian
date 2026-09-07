<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$footerText = sujianOption($this->options, 'footerText', SUJIAN_THEME_DISPLAY_NAME . ' · Theme');
?>
<footer class="site-footer" role="contentinfo">
    <div class="footer-cont">
        <div class="container footer-message">
            <?php echo sujianFooterHtml($footerText, $this->options); ?>
        </div>
        <div class="container footer-meta">
            <span>© <?php echo date('Y'); ?> All rights reserved.</span>
            <span>Theme by <a href="<?php echo htmlspecialchars(SUJIAN_THEME_REPOSITORY, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">SuJian</a></span>
        </div>
    </div>
</footer>
<?php $this->footer(); ?>
<script><?php $this->need('assets/js/sujian-script.php'); ?></script>
<?php if (is_file(__DIR__ . '/assets/ui/sujian-ui.js') && is_file(__DIR__ . '/assets/ui/sujian-ui.css')): ?>
<script type="module" src="<?php $this->options->themeUrl('assets/ui/sujian-ui.js'); ?>?v=<?php echo filemtime(__DIR__ . '/assets/ui/sujian-ui.js'); ?>"></script>
<?php endif; ?>
<?php if (!$this->is('post') && !$this->is('page') && !$this->is('archive', '404')): ?>
<script type="module" src="<?php $this->options->themeUrl('assets/js/instantpage.js'); ?>?v=<?php echo rawurlencode(SUJIAN_THEME_VERSION); ?>"></script>
<?php endif; ?>
</body>
</html>
