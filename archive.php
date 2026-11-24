<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$this->need('header.php');
$archiveKicker = '归档';
if ($this->is('search')) {
    $archiveKicker = '搜索结果';
} elseif ($this->is('category')) {
    $archiveKicker = '分类';
} elseif ($this->is('tag')) {
    $archiveKicker = '标签';
} elseif ($this->is('author')) {
    $archiveKicker = '作者';
}
$archiveTotal = method_exists($this, 'getTotal') ? (int) $this->getTotal() : 0;
$archiveSummary = '';
if ($this->is('search')) {
    $archiveSummary = $archiveTotal > 0 ? '共找到 ' . $archiveTotal . ' 篇相关文章' : '';
} else {
    $archiveDescription = '';
    if ($this->is('category') || $this->is('tag')) {
        $archiveDescription = sujianPlainText(method_exists($this, 'getArchiveDescription') ? $this->getArchiveDescription() : $this->getDescription());
        // 分类描述常被填成与名称相同的文字，这种情况下改为显示文章数
        $archiveName = method_exists($this, 'getArchiveTitle') ? sujianPlainText((string) $this->getArchiveTitle()) : '';
        if ($archiveDescription !== '' && strcasecmp($archiveDescription, $archiveName) === 0) {
            $archiveDescription = '';
        }
    }
    $archiveSummary = $archiveDescription !== '' ? $archiveDescription : ($archiveTotal > 0 ? '共 ' . $archiveTotal . ' 篇文章' : '');
}
?>
<main class="container archive-layout<?php if ($this->is('search')): ?> archive-layout-search<?php endif; ?>" id="main" tabindex="-1" data-list-page>
    <header class="archive-header">
        <span class="archive-kicker"><?php echo htmlspecialchars($archiveKicker, ENT_QUOTES, 'UTF-8'); ?></span>
        <h1><?php $this->archiveTitle(array(
            'category' => _t('%s'),
            'search' => _t('“%s”'),
            'tag' => _t('%s'),
            'author' => _t('%s'),
            'date' => _t('%s')
        ), '', ''); ?></h1>
        <?php if ($archiveSummary !== ''): ?><p class="archive-summary"><?php echo htmlspecialchars($archiveSummary, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <?php if ($this->is('search')): ?>
        <form class="archive-search" method="get" action="<?php $this->options->siteUrl(); ?>" role="search">
            <label class="sr-only" for="archive-search">重新搜索</label>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
            <input id="archive-search" type="search" name="s" placeholder="换个关键词…" value="<?php $this->keywords(); ?>">
            <button class="button button-primary" type="submit">搜索</button>
        </form>
        <?php endif; ?>
    </header>

    <?php if ($this->have()): ?>
    <div class="post-list">
        <?php while ($this->next()): ?>
        <?php $this->need('partials/post-item.php'); ?>
        <?php endwhile; ?>
    </div>
    <?php ob_start(); $this->pageNav('上一页', '下一页', 2, '…'); $pagination = trim(ob_get_clean()); ?>
    <?php if ($pagination !== ''): ?><nav class="pagination-wrap" aria-label="文章分页"><?php echo $pagination; ?></nav><?php endif; ?>
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
