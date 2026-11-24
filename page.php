<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="container single-layout" id="main" tabindex="-1">
    <article class="article page-article">
        <header class="article-header">
            <h1><?php echo htmlspecialchars(sujianContentTitle($this, $this->options), ENT_QUOTES, 'UTF-8'); ?></h1>
        </header>
        <div class="article-content markdown-body"><?php $this->content(); ?></div>
    </article>
    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
