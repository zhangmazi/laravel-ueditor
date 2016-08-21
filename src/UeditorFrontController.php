<?php
/**
 * 控制器.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 22:22
 */
namespace Zhangmazi\Ueditor;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class UeditorFrontController extends UeditorUploaderAbstract
{
    protected $cookieName = 'zmz';

    /**
     * Demo首页
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function demoIndex(Request $request)
    {
        Session::start();
        $is_logined = $this->checkLogined();
        return view('zhangmazi::ueditorDemoIndex', ['is_logined' => $is_logined]);
    }

    /**
     * 重写验证
     * @return bool
     */
    protected function checkLogined()
    {
        $cookie = Cookie::get($this->cookieName);
        return $cookie == 'zhangmazi';
    }
}
