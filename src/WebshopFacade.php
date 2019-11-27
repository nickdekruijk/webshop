<?php

namespace NickDeKruijk\Webshop;

class WebshopFacade extends \Illuminate\Support\Facades\Facade
{
    /**
     * Name of the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'nickdekruijk-webshop';
    }
}
