<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 4));
require dirname(__DIR__) . '/functions.php';

function checkCommentsSame($expected, $actual, $label)
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    echo 'PASS ', $label, PHP_EOL;
}

class CommentsTestList
{
    public $hasComments = true;

    public function to(&$comments) { $comments = $this; }
    public function have() { return $this->hasComments; }
    public function listComments() { echo '<ol class="comment-list"><li>Stored comment</li></ol>'; }
    public function pageNav(...$args) { echo '<a href="?commentPage=2">Next</a>'; }
    public function cancelReply() { echo '<a href="#response">Cancel reply</a>'; }
}

class CommentsTestArchive
{
    protected $options;
    public $user;
    public $allowed = true;
    public $commentQueries = 0;
    public $commentsList;

    public function __construct($options)
    {
        $this->options = $options;
        $this->user = new class {
            public function hasLogin() { return false; }
        };
        $this->commentsList = new CommentsTestList();
    }

    public function allow($permission) { return $permission === 'comment' && $this->allowed; }
    public function respondId() { echo 'respond-post-1'; }
    public function commentUrl() { echo '/action/feedback?type=comment'; }
    public function remember($name) { echo ''; }
    public function commentsNum(...$args) { echo '1 comment'; }

    public function comments()
    {
        $this->commentQueries++;
        return $this->commentsList;
    }

    public function render()
    {
        ob_start();
        try {
            require dirname(__DIR__) . '/comments.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}

$options = (object) array('commentsAntiSpam' => 0, 'commentsRequireMail' => 1, 'commentsRequireUrl' => 0);
$archive = new CommentsTestArchive($options);
checkCommentsSame(true, sujianCommentsEnabled($options), 'existing installations keep comments enabled');
$html = $archive->render();
checkCommentsSame(true, strpos($html, 'id="comments"') !== false, 'enabled comments section renders');
checkCommentsSame(true, strpos($html, 'Stored comment') !== false, 'enabled comments preserve existing discussion');
checkCommentsSame(true, strpos($html, 'id="comment-form"') !== false, 'enabled comments include submission form');
foreach (array(true, false) as $hasComments) {
    $archive->commentsList->hasComments = $hasComments;
    foreach (array(array('0', true), array('0', false), array('1', false)) as $state) {
        $options->commentsEnabled = $state[0];
        $archive->allowed = $state[1];
        $archive->commentQueries = 0;
        $label = 'theme=' . $state[0] . ', allowed=' . (int) $state[1] . ', history=' . (int) $hasComments;
        checkCommentsSame('', $archive->render(), 'closed comments emit no HTML: ' . $label);
        checkCommentsSame(0, $archive->commentQueries, 'closed comments skip comment query: ' . $label);
    }
}
$options->commentsEnabled = '1';
$archive->allowed = true;
$archive->commentsList->hasComments = true;
checkCommentsSame(true, strpos($archive->render(), 'Stored comment') !== false, 're-enabling restores existing discussion');