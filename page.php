<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="container single-layout" id="main">
    <article class="article page-article" itemscope itemtype="https://schema.org/Article">
        <header class="article-header">
            <span class="section-kicker">Page</span>
            <h1 itemprop="headline"><?php echo htmlspecialchars(sujianContentTitle($this, $this->options), ENT_QUOTES, 'UTF-8'); ?></h1>
        </header>
        <div class="article-divider"></div>
        <div class="article-content markdown-body" itemprop="articleBody"><?php $this->content(); ?></div>
    </article>
    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
