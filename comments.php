<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!sujianCommentsEnabled($this->options) || !$this->allow('comment')) return;
?>
<section id="comments" class="comments" aria-label="评论">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
    <div class="comments-list">
        <div class="section-heading compact"><div><span class="section-kicker">Discussion</span><h2><?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?></h2></div></div>
        <?php $comments->listComments(); ?>
        <nav class="pagination-wrap" aria-label="评论分页"><?php $comments->pageNav('上一页', '下一页'); ?></nav>
    </div>
    <?php endif; ?>

    <div id="<?php $this->respondId(); ?>" class="respond">
        <div class="cancel-comment-reply"><?php $comments->cancelReply(); ?></div>
        <span class="section-kicker">Leave a reply</span>
        <h2 id="response">发表评论</h2>
        <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form" class="comment-form" role="form"<?php if ($this->options->commentsAntiSpam): ?> data-comment-token="<?php echo htmlspecialchars($this->security->getToken($this->request->getRequestUrl()), ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
            <?php if ($this->user->hasLogin()): ?>
            <p class="comment-login">以 <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> 登录 · <a href="<?php $this->options->logoutUrl(); ?>">退出</a></p>
            <?php else: ?>
            <div class="field-grid">
                <div class="field"><label for="author">称呼</label><input type="text" name="author" id="author" value="<?php $this->remember('author'); ?>" required></div>
                <div class="field"><label for="mail">邮箱</label><input type="email" name="mail" id="mail" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></div>
                <div class="field"><label for="url">网站</label><input type="url" name="url" id="url" placeholder="https://" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?>></div>
            </div>
            <?php endif; ?>
            <div class="field"><label for="textarea">评论内容</label><textarea rows="7" name="text" id="textarea" placeholder="写下你的想法…" required></textarea></div>
            <button class="button button-primary" type="submit">提交评论</button>
        </form>
    </div>
</section>
