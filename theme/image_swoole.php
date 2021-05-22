<?php
require_once("$CFG->dirroot/lib/xsendfilelib.php");

function send_cached_image($imagepath, $etag) {
    global $CFG;

    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;
    $pathinfo = pathinfo($imagepath);
    $imagename = $pathinfo['filename'].'.'.$pathinfo['extension'];

    $mimetype = get_contenttype_from_ext($pathinfo['extension']);

    SwooleHeader::addHeader('Etag: "'.$etag.'"');
    SwooleHeader::addHeader('Content-Disposition: inline; filename="'.$imagename.'"');
    SwooleHeader::addHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($imagepath)) .' GMT');
    SwooleHeader::addHeader('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    SwooleHeader::addHeader('Pragma: ');
    SwooleHeader::addHeader('Cache-Control: public, max-age='.$lifetime.', no-transform, immutable');
    SwooleHeader::addHeader('Accept-Ranges: none');
    SwooleHeader::addHeader('Content-Type: '.$mimetype);
//    header('Etag: "'.$etag.'"');
//    header('Content-Disposition: inline; filename="'.$imagename.'"');
//    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($imagepath)) .' GMT');
//    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
//    header('Pragma: ');
//    header('Cache-Control: public, max-age='.$lifetime.', no-transform, immutable');
//    header('Accept-Ranges: none');
//    header('Content-Type: '.$mimetype);

//    if (xsendfile($imagepath)) { // PLN TODO
//        die;
//    }

//    if ($mimetype === 'image/svg+xml') {
//        // SVG format is a text file. So we can compress SVG files.
//        if (!min_enable_zlib_compression()) {
//            header('Content-Length: '.filesize($imagepath));
//        }
//    } else {
//        // No need to compress other image formats.
//        header('Content-Length: '.filesize($imagepath));
//    }

    readfile($imagepath);
    throw new ExceptionExit('From send_cached_image');
    die;
}

function send_uncached_image($imagepath) {
    $pathinfo = pathinfo($imagepath);
    $imagename = $pathinfo['filename'].'.'.$pathinfo['extension'];

    $mimetype = get_contenttype_from_ext($pathinfo['extension']);

    header('Content-Disposition: inline; filename="'.$imagename.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + 15) .' GMT');
    header('Pragma: ');
    header('Accept-Ranges: none');
    header('Content-Type: '.$mimetype);
    header('Content-Length: '.filesize($imagepath));

    readfile($imagepath);
    die;
}

function image_not_found() {
    header('HTTP/1.0 404 not found');
    die('Image was not found, sorry.');
}

function get_contenttype_from_ext($ext) {
    switch ($ext) {
        case 'svg':
            return 'image/svg+xml';
        case 'png':
            return 'image/png';
        case 'gif':
            return 'image/gif';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'ico':
            return 'image/vnd.microsoft.icon';
    }
    return 'document/unknown';
}

/**
 * Caches a given image file.
 *
 * @param string $image The name of the image that was requested.
 * @param string $imagefile The location of the image file we want to cache.
 * @param string $candidatelocation The location to cache it in.
 * @return string The path to the cached image.
 */
function cache_image($image, $imagefile, $candidatelocation) {
    global $CFG;
    $pathinfo = pathinfo($imagefile);
    $cacheimage = "$candidatelocation/$image.".$pathinfo['extension'];

    clearstatcache();
    if (!file_exists(dirname($cacheimage))) {
        @mkdir(dirname($cacheimage), $CFG->directorypermissions, true);
    }

    // Prevent serving of incomplete file from concurrent request,
    // the rename() should be more atomic than copy().
    ignore_user_abort(true);
    if (@copy($imagefile, $cacheimage.'.tmp')) {
        rename($cacheimage.'.tmp', $cacheimage);
        @chmod($cacheimage, $CFG->filepermissions);
        @unlink($cacheimage.'.tmp'); // just in case anything fails
    }
    return $cacheimage;
}
