<?php
function json_input() {
    $body = json_decode(file_get_contents('php://input') ?: '', true);

    return is_array($body) ? $body : $_POST;
}
