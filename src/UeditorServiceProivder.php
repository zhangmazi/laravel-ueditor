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
        //注册demo路由
        $this->registerRouteDemo($router);
        if (!empty($route_config['routes'])) {
            foreach ($route_config['routes'] as $k => $v) {
                //如果有定义路由URI
                if (!empty($v['uri'])) {
                    $v['uri'] = !empty($v['uri']) ? $v['uri'] : 'front/ueditor/service';
                    $v['as'] = !empty($v['as']) ? $v['as'] : 'zhangmazi_front_ueditor_service';
                    $v['group_as'] = !empty($v['group_as']) ? $v['group_as'] : '';
                    //处理命名空间
                    $group_params = [];
                    if (!$v['via_integrate']) {
                        $group_params['group_namespace'] = $v['group_namespace'];
                    }
                    if (!$v['group_as']) {
                        $group_params['group_as'] = $v['group_as'];
                    }
                    //处理路由组中间件
                    if (!empty($v['group_middleware'])) {
                        $group_params['group_middleware'] = $v['group_middleware'];
                    }
                    if (!empty($v['group_prefix'])) {
                        $group_params['group_prefix'] = $v['group_prefix'];
                    }
                    if (!empty($v['group_domain'])) {
                        $group_params['group_domain'] = $v['group_domain'];
                    }
                    if (!empty($group_params)) {
                        //写入路由组
                        $router->group($group_params, function ($router) use ($v) {
                            $param = [
                                'uses' => !empty($v['uses']) ? $v['uses'] : 'UeditorFrontController@service',
                            ];
                            if (!empty($v['as'])) {
                                $param['as'] = $v['as'];
                            }
                            if (!empty($v['middleware'])) {
                                $param['middleware'] = $v['middleware'];
                            }
                            $router->any($v['uri'], $param);
                        });
                    } else {
                        //写入路由组
                        $uses = '\\' . __NAMESPACE__ . '\\' .
                            (!empty($v['uses']) ? $v['uses'] : 'UeditorFrontController@service');
                        if (!$v['via_integrate']) {
                            $uses = !empty($v['uses']) ? $v['uses'] : $uses;
                        }
                        $param = [
                            'uses' => $uses,
                        ];
                        if (!empty($v['as'])) {
                            $param['as'] = $v['as'];
                        }
                        if (!empty($v['middleware'])) {
                            $param['middleware'] = $v['middleware'];
                        }
                        $router->any($v['uri'], $param);
                    }
                }
            }
        }
    }

    protected function registerRouteDemo($router)
    {
        if (config('app.debug') === true) {
            $router->get(
                'zhangmazi/ueditor/demo/index',
                ['as' => 'zhangmazi_udemo_index', 'uses' => '\\' .__NAMESPACE__ .'\\UeditorFrontController@demoIndex']
            );
            $router->get(
                'zhangmazi/ueditor/demo/end',
                ['as' => 'zhangmazi_udemo_end', 'uses' => '\\' .__NAMESPACE__ .'\\UeditorEndController@demoIndex']
            );
            $router->post(
                'zhangmazi/ueditor/demo/login',
                ['as' => 'zhangmazi_udemo_login', 'uses' => '\\' .__NAMESPACE__ .'\\UeditorEndController@demoLogin']
            );
            $router->get(
                'zhangmazi/ueditor/demo/logout',
                ['as' => 'zhangmazi_udemo_logout', 'uses' => '\\' .__NAMESPACE__ .'\\UeditorEndController@demoLogout']
            );
        }
    }
}
