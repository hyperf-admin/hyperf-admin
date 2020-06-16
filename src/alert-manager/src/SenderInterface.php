<?php
namespace HyperfAdmin\AlertManager;

interface SenderInterface
{
    public function sendText($message, $at = 'all');

    public function sendMarkdown($message, $at = 'all');
}
