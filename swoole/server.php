<?php
//require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ExceptionHeader.php';
require_once __DIR__ . '/ExceptionExit.php';
require_once __DIR__ . '/SwooleHeader.php';
require_once __DIR__ . '/SwooleRequest.php';

// enable all hooks https://www.swoole.co.uk/docs/modules/swoole-runtime-flags
Swoole\Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);

// https://www.swoole.co.uk/docs/modules/swoole-server/configuration
//require_once __DIR__ . '/SessionMiddleware.php';

$http = new Swoole\HTTP\Server("0.0.0.0", 9501);

$http->set([
    'reactor_num' => 1,
    'worker_num' => 10,

    // Source File Reloading
    'reload_async' => true,
    'max_wait_time' => 5,

//    'log_level' => 5,


//    'stats_file' => '/var/www/stats_file.txt',
]);



$http->on('start', function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

function read_session($sessionId) {
    $file = "/var/www/html/sessions/$sessionId";
    if (file_exists($file)) {
        $data = file_get_contents($file);
        return (array) unserialize($data);
    }
    return [];
}

function write_session($sessionId, $data) {
    echo "Write session\n";
//    var_dump($data);

    $file = "/var/www/html/sessions/$sessionId";
    file_put_contents("/var/www/html/sessions/$sessionId", serialize($data));
}


$http->on('request', function ($request, $swooleResponse) {
    require __DIR__ . '/reset_global.php';
//    unsetGlobal();
    setGlobal($request);
    echo "PLN\n";
    echo "Request time : " . time();

    $rootPath = '/var/www/html/moodle-swoole/';
    $requestedFile = $request->server['request_uri'];
    $file = $rootPath . $requestedFile;
    $fileDir = dirname($file);

    $requestedFile = $request->server['request_uri'];
    if (strstr($requestedFile, 'favicon') !== false) {
        echo "Server.php -> NOT Loaded $requestedFile\n";
        return;
    }

    $rootPath = '/var/www/html/moodle-swoole';
    $requestedFile = $request->server['request_uri'];
    SwooleRequest::setContent($request->getContent());

    // TODO no regex? or clean url?
    // /theme/styles.php/_s/boost/1618589517_1618589489/all
    preg_match('/(.*\.php)(.*)/', $requestedFile, $matches);
    if (count($matches) === 3) {
        if ($request->server['query_string']) {
            // TODO reset $_SERVER
            $_SERVER['QUERY_STRING'] = '?' . $request->server['query_string'];
        }
        list($requestedFile, $file, $parameter) = $matches;
        $_SERVER['PATH_INFO'] = $parameter;
        $requestedFile = $file;
    }

//    if (strpos($requestedFile, '/theme/styles.php') === 0) {
//        // /theme/styles.php/_s/boost/1618589517_1618589489/all
//        $_SERVER['PATH_INFO'] = substr($requestedFile, strlen('/theme/styles.php'));
//        $requestedFile = '/theme/styles.php';
//    }
//    if (strpos($requestedFile, '/lib/javascript.php') === 0) {
//        $response->end('');
//        return;
//        // lib/javascript.php/1618589517/lib/babel-polyfill/polyfill.min.js
//    }
//    if (strpos($requestedFile, '/theme/font.php') === 0) {
//        $response->end('');
//        return;
//        // theme/font.php/boost/core/1618589517/fontawesome-webfont.woff2
//    }
//    if (strpos($requestedFile, '/theme/yui_combo.php') === 0) {
//        if ($request->server['query_string']) {
//            // TODO reset $_SERVER
//            $_SERVER['QUERY_STRING'] = '?' . $request->server['query_string'];
//        }
//        $requestedFile = '/theme/yui_combo.php';
//    }
//    if (strpos($requestedFile, '/theme/image.php') === 0) {
//        // /theme/image.php/_s/boost/block_timeline/1618589517/activities
//        $response->end('');
//        return;
//    }


    $sessionId = null;
    $sessionName = session_name();
    $session = [];
    if (isset($request->cookie[$sessionName])) {
        $sessionId = $request->cookie[$sessionName];
        $session = read_session($sessionId);
    } else {
        // TODO collision to be solved
        $sessionId = session_create_id();
        write_session($sessionId, $session);
    }

    global $_SESSIONPLN, $SESSION;
    $_SESSIONPLN['SESSION'] = (object) $session['SESSION'];

    // Initialize user from session, like done in session/manager::start_session
    $GLOBALS['USER'] = $_SESSIONPLN['USER'];
    // previously
//     $_SESSIONPLN['USER'] =& $GLOBALS['USER'];
    $USER = $session['USER'] ?? (object) ['id' => 0];
    $GLOBALS['SESSION'] = $_SESSIONPLN['SESSION'];
    $_SESSIONPLN['SESSION'] =& $GLOBALS['SESSION'];

//    if (isset($_SESSIONPLN[]))
    if (isset($_SESSIONPLN['USER']->sesskey)){
        echo 'PLN SESSIONPLN sesskey : ' . $_SESSIONPLN['USER']->sesskey . "\n";
    } else {
        echo 'PLN SESSIONPLN no sesskey : '. "\n";
    }

    $len = strlen($requestedFile);
    if ($len > 0 && $requestedFile[$len-1] === '/') {
        if (file_exists($rootPath . $requestedFile . '/index.php')) {
            $requestedFile = $requestedFile . '/index.php';
        }
    }
//    if ($requestedFile === '/') {
//        $requestedFile = '/index.php';
//    }


    $file = $rootPath . $requestedFile;
    $fileDir = dirname($file);

    //    echo "PLN requested : $requestedFile\n";
//    echo "PLN file : $file\n";

//    if (strstr($file, 'index.php') === false) {
//        // TODO
//        echo "Server.php -> NOT Load $file\n";
//        return;
//    }

//    echo "Server.php -> Load $file\n";

//    var_dump($request->server);

    unset($_GET);
    unset($_POST);

    if ($request->server['request_method'] === 'POST') {
        foreach($request->post as $key => $value) {
            $_POST[$key] = $value;
        }
//        swoole_1  |   ["anchor"]=>
//        swoole_1  |   string(0) ""
//        swoole_1  |   ["logintoken"]=>
//        swoole_1  |   string(32) "rP5ct0oEsz1UIbPYw4ztWT4pUjDkEKjf"
//        swoole_1  |   ["username"]=>
//        swoole_1  |   string(5) "admin"
//        swoole_1  |   ["password"]=>
//        swoole_1  |   string(10) "Wibble123!"

    }

    if ($request->server['request_method'] === 'GET' && isset($request->get)) {
        foreach($request->get as $key => $value) {
            $_GET[$key] = $value;
        }
//        swoole_1  |   ["anchor"]=>
//        swoole_1  |   string(0) ""
//        swoole_1  |   ["logintoken"]=>
//        swoole_1  |   string(32) "rP5ct0oEsz1UIbPYw4ztWT4pUjDkEKjf"
//        swoole_1  |   ["username"]=>
//        swoole_1  |   string(5) "admin"
//        swoole_1  |   ["password"]=>
//        swoole_1  |   string(10) "Wibble123!"

    }

    if ($request->server['query_string']) {
        parse_str($request->server['query_string'], $output);
        foreach ($output as $key => $value) {
            $_GET[$key] = $value;
        }
    }

    require $rootPath . '/config.swoole.php';
    require $rootPath . '/lib/setup.php';
    // TODO not sure why this is not called authomatically when loading /theme/style.php page
    require_once $rootPath . '/lib/configonlylib.php';


    // TODO find a better way?
    chdir($fileDir);

    echo "\n";
    echo " LOAD ----> $file - process " . getmypid() . "\n";
    echo "\n";

    $header = false;
    $capture = true;
    try {
        if ($capture) {
            ob_start();
        }
//        echo "<PRE>";
        require $file;
    } catch (ExceptionExit $e) {
        foreach(SwooleHeader::getHeaders() as $header) {
            $swooleResponse->header($header->key, $header->value);
        }
        if ($e->getCode()) {
            $swooleResponse->status($e->getCode());
        }
        $header = true;
        SwooleHeader::reset();
    }

    if ($capture) {
        $page = ob_get_clean();
    } else {
        $page = 'No capture';
    }


    echo "Get cookie params\n";
    $cookie = session_get_cookie_params();
    $cookie = [
        'lifetime' => null,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => 0,
    ];

    $swooleResponse->rawcookie(
        $sessionName,
        $sessionId,
        $cookie['lifetime'] ? time() + $cookie['lifetime'] : null,
        $cookie['path'],
        $cookie['domain'],
        $cookie['secure'],
        $cookie['httponly']
    );


    $header === false && $swooleResponse->header("Content-Type", "text/html");

    // TODO : write only if needed
    echo " ------------------- process " . getmypid() . "\n";

    // Just to be sure
    if (isset($_SESSIONPLN['USER']->sesskey)){
        echo 'PLN SESSIONPLN sesskey : ' . $_SESSIONPLN['USER']->sesskey . "\n";
    } else {
        echo 'PLN SESSIONPLN no sesskey : '. "\n";
    }

    $_SESSIONPLN['USER'] = $USER;
    write_session($sessionId, $_SESSIONPLN);

    SwooleRequest::setContent('');
    renderer_base::reset_template_cache();
//    filter_manager::$singletoninstance = null;
    // TODO Can we avoid to do that ?
//    core_plugin_manager::$singletoninstance = null;

    //    echo "This is the end" . strlen($page);
    $swooleResponse->end($page);
//    $response->end($page);
});

$http->start();

function testDB() {

    $host = 'mysql';
    $db   = 'moodle';
    $user = 'root';
    $pass = 'Wibble123!';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    // Here mysqli
//    $mysqli = mysqli_init();
//    $conn = $mysqli->real_connect($host, $user, $pass, $db);
//    $result = $mysqli->query('SELECT * from mdl_user');
//    var_dump($result->fetch_all());
//    return;

    // Following PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
    $stmt = $pdo->query('SELECT * from mdl_user');
    while ($row = $stmt->fetch())
    {
        echo $row['username'] . "\n";
    }
    $mysqli = new mysqli($host, $user, $pass, $db);
    if (mysqli_connect_errno()) {
        /* Of course, your error handling is nicer... */
        die(sprintf("[%d] %s\n", mysqli_connect_errno(), mysqli_connect_error()));
    }

    $sql = 'SELECT * from mdl_user';
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result(); // get the mysqli result
    $users = $result->fetch_assoc(); // fetch data
    var_dump($users);

    require_once __DIR__ . '/config.php';
}

function unsetGlobal() {
//    unset($CFG);
//    unset($COURSE);
//    unset($DB);
//    unset($FULLME);
//    unset($FULLSCRIPT);
//    unset($ME);
//    unset($OUTPUT);
//    unset($PAGE);
//    unset($SCRIPT);
//    unset($SESSION);
//    unset($SITE);
//    unset($USER);
//    unset($GLOBALS['CFG']);
//    unset($GLOBALS['COURSE']);
//    unset($GLOBALS['DB']);
//    unset($GLOBALS['FULLME']);
//    unset($GLOBALS['FULLSCRIPT']);
//    unset($GLOBALS['ME']);
//    unset($GLOBALS['OUTPUT']);
//    unset($GLOBALS['PAGE']);
//    unset($GLOBALS['SCRIPT']);
//    unset($GLOBALS['SESSION']);
//    unset($GLOBALS['SITE']);
//    unset($GLOBALS['USER']);
//    global $USER;

    // TODO define or on every define from the list :
    // NO_DEBUG_DISPLAY
    // ABORT_AFTER_CONFIG
    //
    //
    //
}

function setGlobal($request) {
    $_SERVER['SERVER_SOFTWARE'] = '';
    $_SERVER['HTTP_USER_AGENT'] = $request->header['user-agent'];
}
