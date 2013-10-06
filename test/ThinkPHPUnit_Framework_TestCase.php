<?php

require_once __DIR__.'/../Vendor/autoload.php';

class mockBrowser {
    private static $__request_uri;

    public static $_SERVER = array (
        'USER' => 'nobody',
        'HOME' => '/',
        'FCGI_ROLE' => 'RESPONDER',
        'SCRIPT_FILENAME' => '',
        'QUERY_STRING' => '',
        'REQUEST_METHOD' => 'GET',
        'CONTENT_TYPE' => '',
        'CONTENT_LENGTH' => '',
        'SCRIPT_NAME' => '',
        'REQUEST_URI' => '',
        'DOCUMENT_URI' => '',
        'DOCUMENT_ROOT' => '/usr/local/nginx-1.2.6/html',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'GATEWAY_INTERFACE' => 'CGI/1.1',
        'SERVER_SOFTWARE' => 'nginx/1.2.6',
        'REMOTE_ADDR' => '',
        'REMOTE_PORT' => '',
        'SERVER_ADDR' => '127.0.0.1',
        'SERVER_PORT' => '80',
        'SERVER_NAME' => '',
        'REDIRECT_STATUS' => '200',
        'HTTP_HOST' => 'tp.91.com',
        'HTTP_CONNECTION' => 'keep-alive',
        'HTTP_CACHE_CONTROL' => 'no-cache',
        'HTTP_PRAGMA' => 'no-cache',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.76 Safari/537.36',
        'HTTP_DNT' => '1',
        'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
        'HTTP_ACCEPT_LANGUAGE' => 'zh-CN,zh;q=0.8',
        'HTTP_COOKIE' => 'PHPSESSID=ks2ksv8s8k0fspljbpoqv9umu1',
        'PHP_SELF' => '',
        'REQUEST_TIME' => 0,
        'PATH_INFO' => '',
    );

    public static $_GET     = array();
    public static $_POST    = array();
    public static $_REQUEST = array();
    public static $_COOKIE  = array();
    public static $_SESSION = array();

    public static function setParamServer($name, $value) {
        self::$_SERVER[$name] = $value;
    }

    public static function setParamGet($name, $value) {
        self::$_GET[$name] = $value;
        self::setParamRequest($name, $value);
    }

    public static function setParamPost($name, $value) {
        self::$_POST[$name] = $value;
        self::setParamRequest($name, $value);
    }

    public static function setParamRequest($name, $value) {
        self::$_REQUEST[$name] = $value;
    }

    public static function setParamCookie($name, $value) {
        self::$_COOKIE[$name] = $value;
    }

    public static function setParamSession($name, $value) {
        self::$_SESSION[$name] = $value;
    }

    private static function __changeAtEverytime() {
        self::setParamServer('REQUEST_TIME', time());
        self::setParamServer('REMOTE_PORT', rand(10000, 65534));
        self::setParamServer('SERVER_NAME', MyThinkPHP_SERVER_NAME);

        self::setIndexFile();

        if (self::$_GET) {
            $path_info = '';
            foreach (self::$_GET as $k => $v) {
                $path_info .= sprintf('/%s/%s', $k, $v);
            }
            self::setParamServer('QUERY_STRING', 's='.$path_info);
            self::setParamServer('REQUEST_URI', $path_info);
            self::setParamServer('PATH_INFO', $path_info);
        }
    }

    public static function setPathInfo($path_info) {
        self::setParamServer('QUERY_STRING', 's='.$path_info);
        self::setParamServer('REQUEST_URI', $path_info);
        self::setParamServer('PATH_INFO', $path_info);
    }

    public static function setIndexFile() {
        self::setParamServer('SCRIPT_FILENAME', MyThinkPHP_INDEX_FILE);
        self::setParamServer('SCRIPT_NAME', '/'.basename(MyThinkPHP_INDEX_FILE));
        self::setParamServer('DOCUMENT_URI', SCRIPT_NAME);
        self::setParamServer('PHP_SELF', SCRIPT_NAME);
    }

    public static function setMyIp($ip) {
        self::setParamServer('REMOTE_ADDR', $ip);

        if (!self::$_SERVER['REQUEST_URI']) {
            self::setIndexFile();
        }
    }

    public static function getLastUri() {
        return self::$__request_uri;
    }

    public static function request($uri) {
        self::$__request_uri = $uri;

        self::__changeAtEverytime();
        self::setPathinfo($uri);

        return array(
            '_SERVER'  => self::$_SERVER,
            '_GET'     => self::$_GET,
            '_POST'    => self::$_POST,
            '_REQUEST' => self::$_REQUEST,
            '_COOKIE'  => self::$_COOKIE,
            '_SESSION' => self::$_SESSION,
        );
    }
}

class ThinkPHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase {
    public static $mockView;

    public static function setUpBeforeClass() {
        self::__startThinkPHP();
        self::__cleanup_runtime_files();
    }

    public function setUp() {
        parent::setUp();
        self::__cleanup_runtime_files();
    }

    public function tearDown() {
        parent::tearDown();
        self::__cleanup_runtime_files();
    }

    public static function tearDownAfterClass() {
        self::__cleanup_runtime_files();
    }

    public static function receive($request) {
        global $_SERVER, $_GET, $_POST, $_REQUEST, $_COOKIE, $_SESSION;

        $_SERVER  = $request['_SERVER'];
        $_GET     = $request['_GET'];
        $_POST    = $request['_POST'];
        $_REQUEST = $request['_REQUEST'];
        $_COOKIE  = $request['_COOKIE'];
        $_SESSION = $request['_SESSION'];

        Dispatcher::dispatch();

        call_user_method($_GET['_URL_'][1], A($_GET['_URL_'][0]));
    }

    public function initViewInThinkPHP() {
        require_once sprintf('%s/Lib/Core/View.class.php', THINK_PATH);

        $mock_functions = array(
            'display',
            'render',
            'fetch',
            'parseTemplate',
            'theme',
            'getTemplateTheme'
        );

        self::$mockView = $this->getMock('View', $mock_functions);

        return self::$mockView;
    }

    private static function __startThinkPHP() {
        require MyThinkPHP_INDEX_FILE;
    }

    private static function __cleanup_runtime_files() {
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(RUNTIME_PATH, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if (is_dir($path)) continue;
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, array('php', 'tpl', 'html'))) {
                $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            }
        }
    }
}

?>
