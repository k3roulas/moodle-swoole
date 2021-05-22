<?php

/**
 * Send the JavaScript cached
 * @param string $content
 * @param string $mimetype
 * @param string $etag
 * @param int $lastmodified
 */
function combo_send_cached($content, $mimetype, $etag, $lastmodified) {
    $lifetime = 60*60*24*360; // 1 year, we do not change YUI versions often, there are a few custom yui modules

    SwooleHeader::addHeader('Content-Disposition: inline; filename="combo"');
    SwooleHeader::addHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');
    SwooleHeader::addHeader('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    SwooleHeader::addHeader('Pragma: ');
    SwooleHeader::addHeader('Cache-Control: public, max-age='.$lifetime.', immutable');
    SwooleHeader::addHeader('Accept-Ranges: none');
    SwooleHeader::addHeader('Content-Type: '.$mimetype);
    SwooleHeader::addHeader('Etag: "'.$etag.'"');
//    header('Content-Disposition: inline; filename="combo"');
//    header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');
//    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
//    header('Pragma: ');
//    header('Cache-Control: public, max-age='.$lifetime.', immutable');
//    header('Accept-Ranges: none');
//    header('Content-Type: '.$mimetype);
//    header('Etag: "'.$etag.'"');
//    if (!min_enable_zlib_compression()) {
//        header('Content-Length: '.strlen($content));
//    }

    echo $content;
    throw new ExceptionExit('from combo_send_cached');

    die;
}

/**
 * Send the JavaScript uncached
 * @param string $content
 * @param string $mimetype
 */
function combo_send_uncached($content, $mimetype) {
    SwooleHeader::addHeader('Content-Disposition: inline; filename="combo"');
    SwooleHeader::addHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    SwooleHeader::addHeader('Expires: '. gmdate('D, d M Y H:i:s', time() + 2) .' GMT');
    SwooleHeader::addHeader('Pragma: ');
    SwooleHeader::addHeader('Accept-Ranges: none');
    SwooleHeader::addHeader('Content-Type: '.$mimetype);
//    header('Content-Disposition: inline; filename="combo"');
//    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
//    header('Expires: '. gmdate('D, d M Y H:i:s', time() + 2) .' GMT');
//    header('Pragma: ');
//    header('Accept-Ranges: none');
//    header('Content-Type: '.$mimetype);
//    if (!min_enable_zlib_compression()) {
//        header('Content-Length: '.strlen($content));
//    }
    throw new ExceptionExit('from combo_send_uncached');

    echo $content;
    die;
}

function combo_not_found($message = '') {

    SwooleHeader::addHeader('HTTP/1.0 404 not found');
//    header('HTTP/1.0 404 not found');
    if ($message) {
        echo $message;
    } else {
        echo 'Combo resource not found, sorry.';
    }
    throw new ExceptionExit('from combo_not_found');
    die;
}

function combo_params() {
    if (isset($_SERVER['QUERY_STRING']) and strpos($_SERVER['QUERY_STRING'], 'file=/') === 0) {
        // url rewriting
        $slashargument = substr($_SERVER['QUERY_STRING'], 6);
        return array($slashargument, true);

    } else if (isset($_SERVER['REQUEST_URI']) and strpos($_SERVER['REQUEST_URI'], '?') !== false) {
        $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        return array($parts[1], false);

    } else if (isset($_SERVER['QUERY_STRING']) and strpos($_SERVER['QUERY_STRING'], '?') !== false) {
        // note: buggy or misconfigured IIS does return the query string in REQUEST_URI
        return array($_SERVER['QUERY_STRING'], false);

    } else if ($slashargument = min_get_slash_argument(false)) {
        $slashargument = ltrim($slashargument, '/');
        return array($slashargument, true);

    } else {
        // unsupported server, sorry!
        combo_not_found('Unsupported server - query string can not be determined, try disabling YUI combo loading in admin settings.');
    }
}
