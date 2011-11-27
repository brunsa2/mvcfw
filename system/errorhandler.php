<?php

class ErrorHandler {
    public function shutdown($message) {
        echo 'The system has encountered a fatal error and has shut down with the following message: ' . $message;
        exit;
    }
}

?>