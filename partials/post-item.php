<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $excerpt = sujianExcerpt($this); ?>
<article class="post-item">
    <div class="post-meta">
        <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y年n月j日'); ?></time>
        <span class="post-meta-sep" aria-hidden="true">·</span>
        <span class="post-cats"><?php $this->category('、', true, '随笔'); ?></span>
        <?php if (sujianCommentsEnabled($this->options) && $this->allow('comment')): ?>
        <span class="post-meta-sep" aria-hidden="true">·</span>
        <span><?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?></span>
        <?php endif; ?>
    </div>
    <h2 class="post-title"><a href="<?php $this->permalink(); ?>"<?php echo sujianArticleLinkAttributes($this->options); ?>><?php $this->sticky(); ?><?php $this->title(); ?></a></h2>
    <?php if ($excerpt !== ''): ?><p class="post-excerpt"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
</article>
