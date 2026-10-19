<?php
/**
 * SuJian（素笺）：一款极尽简洁、专注内容的 Typecho 个人博客主题
 *
 * @package SuJian · 素笺
 * @author WishMelz
 * @version 1.0.1
 * @link https://github.com/WishMelz/SuJian
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
$bannerMode = sujianOption($this->options, 'bannerMode', 'gradient');
$bannerImage = sujianSafeUrl(sujianOption($this->options, 'bannerImage'));
$bannerGradient = sujianSafeGradient(sujianOption($this->options, 'bannerGradient'), sujianBannerGradient('gradient'));
$bannerTitle = sujianOption($this->options, 'bannerTitle', sujianSiteDisplayTitle($this->options));
$bannerDescription = sujianOption($this->options, 'bannerDescription', (string) $this->options->description);
if ($bannerMode === 'random') {
    $bannerGradient = sujianBannerGradient('random');
}
$bannerLoadingGradient = '135deg, #f59e0b 0%, #f97316 45%, #db2777 100%';
$bannerStyle = $bannerMode === 'image' && $bannerImage !== ''
    ? 'background-color:#c2410c;background-image:url(' . json_encode($bannerImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '),linear-gradient(' . $bannerLoadingGradient . ');'
    : 'background-image:linear-gradient(' . $bannerGradient . ');';
?>
<section class="home-banner" style="<?php echo htmlspecialchars($bannerStyle, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="home-banner-overlay" aria-hidden="true"></div>
    <div class="container home-banner-content">
        <span class="home-banner-kicker">素笺 · Personal Blog</span>
        <h1><?php echo htmlspecialchars($bannerTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($bannerDescription !== ''): ?><p><?php echo htmlspecialchars($bannerDescription, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    </div>
</section>
<main class="container index-layout" id="main">
    <section class="post-stream" id="latest-posts" aria-label="文章列表">
        <div class="post-list">
            <?php while ($this->next()): ?>
            <?php $excerpt = sujianExcerpt($this); ?>
            <article class="post-card" itemscope itemtype="https://schema.org/BlogPosting">
                <h2 itemprop="headline"><a itemprop="url" href="<?php $this->permalink(); ?>"<?php echo sujianArticleLinkAttributes($this->options); ?>><?php $this->sticky(); ?><?php $this->title(); ?></a></h2>
                <?php if ($excerpt !== ''): ?><div class="post-excerpt" itemprop="description"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <div class="post-card-footer">
                    <div class="post-card-meta">
                        <div class="category-list"><span class="badge"><?php $this->category('</span><span class="badge">', true, '随笔'); ?></span></div>
                        <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y-m-d'); ?></time>
                    </div>
                    <a class="read-more" href="<?php $this->permalink(); ?>"<?php echo sujianArticleLinkAttributes($this->options); ?>>阅读全文<span class="sr-only">：<?php echo htmlspecialchars(sujianContentTitle($this, $this->options, '文章'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                    </a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
        <nav class="pagination-wrap" aria-label="文章分页"><?php $this->pageNav('上一页', '下一页', 2, '…'); ?></nav>
    </section>
</main>
<?php $this->need('footer.php'); ?>
