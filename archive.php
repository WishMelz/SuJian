<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="container archive-layout<?php if ($this->is('search')): ?> archive-layout-search<?php endif; ?>" id="main">
    <header class="archive-header">
        <span class="section-kicker"><?php echo $this->is('search') ? 'Search results' : 'Archive'; ?></span>
        <h1><?php $this->archiveTitle(array(
            'category' => _t('分类：%s'),
            'search' => _t('搜索“%s”'),
            'tag' => _t('标签：%s'),
            'author' => _t('%s 的文章')
        ), '', ''); ?></h1>
        <p><?php echo $this->is('search') ? '可查看结果，或尝试其他关键词。' : '按主题整理的文章、代码片段与实践记录。'; ?></p>
        <?php if ($this->is('search')): ?>
        <form class="archive-search" method="get" action="<?php $this->options->siteUrl(); ?>" role="search">
            <label class="sr-only" for="archive-search">重新搜索</label>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
            <input id="archive-search" type="search" name="s" placeholder="重新搜索…" value="<?php $this->keywords(); ?>">
            <button class="button button-primary" type="submit">搜索</button>
        </form>
        <?php endif; ?>
    </header>

    <?php if ($this->have()): ?>
    <div class="archive-list">
        <?php while ($this->next()): ?>
        <?php $excerpt = sujianExcerpt($this); ?>
        <article class="post-card archive-card" itemscope itemtype="https://schema.org/BlogPosting">
            <div class="post-card-topline">
                <div class="category-list"><span class="badge"><?php $this->category('</span><span class="badge">', true, '随笔'); ?></span></div>
                <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y-m-d'); ?></time>
            </div>
            <h2 itemprop="headline"><a itemprop="url" href="<?php $this->permalink(); ?>"<?php echo sujianArticleLinkAttributes($this->options); ?>><?php $this->title(); ?></a></h2>
            <?php if ($excerpt !== ''): ?><div class="post-excerpt" itemprop="description"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <div class="post-card-footer">
                <?php if (sujianCommentsEnabled($this->options) && $this->allow('comment')): ?>
                <span><?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?></span>
                <?php endif; ?>
                <a class="read-more" href="<?php $this->permalink(); ?>"<?php echo sujianArticleLinkAttributes($this->options); ?>>阅读全文<span class="sr-only">：<?php echo htmlspecialchars(sujianContentTitle($this, $this->options, '文章'), ENT_QUOTES, 'UTF-8'); ?></span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
            </div>
        </article>
        <?php endwhile; ?>
    </div>
    <nav class="pagination-wrap" aria-label="文章分页"><?php $this->pageNav('上一页', '下一页', 2, '…'); ?></nav>
    <?php else: ?>
    <section class="empty-state">
        <span class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg></span>
        <h2>没有找到相关内容</h2>
        <p>换一个关键词，或返回首页继续浏览最新文章。</p>
        <a class="button button-primary" href="<?php $this->options->siteUrl(); ?>">返回首页</a>
    </section>
    <?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>
