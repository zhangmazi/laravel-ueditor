<?php
/**
 * 编辑器服务器提供者.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 21:42
 */
namespace Zhangmazi\Ueditor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class UeditorServiceProivder extends ServiceProvider
{
    /**
     * 指定是否延缓提供者加载
     *
     * @var bool
     */
    //protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->publishes([__DIR__.'/assets' => public_path('assets')], 'public');
        $this->publishes([__DIR__ . '/config' => config_path()], 'config');
        $this->publishes([__DIR__ . '/lang' => resource_path('lang/vendor/zhangmazi')]);
        $this->publishes([__DIR__.'/views' => resource_path('views/vendor/zhangmazi')], 'zhangmazi');
        $this->loadViewsFrom(__DIR__.'/views', 'zhangmazi');
        //define language alias
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'ueditor');

        //use Router
        self::registerRoutes($router);
    }

    /**
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $filesystems_config = config('filesystems');
        $package_filesystems_config = include(__DIR__.'/config/zhangmazi/filesystems.php');
        $merge_disks_config = array_merge($package_filesystems_config['disks'], $filesystems_config['disks']);
        $filesystems_config['disks'] = $merge_disks_config;
        $this->app['config']->set(
            'filesystems',
            $filesystems_config
        );
        //绑定上传工具, 非单例
        $this->app->bind('zhangmazi.ueditor.uploader', function ($app) {
            return new Uploader();
        });
    }

    /**
     * 注册路由
     * @param $router
     * @return void
     */
    protected function registerRoutes($router)
    {
        $route_config = config('zhangmazi.ueditor', []);
        if (!empty($route_config['routes'])) {
            foreach ($route_config['routes'] as $k => $v) {
                //如果有定义路由URI
                if (!empty($v['uri'])) {
                    $v['uri'] = !empty($v['uri']) ? $v['uri'] : 'front/ueditor/service';
                    $v['as'] = !empty($v['as']) ? $v['as'] : 'zhangmazi_front_ueditor_service';
                    $v['group_as'] = !empty($v['group_as']) ? $v['group_as'] : '';
                    //处理命名空间
                    $group_params = ['namespace' => __NAMESPACE__];
                    if (!$v['via_integrate']) {
                        $group_params['namespace'] = $v['namespace'];
                    }
                    if (!$v['group_as']) {
                        $group_params['group_as'] = $v['group_as'];
                    }
                    //处理中间件
                    if (!empty($v['middleware'])) {
                        $group_params['middleware'] = $v['middleware'];
                    }
                    if (!empty($v['prefix'])) {
                        $group_params['prefix'] = $v['prefix'];
                    }
                    if (!empty($v['domain'])) {
                        $group_params['domain'] = $v['domain'];
                    }
                    //写入路由组
                    $router->group($group_params, function ($router) use ($v) {
                        $router->any(
                            $v['uri'],
                            [
                                'uses' => !empty($v['uses']) ? $v['uses'] : 'UeditorFrontController@service',
                                'as' => $v['as']
                            ]
                        );
                    });
                }
            }
        }
    }
}
