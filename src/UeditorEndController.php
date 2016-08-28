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
use Illuminate\Support\Facades\Cookie;

class UeditorEndController extends Controller
{
    use UeditorUploaderAbstract;
    protected $cookieName = 'zmz';

    public function demoIndex()
    {
        $is_logined = $this->checkGuard();
        return view('zhangmazi::ueditorDemoIndex', ['is_logined' => $is_logined]);
    }

    /**
     * 登录
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function demoLogin(Request $request)
    {
        $account = $request->input('account', '');
        $password = $request->input('password', '');
        if ($account == 'zhangmazi' && $password == '88888888') {
            return redirect(route('zhangmazi_udemo_index'))->cookie($this->setLogined($account));
        } else {
            return abort(500, '登录失败');
        }
    }

    /**
     * 退出登录
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function demoLogout()
    {
        return redirect(route('zhangmazi_udemo_index'))->cookie($this->unsetLogined());
    }

    /**
     * 重写验证
     * @return bool
     */
    protected function checkGuard()
    {
        $cookie = Cookie::get($this->cookieName);
        return $cookie == 'zhangmazi';
    }


    /**
     * 设置登录状态
     * @param $account
     * @return mixed
     */
    private function setLogined($account)
    {
        return Cookie::make($this->cookieName, $account);
    }

    /**
     * 设置退出状态
     * @return mixed
     */
    private function unsetLogined()
    {
        return Cookie::forget($this->cookieName);
    }
}
