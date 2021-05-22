<?php

/**
 * Generate the theme CSS and store it.
 *
 * @param   theme_config    $theme The theme to be generated
 * @param   int             $rev The theme revision
 * @param   int             $themesubrev The theme sub-revision
 * @param   string          $candidatedir The directory that it should be stored in
 * @return  string          The path that the primary CSS was written to
 */
function theme_styles_generate_and_store($theme, $rev, $themesubrev, $candidatedir) {
    global $CFG;
    require_once("{$CFG->libdir}/filelib.php");

    // Generate the content first.
    if (!$csscontent = $theme->get_css_cached_content()) {
        $csscontent = $theme->get_css_content();
        $theme->set_css_content_cache($csscontent);
    }

    if ($theme->get_rtl_mode()) {
        $type = "all-rtl";
    } else {
        $type = "all";
    }

    // Determine the candidatesheet path.
    $candidatesheet = "{$candidatedir}/" . theme_styles_get_filename($type, $themesubrev, $theme->use_svg_icons());

    // Store the CSS.
    css_store_css($theme, $candidatesheet, $csscontent);

    // Store the fallback CSS in the temp directory.
    // This file is used as a fallback when waiting for a theme to compile and is not versioned in any way.
    $fallbacksheet = make_temp_directory("theme/{$theme->name}")
        . "/"
        . theme_styles_get_filename($type, 0, $theme->use_svg_icons());
    css_store_css($theme, $fallbacksheet, $csscontent);

    // Delete older revisions from localcache.
    $themecachedirs = glob("{$CFG->localcachedir}/theme/*", GLOB_ONLYDIR);
    foreach ($themecachedirs as $localcachedir) {
        $cachedrev = [];
        preg_match("/\/theme\/([0-9]+)$/", $localcachedir, $cachedrev);
        $cachedrev = isset($cachedrev[1]) ? intval($cachedrev[1]) : 0;
        if ($cachedrev > 0 && $cachedrev < $rev) {
            fulldelete($localcachedir);
        }
    }

    // Delete older theme subrevision CSS from localcache.
    $subrevfiles = glob("{$CFG->localcachedir}/theme/{$rev}/{$theme->name}/css/*.css");
    foreach ($subrevfiles as $subrevfile) {
        $cachedsubrev = [];
        preg_match("/_([0-9]+)\.([0-9]+\.)?css$/", $subrevfile, $cachedsubrev);
        $cachedsubrev = isset($cachedsubrev[1]) ? intval($cachedsubrev[1]) : 0;
        if ($cachedsubrev > 0 && $cachedsubrev < $themesubrev) {
            fulldelete($subrevfile);
        }
    }

    return $candidatesheet;
}

/**
 * Fetch the preferred fallback content location if available.
 *
 * @param   theme_config    $theme The theme to be generated
 * @return  string          The path to the fallback sheet on disk
 */
function theme_styles_fallback_content($theme) {
    global $CFG;

    if (!$theme->usefallback) {
        // This theme does not support fallbacks.
        return false;
    }

    $type = $theme->get_rtl_mode() ? 'all-rtl' : 'all';
    $filename = theme_styles_get_filename($type);

    $fallbacksheet = "{$CFG->tempdir}/theme/{$theme->name}/{$filename}";
    if (file_exists($fallbacksheet)) {
        return $fallbacksheet;
    }

    return false;
}

/**
 * Get the filename for the specified configuration.
 *
 * @param   string  $type The requested sheet type
 * @param   int     $themesubrev The theme sub-revision
 * @param   bool    $usesvg Whether SVGs are allowed
 * @return  string  The filename for this sheet
 */
function theme_styles_get_filename($type, $themesubrev = 0, $usesvg = true) {
    $filename = $type;
    $filename .= ($themesubrev > 0) ? "_{$themesubrev}" : '';
    $filename .= $usesvg ? '' : '-nosvg';

    return "{$filename}.css";
}

/**
 * Determine the correct etag for the specified configuration.
 *
 * @param   string  $themename The name of the theme
 * @param   int     $rev The revision number
 * @param   string  $type The requested sheet type
 * @param   int     $themesubrev The theme sub-revision
 * @param   bool    $usesvg Whether SVGs are allowed
 * @return  string  The etag to use for this request
 */
function theme_styles_get_etag($themename, $rev, $type, $themesubrev, $usesvg) {
    $etag = [$rev, $themename, $type, $themesubrev];

    if (!$usesvg) {
        $etag[] = 'nosvg';
    }

    return sha1(implode('/', $etag));
}
