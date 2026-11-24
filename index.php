<?php
/**
 * SuJian（素笺）：一款极尽简洁、专注内容的 Typecho 个人博客主题
 *
 * @package SuJian · 素笺
 * @author WishMelz
 * @version 1.0.2
 * @link https://github.com/WishMelz/SuJian
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
$bannerMode = sujianOption($this->options, 'bannerMode', 'text');
$bannerTitle = sujianOption($this->options, 'bannerTitle', sujianSiteDisplayTitle($this->options));
$bannerDescription = sujianOption($this->options, 'bannerDescription', (string) $this->options->description);
$heroImage = $bannerMode === 'image' ? sujianSafeUrl(sujianOption($this->options, 'bannerImage')) : '';
$heroRuleStyle = '';
if ($bannerMode === 'gradient') {
    $heroRuleStyle = 'background-image:linear-gradient(' . sujianSafeGradient(sujianOption($this->options, 'bannerGradient'), sujianBannerGradient('gradient')) . ');';
} elseif ($bannerMode === 'random') {
    $heroRuleStyle = 'background-image:linear-gradient(' . sujianBannerGradient('random') . ');';
}
$isFirstPage = max(1, (int) $this->getCurrentPage()) === 1;
?>
<?php if ($isFirstPage): ?>
<section class="home-hero" aria-label="站点介绍">
    <div class="container">
        <h1 class="hero-title"><?php echo htmlspecialchars($bannerTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($bannerDescription !== ''): ?><p class="hero-desc"><?php echo htmlspecialchars($bannerDescription, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <?php if ($heroImage !== ''): ?>
        <figure class="hero-figure"><img src="<?php echo htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="1440" height="600" decoding="async" fetchpriority="high" onerror="this.parentNode.hidden=true"></figure>
        <?php else: ?>
        <span class="hero-rule" aria-hidden="true"<?php if ($heroRuleStyle !== ''): ?> style="<?php echo htmlspecialchars($heroRuleStyle, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>></span>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<main class="container index-layout<?php if (!$isFirstPage): ?> index-layout-paged<?php endif; ?>" id="main" tabindex="-1" data-list-page>
    <section class="post-stream" aria-label="文章列表">
        <?php if (!$isFirstPage): ?><h1 class="sr-only">文章列表 · 第 <?php echo (int) $this->getCurrentPage(); ?> 页</h1><?php endif; ?>
        <div class="post-list">
            <?php while ($this->next()): ?>
            <?php $this->need('partials/post-item.php'); ?>
            <?php endwhile; ?>
        </div>
        <?php ob_start(); $this->pageNav('上一页', '下一页', 2, '…'); $pagination = trim(ob_get_clean()); ?>
        <?php if ($pagination !== ''): ?><nav class="pagination-wrap" aria-label="文章分页"><?php echo $pagination; ?></nav><?php endif; ?>
    </section>
</main>
<?php $this->need('footer.php'); ?>
