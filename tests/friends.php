<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 4));
spl_autoload_register(function ($class) {
    $file = __TYPECHO_ROOT_DIR__ . '/var/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
require dirname(__DIR__) . '/functions.php';

function checkFriendsSame($expected, $actual, $label)
{
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': ' . var_export($actual, true));
    }
    echo 'PASS ', $label, PHP_EOL;
}

class FriendsTestOptions extends \Widget\Options
{
    protected function initParameter(\Typecho\Config $parameter)
    {
        $this->row = $parameter->toArray();
    }

    public function execute()
    {
    }
}

\Typecho\Widget::alias('Widget\\Options', FriendsTestOptions::class);
$options = \Widget\Options::alloc(array(
    '#siteUrl' => 'https://example.test:8443/blog/',
    '#themeUrl' => 'https://example.test:8443/blog/usr/themes/Sujian/',
    'friendsData' => 'Example|/avatar.png|https://friend.test||A personal blog'
));
checkFriendsSame(false, sujianFriendsEnabled($options), 'disabled by default');
checkFriendsSame(array(), sujianFriends($options), 'disabled hides stored friends');
$options->friendsEnabled = '1';
checkFriendsSame(true, sujianFriendsEnabled($options), 'enabled explicitly');
$friends = sujianFriends($options);
checkFriendsSame(array(array(
    'name' => 'Example',
    'avatar' => 'https://example.test:8443/avatar.png',
    'address' => 'https://friend.test',
    'url' => 'https://friend.test',
    'description' => 'A personal blog'
)), $friends, 'parse all fields and default destination');

$options->friendsData = 'New format|||https://new-format.test|New description | details';
$friends = sujianFriends($options);
checkFriendsSame('https://new-format.test', $friends[0]['url'], 'parse four-field format with empty legacy address');
checkFriendsSame('https://new-format.test', $friends[0]['address'], 'use destination as legacy display address');
checkFriendsSame('New description | details', $friends[0]['description'], 'preserve four-field description and pipes');
$options->friendsData = 'Legacy||javascript:alert(1)|https://destination.test|Description';
checkFriendsSame('https://destination.test', sujianFriends($options)[0]['url'], 'obsolete address does not invalidate explicit destination');

$options->friendsData = " First | | https://first.test | https://redirect.test/go?site=first&from=blog | One | Two \r\n\r\nSecond|avatars/second.png|/second/||\rThird|//cdn.test/third.png|http://third.test||Three\n";
$friends = sujianFriends($options);
checkFriendsSame(3, count($friends), 'support line endings and ignore blank lines');
checkFriendsSame('First', $friends[0]['name'], 'trim values and preserve order');
checkFriendsSame('https://example.test:8443/blog/usr/themes/Sujian/favicon.svg', $friends[0]['avatar'], 'empty avatar uses theme default');
checkFriendsSame('https://first.test', $friends[0]['address'], 'display address remains independent');
checkFriendsSame('https://redirect.test/go?site=first&from=blog', $friends[0]['url'], 'custom click destination');
checkFriendsSame('One | Two', $friends[0]['description'], 'description may contain pipes');
checkFriendsSame('https://example.test:8443/blog/avatars/second.png', $friends[1]['avatar'], 'relative avatar preserves site subdirectory');
checkFriendsSame('https://example.test:8443/second/', $friends[1]['url'], 'relative address preserves origin and port');
checkFriendsSame('', $friends[1]['description'], 'empty description');
checkFriendsSame('https://cdn.test/third.png', $friends[2]['avatar'], 'protocol relative avatar');
checkFriendsSame('http://third.test', $friends[2]['url'], 'HTTP address');

foreach (array('javascript:alert(1)', 'data:text/html,test', 'ftp://friend.test', 'https:broken') as $unsafeUrl) {
    $options->friendsData = 'Unsafe||' . $unsafeUrl . '||Test';
    checkFriendsSame(array(), sujianFriends($options), 'reject unsafe address ' . $unsafeUrl);
    $options->friendsData = 'Unsafe||https://friend.test|' . $unsafeUrl . '|Test';
    checkFriendsSame(array(), sujianFriends($options), 'reject unsafe destination ' . $unsafeUrl);
    $options->friendsData = 'Safe|' . $unsafeUrl . '|https://friend.test||Test';
    checkFriendsSame('https://example.test:8443/blog/usr/themes/Sujian/favicon.svg', sujianFriends($options)[0]['avatar'], 'unsafe avatar falls back ' . $unsafeUrl);
}

$options->friendsData = "Missing columns|https://friend.test\n||https://friend.test||No name\nNo address||||No address\nValid||https://friend.test||";
checkFriendsSame(1, count(sujianFriends($options)), 'skip malformed rows and missing required fields');
$options->friendsData = '';
checkFriendsSame(array(), sujianFriends($options), 'enabled with no data');
$options->friendsData = 'Example||https://friend.test||Stored';
$options->friendsEnabled = '0';
checkFriendsSame(array(), sujianFriends($options), 'switch off hides friends');
checkFriendsSame('Example||https://friend.test||Stored', $options->friendsData, 'switch off preserves configuration');

class FriendsTestArchive extends \Widget\Base
{
    public $template = 'page-friends.php';
    public $type = 'page';
    public $title = 'Friends';
    public $content = '<p>Page introduction</p>';
    public $includes = array();

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function is($type)
    {
        return $this->type === $type;
    }

    public function need($file)
    {
        $this->includes[] = $file;
    }

    public function content()
    {
        echo $this->content;
    }

    public function render()
    {
        ob_start();
        try {
            themeInit($this);
            require dirname(__DIR__) . '/page-friends.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}

$archive = new FriendsTestArchive($options);
checkFriendsSame(true, (new ReflectionProperty(\Widget\Base::class, 'options'))->isProtected(), 'archive options use native protected visibility');
checkFriendsSame(null, $archive->options, 'external archive options access cannot read site settings');
checkFriendsSame($options, \Widget\Options::alloc(), 'public options accessor resolves shared site settings');
$status = 200;
try {
    $archive->render();
} catch (\Typecho\Widget\Exception $exception) {
    $status = $exception->getCode();
}
checkFriendsSame(404, $status, 'disabled friends page returns 404');
checkFriendsSame(array(), $archive->includes, 'disabled page stops before header output');
$archive->template = 'page.php';
checkFriendsSame(null, themeInit($archive), 'other page templates are unaffected');
$archive->template = 'page-friends.php';
$archive->type = 'post';
checkFriendsSame(null, themeInit($archive), 'other archive types are unaffected');
$archive->type = 'page';
$options->friendsEnabled = '1';
checkFriendsSame(null, themeInit($archive), 'enabled friends page is accessible with protected archive options');
$options->friendsData = 'Name <script>alert(1)</script>|https://cdn.test/avatar.png?one=1&two=2|https://friend.test|https://redirect.test/?one=1&two=2|<img src=x onerror=alert(1)> & details';
$archive->title = 'Friends <script>alert(2)</script>';
$html = $archive->render();
checkFriendsSame(array('header.php', 'comments.php', 'footer.php'), $archive->includes, 'uses shared theme templates');
checkFriendsSame(true, strpos($html, '<script>') === false, 'escape page title and friend name');
checkFriendsSame(true, strpos($html, '&lt;img src=x onerror=alert(1)&gt; &amp; details') !== false, 'escape friend description');
checkFriendsSame(true, strpos($html, 'src="https://cdn.test/avatar.png?one=1&amp;two=2"') !== false, 'escape avatar attribute');
checkFriendsSame(true, strpos($html, 'href="https://redirect.test/?one=1&amp;two=2"') !== false, 'escape destination attribute');
checkFriendsSame(true, strpos($html, 'friend-address') === false, 'do not render legacy display address');
checkFriendsSame(true, strpos($html, 'rel="external noopener noreferrer"') !== false, 'protect new tab destination');
checkFriendsSame(true, strpos($html, '<p>Page introduction</p>') !== false, 'preserve authored page content');
$options->friendsData = '';
$archive->content = '';
$html = $archive->render();
checkFriendsSame(true, strpos($html, 'friends-empty') !== false, 'render empty state');
checkFriendsSame(true, strpos($html, 'friends-list') === false, 'no empty list container');
checkFriendsSame(true, strpos($html, 'friends-intro') === false, 'no empty introduction container');
