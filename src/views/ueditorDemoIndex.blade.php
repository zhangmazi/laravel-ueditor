<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Demo演示 - 百度编辑器For Laravel 5</title>
    <link href="{{ asset('assets/editors/demo/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/editors/demo/css/bootstrap-theme.min.css') }}" rel="stylesheet">
</head>
<body>

<!-- Fixed navbar -->
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="#">Demo演示</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                @if (!$is_logined)
                    <li><a href="javascript:;">未登录,后台上传会失败(有checkGuard认证)</a></li>
                @else
                    <li><a href="javascript:;">已登录:zhangmazi</a></li>
                    <li><a href="{{ route('zhangmazi_udemo_logout') }}">退出</a></li>
                @endif
            </ul>
            @if (!$is_logined)
                <form class="navbar-form navbar-right form-inline" method="post" action="{{ route('zhangmazi_udemo_login') }}">
                    <input type="text" class="form-control" name="account" placeholder="默认账号:zhangmazi">
                    <input type="text" class="form-control" name="password" placeholder="默认密码:88888888">
                    <button class="btn btn-primary">登录</button>
                </form>
            @endif
        </div>
    </div>
</nav>

<div class="container theme-showcase" role="main">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <h2>百度编辑器 For Laravel 5</h2>
        <p>支持自定义路由,支持图片、附件上传, 默认前后台独立控制器,支持重写方法方便自己的业务逻辑处理,支持扩展图片助手(推荐使用Intervention\Image第三方包)</p>
    </div>


    <div class="page-header">
        <h1>安装</h1>
    </div>
    <div class="row">
        <h4>1.Composer 安装</h4>
        <pre>composer require "zhangmazi/laravel-ueditor:^1.0"</pre>
        <h4>2.编辑config/app.php文件,在节点[providers]中加入</h4>
        <pre>Zhangmazi\Ueditor\UeditorServiceProivder::class</pre>
        <h4>3.在命令行工具执行</h4>
        <pre>php artisan vendor:publish --provider="Zhangmazi\Ueditor\UeditorServiceProivder"</pre>
        <p>相关资源配置会成功发布到:config/zhangmazi/(配置); public/assets/(静态资源); resources/views/vendor/zhangmazi/(视图,包含demo所需).</p>
    </div>
    <div class="row">
        <h3>配置</h3>
        <h4>1.配置config/zhangmazi/filesystem.php</h4>
        <p>请根据注释填写,特别要注意root和url_root,这个2个很关键,因为直接导致你是否能上传成功和是否能正常开放预览附件; root的物理路径一定有0755或者0777(当需要建立子目录时)权限.</p>
        <p>[version 1.0.5]这次更新主要是配置调整,所以重要操作,请将disks节点数组复制到config/filesystem.php内的disks内,并注意如果启用S3驱动,root一定要是null</p>
        <h4>2.配置config/zhangmazi/ueditor.php</h4>
        <p>请根据注释填写,节点[routes]支持多组应用场景,其配置其实就Laravel的Route原生配置方法; 其中带有"group_"前缀的都不填,将不使用路由组模式; 如果"via_integrate"为true,将使用内置命名空间,同时不要修改"uses".</p>
        <h4>3.配置config/zhangmazi/ext2mime.php</h4>
        <p>这个增加上传安全性的, 如果您觉得多了和少了, 请自行根据格式进行修改.</p>
    </div>

    <div class="page-header">
        <h1>使用</h1>
    </div>
    <div class="row">
        <h3>Demo使用</h3>
        <p>开发此包时, 为了增加体验感, 特为大家准备了demo.</p>
        <p>
            <pre>php artisan serve --host=0.0.0.0 --port=8030</pre>
        </p>
        <p>访问 <a href="http://localhost:8030/zhangmazi/ueditor/demo/index">http://localhost:8030/zhangmazi/ueditor/demo/index</a>, 其中localhost跟更改为你自己的绑定的域名.</p>
        <p>为了安全性, 在[.env]文件中APP_DEBUG=true才能使用demo,否则无法访问以上demo相关路由地址.</p>
    </div>
    <div class="row">
        <h3>如何使用</h3>
        <h4>1.在您的视图中, 在body闭包前(即&lt;/body&gt;),加入以下代码</h4>
        <pre>@@include("zhangmazi::ueditor")</pre>
        <h4>2.在您的视图中, 需要占位编辑器的dom节点内,加入以下代码</h4>
        <pre><?php echo htmlspecialchars('<script id="ueditor_filed" name="article_content" type="text/plain"></script>'); ?></pre>
        <p>其中id="ueditor_filed"这里是需要给百度编辑器创建的时候用到的名字, 如果同一个页面有多个,这个id请用不同的名字替换.</p>
        <h4>3.在您的视图中, 在body闭包前(即&lt;/body&gt;),加入以下代码</h4>
        <pre>&lt;script&gt;
    var ueditor_full = UE.getEditor('demo_full_toolbar', {
    'serverUrl' : '@{{ route("zhangmazi_front_ueditor_service", ['_token' => csrf_token()]) }}'
});
&lt;/script&gt;</pre>
        <p>如果需要更多参考以及调用样板,比如如何自定义编辑工具栏、同一个页面多个编辑器,请查看阅读文件 vendor/zhangmazi/ueditor/src/views/ueditorDemoIndex.blade.php</p>
    </div>

    <div class="page-header">
        <h1>自定义扩展</h1>
        <p>以下说明需要一定PHP知识和Laravel5框架了解背景</p>
    </div>
    <div class="row">
        <h3>1.扩展控制器</h3>
        <p>新建一个控制器, 内部复用一个类UeditorUploaderAbstract,有兴趣可以查看这个类,根据自身业务选择性重写覆盖.</p>
        <pre>&lt;?php
/**
 * 自定义的编辑器控制器.
 * 可以观看 Zhangmazi\Ueditor\UeditorUploaderAbstract 复用类的方法,根据自身业务选择性重写覆盖
 *
 * @@author ninja911&lt;ninja911@@qq.com&gt;
 * @@date   2016-08-20 22:22
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

?&gt;</pre>
        <h3>2.配置config/zhangmazi/ueditor.php</h3>
        <p>把相关路由配置一下,不用内置的</p>
        <h3>3.查看路由清单,看是否生效,命令行里执行</h3>
        <pre>php artisan route:list</pre>
    </div>


    <div class="page-header">
        <h1>TODO</h1>
    </div>
    <div class="row">
        <h3>1.完成i18n语言包中的中文和英文</h3>
        <p>目前个人时间比较紧,如果谁有愿意翻译修改支持i18n,大大的感谢,请提交github merge request</p>
        <h3>2.发现或者支持更多的Storage第三方文件存储驱动</h3>
        <p>目前Laravel对亚马逊S3支持的相对完美, 但像其他国内的云存储服务,需要用Storage::extend来扩展驱动以及配置</p>
    </div>


    <div class="page-header">
        <h1>默认前台demo</h1>
    </div>

    <div class="row">
        <h3>全部的工具条(toolbar)展现出来</h3>
        <div class="col-md-12">
            <script id="demo_full_toolbar" name="content" type="text/plain"><?php echo htmlspecialchars('全部工具条展示');?></script>
        </div>
    </div>
    <hr />
    <div class="row">
        <h3>自定义的工具条(toolbar)</h3>
        <div class="col-md-12">
            <script id="demo_simple_toolbar" name="content" type="text/plain"><?php echo htmlspecialchars('自定义工具条展示,自定义高度,自定义宽度');?></script>
        </div>
    </div>

    <hr />
    <div class="page-header">
        <h1>后台demo</h1>
    </div>
    <div class="row">
        <h3 class="text-danger">需要先登录</h3>
        <div class="col-md-12">
            <script id="demo_end_toolbar" name="content" type="text/plain"><?php echo htmlspecialchars('若未登录,无法使用附件上传功能');?></script>
        </div>
    </div>

    <hr />
</div>

<footer class="footer">
    <div class="container">
        <p class="text-muted text-center">此Laravel 扩展包基于MIT协议开源. Powered By <a href="http://www.ninja911.com">NinJa911</a></p>
    </div>
</footer>





<!--start 引入百度编辑器的静态资源基础js文件-->
@include("zhangmazi::ueditor")
<!--end 引入百度编辑器的静态资源基础js文件-->

<!--start 人工手动根据自己需要进行创建编辑器-->
<script>
    // 定义默认编辑器高度
    var ueditor_height = 480;
    // UE编辑器上传参数-通用版
    function getUeditorCommonParams() {
        return {
            'thumb_appointed' : '0',    //强制缩略图, 1=是,0=否,支持多组,用逗号分隔开,如0,0,0,0
            'thumb_water' : '0',     //是否水印,1=是,0=否,支持多组,用逗号分隔开,如0,0,0,0
            'thumb_num' : '1',      //缩略图数量, 跟多组有关联性
            'thumb_max_width' : '600',  //缩略图最大宽度, 多组用逗号分隔并由小到大,如200,400,800
            'thumb_max_height' : '2000',//缩略图最大高度, 多组用逗号分隔并由小到大,如200,400,800
            'ext_type' : '100', //允许上传的扩展名
            'need_origin_pic' : 0,  //是否保留原图, 1=要, 0=不
            '_token' : '{{ csrf_token() }}',    //Laravel 校验token用的
        };
    }


    // 生成一个全部工具条的编辑器
    var ueditor_full = UE.getEditor('demo_full_toolbar', {
        'serverUrl' : '{{ route("zhangmazi_front_ueditor_service", ['_token' => csrf_token()]) }}',
        'autoHeightEnabled' : false,
        'pageBreakTag' : 'editor_page_break_tag',
        'maximumWords' : 1000000,   //自定义可以输入多少字
        'autoFloatEnabled' : false,
        'initialFrameWidth' : "100%",
        'initialFrameHeight' : ueditor_height
    });

    // 附加上其他参数,方便后端业务自主获取用
    ueditor_full.ready(function() {
        ueditor_full.execCommand('serverparam', function(editor) {
            return getUeditorCommonParams();
        });
    });


    // 简单toolbars
    // 具体配置,请参考[百度编辑器官方文档](http://fex.baidu.com/ueditor/#start-toolbar)
    var ueditor_toolbars = [
        ['fontfamily', 'fontsize', 'forecolor', 'backcolor', 'bold', 'italic', 'underline','strikethrough',
            'justifycenter', 'justifyleft', 'justifyright', 'fontborder', 'removeformat', 'link', 'unlink',
            'simpleupload', 'lineheight', 'imagecenter', 'preview', 'source', 'fullscreen'
        ]
    ];

    // 生成自定义工具条编辑器
    var ueditor_simple = UE.getEditor('demo_simple_toolbar', {
        'serverUrl' : '{{ route("zhangmazi_front_ueditor_service", ['_token' => csrf_token()]) }}',
        'autoHeightEnabled' : false,
        'pageBreakTag' : 'editor_page_break_tag',
        'maximumWords' : 1000000,   //自定义可以输入多少字
        'toolbars' : ueditor_toolbars,  //采用自定义工具条
        'autoFloatEnabled' : false,
        'initialFrameWidth' : 600,  //自定义高度
        'initialFrameHeight' : 300  //自定义高度
    });
    // 附加上其他参数,方便后端业务自主获取用
    ueditor_simple.ready(function() {
        ueditor_simple.execCommand('serverparam', function(editor) {
            return getUeditorCommonParams();
        });
    });


    // 生成后端演示编辑器, 注意这里的serverUrl配置路由跟前端不同
    var ueditor_end = UE.getEditor('demo_end_toolbar', {
        'serverUrl' : '{{ route("zhangmazi_end_ueditor_service", ['_token' => csrf_token()]) }}',
        'autoHeightEnabled' : false,
        'pageBreakTag' : 'editor_page_break_tag',
        'maximumWords' : 1000000,   //自定义可以输入多少字
        'autoFloatEnabled' : false,
        'initialFrameWidth' : "100%",
        'initialFrameHeight' : ueditor_height
    });
    // 附加上其他参数,方便后端业务自主获取用
    ueditor_end.ready(function() {
        ueditor_end.execCommand('serverparam', function(editor) {
            return getUeditorCommonParams();
        });
    });
</script>
<!--end 人工手动根据自己需要进行创建编辑器-->






</body>
</html>