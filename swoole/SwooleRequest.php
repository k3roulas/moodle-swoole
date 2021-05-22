<?php

class SwooleRequest {

    static string $content;

    static public function setContent(string $content) {
        SwooleRequest::$content = $content;
    }

    static public function getContent() {
        return SwooleRequest::$content;
    }
}
