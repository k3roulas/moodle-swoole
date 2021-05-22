<?php

// Utility functions.

function send_cached_font($fontpath, $etag, $font, $mimetype) {
    global $CFG;
    require_once("$CFG->dirroot/lib/xsendfilelib.php");

    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;

    SwooleHeader::addHeader('Etag: "'.$etag.'"');
    SwooleHeader::addHeader('Content-Disposition: inline; filename="'.$font.'"');
    SwooleHeader::addHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($fontpath)) .' GMT');
    SwooleHeader::addHeader('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    SwooleHeader::addHeader('Pragma: ');
    SwooleHeader::addHeader('Cache-Control: public, max-age='.$lifetime.', immutable');
    SwooleHeader::addHeader('Accept-Ranges: none');
    SwooleHeader::addHeader('Content-Type: '.$mimetype);
//    SwooleHeader::addHeader('Content-Length: '.filesize($fontpath));
//    header('Etag: "'.$etag.'"');
//    header('Content-Disposition: inline; filename="'.$font.'"');
//    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($fontpath)) .' GMT');
//    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
//    header('Pragma: ');
//    header('Cache-Control: public, max-age='.$lifetime.', immutable');
//    header('Accept-Ranges: none');
//    header('Content-Type: '.$mimetype);
//    header('Content-Length: '.filesize($fontpath));

//    if (xsendfile($fontpath)) { TODO PLN
//        die;
//    }

    // No need to gzip already compressed fonts.

    readfile($fontpath);
    throw new ExceptionExit('from send_cached_font');
    die;
}

function send_uncached_font($fontpath, $font, $mimetype) {
    header('Content-Disposition: inline; filename="'.$font.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + 15) .' GMT');
    header('Pragma: ');
    header('Accept-Ranges: none');
    header('Content-Type: '.$mimetype);
    header('Content-Length: '.filesize($fontpath));

    readfile($fontpath);
    die;
}

function font_not_found() {
    header('HTTP/1.0 404 not found');
    die('font was not found, sorry.');
}

/**
 * Caches a given font file.
 *
 * @param string $font The name of the font that was requested.
 * @param string $fontfile The location of the font file we want to cache.
 * @param string $candidatelocation The location to cache it in.
 * @return string The path to the cached font.
 */
function cache_font($font, $fontfile, $candidatelocation) {
    global $CFG;
    $cachefont = "$candidatelocation/$font";

    clearstatcache();
    if (!file_exists($candidatelocation)) {
        @mkdir($candidatelocation, $CFG->directorypermissions, true);
    }

    // Prevent serving of incomplete file from concurrent request,
    // the rename() should be more atomic than copy().
    ignore_user_abort(true);
    if (@copy($fontfile, $cachefont.'.tmp')) {
        rename($cachefont.'.tmp', $cachefont);
        @chmod($cachefont, $CFG->filepermissions);
        @unlink($cachefont.'.tmp'); // Just in case anything fails.
    }
    return $cachefont;
}
