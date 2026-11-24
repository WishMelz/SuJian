<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!sujianCommentsEnabled($this->options) || !$this->allow('comment')) return;
?>
<section id="comments" class="comments" aria-label="评论">
    <?php $this->comments()->to($comments); ?>
    <?php if ($comments->have()): ?>
    <div class="comments-list">
        <h2 class="comments-title"><?php $this->commentsNum('暂无评论', '1 条评论', '%d 条评论'); ?></h2>
        <?php $comments->listComments(); ?>
        <?php ob_start(); $comments->pageNav('上一页', '下一页'); $commentPagination = trim(ob_get_clean()); ?>
        <?php if ($commentPagination !== ''): ?><nav class="pagination-wrap" aria-label="评论分页"><?php echo $commentPagination; ?></nav><?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="<?php $this->respondId(); ?>" class="respond">
        <div class="cancel-comment-reply"><?php $comments->cancelReply(); ?></div>
        <h2 id="response" class="respond-title">发表评论</h2>
        <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form" class="comment-form"<?php if ($this->options->commentsAntiSpam): ?> data-comment-token="<?php echo htmlspecialchars($this->security->getToken($this->request->getRequestUrl()), ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
            <?php if ($this->user->hasLogin()): ?>
            <p class="comment-login">以 <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> 登录 · <a href="<?php $this->options->logoutUrl(); ?>">退出</a></p>
            <?php else: ?>
            <div class="field-grid">
                <div class="field"><label for="author">称呼 <span class="required-mark" aria-hidden="true">*</span></label><input type="text" name="author" id="author" autocomplete="name" value="<?php $this->remember('author'); ?>" required></div>
                <div class="field"><label for="mail">邮箱<?php if ($this->options->commentsRequireMail): ?> <span class="required-mark" aria-hidden="true">*</span><?php endif; ?></label><input type="email" name="mail" id="mail" autocomplete="email" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></div>
                <div class="field"><label for="url">网站<?php if ($this->options->commentsRequireUrl): ?> <span class="required-mark" aria-hidden="true">*</span><?php endif; ?></label><input type="url" name="url" id="url" autocomplete="url" placeholder="https://" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireUrl): ?> required<?php endif; ?>></div>
            </div>
            <?php endif; ?>
            <div class="field"><label for="textarea">评论内容 <span class="required-mark" aria-hidden="true">*</span></label><textarea rows="7" name="text" id="textarea" placeholder="写下你的想法…" required></textarea></div>
            <button class="button button-primary" type="submit">提交评论</button>
        </form>
    </div>
</section>
