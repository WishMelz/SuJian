<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$friends = sujianFriends($this->options);
$this->need('header.php');
?>
<main class="container single-layout friends-layout" id="main">
    <header class="friends-header">
        <span class="section-kicker">Friends</span>
        <h1><?php echo htmlspecialchars(sujianContentTitle($this, $this->options, '友情链接'), ENT_QUOTES, 'UTF-8'); ?></h1>
    </header>
    <?php if (trim((string) $this->content) !== ''): ?>
        <div class="article-content markdown-body friends-intro"><?php $this->content(); ?></div>
    <?php endif; ?>
    <?php if ($friends): ?>
        <ul class="friends-list" aria-label="友情链接">
            <?php foreach ($friends as $friend): ?>
                <li>
                    <a class="friend-card" href="<?php echo htmlspecialchars($friend['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="external noopener noreferrer">
                        <img class="friend-avatar" src="<?php echo htmlspecialchars($friend['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="48" height="48" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                        <div class="friend-info">
                            <h2 class="friend-name"><?php echo htmlspecialchars($friend['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <?php if ($friend['description'] !== ''): ?>
                                <p class="friend-description"><?php echo htmlspecialchars($friend['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="friends-empty">暂无友情链接。</p>
    <?php endif; ?>
    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>