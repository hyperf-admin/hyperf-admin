<?php

class AlertMessage
{
    public $type;

    public $message;

    public $receivers;

    public $webhook;

    public $title;

    public function __construct(array $params)
    {
        foreach($params as $key => $val) {
            if(property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }
}
