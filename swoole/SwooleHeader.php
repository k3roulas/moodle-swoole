<?php

class SwooleHeader {

    static private $headers = [];

    static public function addHeader($header) {
        list($key, $value) = explode(':', $header, 2);
        SwooleHeader::$headers[] = (object) ['key' => $key, 'value' => $value];
    }

    static public function reset() {
        SwooleHeader::$headers = [];
    }

    static public function getHeaders() {
        return SwooleHeader::$headers;
    }
}
