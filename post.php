<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="container single-layout" id="main" tabindex="-1">
    <?php if (sujianArticleLinkTarget($this->options) === 'self'): ?>
    <nav class="article-back" aria-label="返回导航">
        <a class="article-back-button" href="<?php $this->options->siteUrl(); ?>" data-article-back aria-label="返回文章列表">
            <span class="article-back-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
            </span>
            返回文章列表
        </a>
    </nav>
    <?php endif; ?>
    <article class="article">
        <header class="article-header">
            <h1><?php $this->title(); ?></h1>
            <div class="article-meta">
                <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y年n月j日'); ?></time>
                <span class="post-meta-sep" aria-hidden="true">·</span>
                <span class="article-cats"><?php $this->category('、', true, '随笔'); ?></span>
            </div>
        </header>
        <div class="article-content markdown-body"><?php $this->content(); ?></div>
        <?php ob_start(); $this->tags('', true, ''); $articleTagsHtml = trim(ob_get_clean()); ?>
        <?php if ($articleTagsHtml !== ''): ?>
        <footer class="article-footer">
            <div class="article-tags" aria-label="标签"><?php echo $articleTagsHtml; ?></div>
        </footer>
        <?php endif; ?>
    </article>

    <nav class="post-near" aria-label="相邻文章">
        <div><span>上一篇</span><?php $this->thePrev('%s', '<em>没有更早的文章</em>'); ?></div>
        <div class="post-near-next"><span>下一篇</span><?php $this->theNext('%s', '<em>已经是最新文章</em>'); ?></div>
    </nav>

    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
