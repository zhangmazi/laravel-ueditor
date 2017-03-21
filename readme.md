
# 百度编辑器 For Laravel 5

支持自定义路由,支持图片、附件上传, 默认前后台独立控制器,支持重写方法方便自己的业务逻辑处理,支持扩展图片助手(推荐使用Intervention\Image第三方包)


## 官网

[NinJa911工作室](http://www.ninja911.com).

## 疑问讨论

请在issue里new一个.

## 其他源

[国外 github.com](https://github.com/zhangmazi/laravel-ueditor)
[国内 coding.net](https://coding.net/u/ninja911/p/laravel-ueditor)


## 授权

此Laravel 扩展包基于MIT协议开源[MIT license](http://opensource.org/licenses/MIT).


# 安装

#### 1.Composer 安装

```shell
composer require "zhangmazi/laravel-ueditor"
```

#### 2.编辑config/app.php文件,在节点[providers]中加入

```php
Zhangmazi\Ueditor\UeditorServiceProivder::class
```

#### 3.在命令行工具执行

```shell
php artisan vendor:publish --provider="Zhangmazi\Ueditor\UeditorServiceProivder"
```

相关资源配置会成功发布到:config/zhangmazi/(配置); public/assets/(静态资源); resources/views/vendor/zhangmazi/(视图,包含demo所需).

### 配置

#### 1.配置config/zhangmazi/filesystem.php

请根据注释填写,特别要注意root和url_root,这个2个很关键,因为直接导致你是否能上传成功和是否能正常开放预览附件; root的物理路径一定有0755或者0777(当需要建立子目录时)权限.
[version 1.0.5]这次更新主要是配置调整,所以重要操作,请将disks节点数组复制到config/filesystem.php内的disks内,并注意如果启用S3驱动,root一定要是null

#### 2.配置config/zhangmazi/ueditor.php

请根据注释填写,节点[routes]支持多组应用场景,其配置其实就Laravel的Route原生配置方法; 其中带有"group_"前缀的都不填,将不使用路由组模式; 如果"via_integrate"为true,将使用内置命名空间,同时不要修改"uses".

#### 3.配置config/zhangmazi/ext2mime.php

这个增加上传安全性的, 如果您觉得多了和少了, 请自行根据格式进行修改.

# 使用

### Demo使用

开发此包时, 为了增加体验感, 特为大家准备了demo. 启用内置服务运行命令

```shell
php artisan serve --host=0.0.0.0 --port=8030
```

访问 [http://localhost:8030/zhangmazi/ueditor/demo/index](http://localhost:8030/zhangmazi/ueditor/demo/index), 其中localhost跟更改为你自己的绑定的域名.

为了安全性, 在[.env]文件中APP_DEBUG=true才能使用demo,否则无法访问以上demo相关路由地址.


### 如何使用

#### 1.在您的视图中, 在body闭包前(即`</body>`),加入以下代码

```html
@include("zhangmazi::ueditor")
```

#### 2.在您的视图中, 需要占位编辑器的dom节点内,加入以下代码

```html
<script id="ueditor_filed" name="article_content" type="text/plain"></script>
```

其中id="ueditor_filed"这里是需要给百度编辑器创建的时候用到的名字, 如果同一个页面有多个,这个id请用不同的名字替换.

#### 3.在您的视图中, 在body闭包前(即`</body>`),加入以下代码

```html
<script>
    var ueditor_full = UE.getEditor('ueditor_filed', {
    'serverUrl' : '{{ route("zhangmazi_front_ueditor_service", ['_token' => csrf_token()]) }}'
});
</script>
```

如果需要更多参考以及调用样板,比如如何自定义编辑工具栏、同一个页面多个编辑器,请查看阅读文件 vendor/zhangmazi/ueditor/src/views/ueditorDemoIndex.blade.php


# 自定义扩展

以下说明需要一定PHP知识和Laravel5框架了解背景

### 1.扩展控制器

新建一个控制器, 内部复用一个类UeditorUploaderAbstract,有兴趣可以查看这个类,根据自身业务选择性重写覆盖.

```php
<?php
/**
 * 自定义的编辑器控制器.
 * 可以观看 Zhangmazi\Ueditor\UeditorUploaderAbstract 复用类的方法,根据自身业务选择性重写覆盖
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 22:22
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Zhangmazi\Ueditor\UeditorUploaderAbstract;

class CustomUeditorController extends Controller
{
    use UeditorUploaderAbstract;
    /**
     * 记录上传日志(这些方法都可以重写覆盖)
     * @return mixed
     */
    protected function insertRecord()
    {

    }

    /**
     * 验证是否合法(这些方法都可以重写覆盖)
     * @return bool|mixed
     */
    protected function checkGuard()
    {
        //如果是后端
        //return Auth::check();
        return true;
    }

    /**
     * 获取相对于public_path()根目录的相对目录
     * @return bool|mixed
     */
    protected function getRelativeDir()
    {
        return 'uploads/ueditor';
    }

    /**
     * 获取保存根目录路径
     * @paraam string $driver_name 驱动名
     * @return string
     */
    protected function getSaveRootPath($driver_name = 'local')
    {
        return storage_path('app/ueditor');
    }

    /**
     * 删除原始文件
     * @param $file
     * @return bool
     */
    protected function deleteOriginFile($file)
    {
        File::delete($file['file_native_path']);
        File::delete($file['origin_pic_native_path']);

        return true;
    }
}

?>
```

### 2.配置config/zhangmazi/ueditor.php

把相关路由配置一下,不用内置的

### 3.查看路由清单,看是否生效,命令行里执行

```shell
php artisan route:list
```

# TODO


### 1.完成i18n语言包中的中文和英文

目前个人时间比较紧,如果谁有愿意翻译修改支持i18n,大大的感谢,请提交github merge request

### 2.发现或者支持更多的Storage第三方文件存储驱动

目前Laravel对亚马逊S3支持的相对完美, 但像其他国内的云存储服务,需要用Storage::extend来扩展驱动以及配置
