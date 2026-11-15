<?php
declare(strict_types=1);

// Accepts JSON bodies (the fetch client) and form posts (manual testing).
function json_input(): array
{
    $body = json_decode(file_get_contents('php://input') ?: '', true);

    return is_array($body) ? $body : $_POST;
}
