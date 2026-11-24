<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="container error-layout" id="main" tabindex="-1">
    <section class="empty-state error-state">
        <span class="error-code">404</span>
        <h1>页面似乎走远了</h1>
        <p>链接可能已失效，也可能只是暂时找不到。可以搜索站内内容，或回到首页。</p>
        <form class="archive-search" method="get" action="<?php $this->options->siteUrl(); ?>" role="search">
            <label class="sr-only" for="not-found-search">搜索文章</label>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.4-3.4"></path></svg>
            <input id="not-found-search" type="search" name="s" placeholder="搜索文章…">
            <button class="button button-primary" type="submit">搜索</button>
        </form>
        <a class="text-link" href="<?php $this->options->siteUrl(); ?>">← 返回首页</a>
    </section>
</main>
<?php $this->need('footer.php'); ?>
