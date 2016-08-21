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
        <p>支持自定义路由, 默认前后台独立控制器,支持重写方法方便自己的业务逻辑处理,支持扩展图片助手(推荐使用Intervention\Image第三方包)</p>
    </div>


    <div class="page-header">
        <h1>安装</h1>
    </div>
    <div class="row">
        <h4>1.Composer 安装</h4>
        <pre>composer require zhangmazi/laravel-ueditor</pre>
        <h4>2.编辑config/app.php文件,在节点[providers]中加入</h4>
        <pre>Zhangmazi\Ueditor\UeditorServiceProivder::class</pre>
        <h4>3.在命令行工具执行</h4>
        <pre>php artisan vendor:publish --provider="Zhangmazi\Ueditor\UeditorServiceProivder"</pre>
        <p>相关资源配置会成功发布到:config/zhangmazi/(配置); public/assets/(静态资源); resources/views/vendor/zhangmazi/(视图,包含demo所需)</p>
    </div>
    <div class="row">
        <h3>配置</h3>
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

    // 生成一个全部工具条的编辑器
    var ueditor_full = UE.getEditor('demo_full_toolbar', {
        'serverUrl' : '{{ route("zhangmazi_front_ueditor_service", ['_token' => csrf_token()]) }}',
        'autoHeightEnabled' : false,
        'pageBreakTag' : 'editor_page_break_tag',
        'maximumWords' : 1000000,   //自定义可以输入多少字
        'initialFrameWidth' : "100%",
        'initialFrameHeight' : ueditor_height
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
        'initialFrameWidth' : 600,  //自定义高度
        'initialFrameHeight' : 300  //自定义高度
    });

    // 生成后端演示编辑器, 注意这里的serverUrl配置路由跟前端不同
    var ueditor_end = UE.getEditor('demo_end_toolbar', {
        'serverUrl' : '{{ route("zhangmazi_end_ueditor_service", ['_token' => csrf_token()]) }}',
        'autoHeightEnabled' : false,
        'pageBreakTag' : 'editor_page_break_tag',
        'maximumWords' : 1000000,   //自定义可以输入多少字
        'initialFrameWidth' : "100%",
        'initialFrameHeight' : ueditor_height
    });
</script>
<!--end 人工手动根据自己需要进行创建编辑器-->






</body>
</html>