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
    <article class="article" itemscope itemtype="https://schema.org/BlogPosting">
        <header class="article-header">
            <div class="category-list"><span class="badge"><?php $this->category('</span><span class="badge">', true, '随笔'); ?></span></div>
            <h1 itemprop="headline"><?php $this->title(); ?></h1>
            <div class="article-meta">
                <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y年m月d日'); ?></time>
            </div>
        </header>
        <div class="article-divider"></div>
        <div class="article-content markdown-body" itemprop="articleBody"><?php $this->content(); ?></div>
        <footer class="article-footer">
            <div class="article-tags"><strong>标签</strong><span class="badge badge-muted"><?php $this->tags('</span><span class="badge badge-muted">', true, '暂无标签'); ?></span></div>
        </footer>
    </article>

    <nav class="post-near" aria-label="相邻文章">
        <div><span>上一篇</span><?php $this->thePrev('%s', '<em>没有更早的文章</em>'); ?></div>
        <div class="post-near-next"><span>下一篇</span><?php $this->theNext('%s', '<em>已经是最新文章</em>'); ?></div>
    </nav>

    <?php $this->need('comments.php'); ?>
</main>
<div id="sujian-back-to-top"></div>
<?php $this->need('footer.php'); ?>
