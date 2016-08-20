<?php
/**
 * 门.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 22:32
 */
namespace Zhangmazi\Ueditor\Facades;

use Illuminate\Support\Facades\Facade;

class Ueditor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ueditor';
    }
}
