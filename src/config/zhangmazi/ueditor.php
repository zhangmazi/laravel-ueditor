<?php
/**
 * 百度编辑器配置.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 21:59
 */
return [
    'routes' => [
        'front' => [
            'via_integrate' => true,    //是否使用集成的
            'group_namespace' => '',  //使用集成,此命名空间将无效
            'group_prefix' => '', //组前缀
            'group_domain' => '', //组域名
            'group_as' => 'zhangmazi_front',    //组里的别名
            'group_middleware' => [],   //组里的中间件
            'uri' => 'front/ueditor/service',   //路由URI
            'uses' => 'UeditorFrontController@service', //引用控制器以及方法
            'as' => 'zhangmazi_front_ueditor_service',  //别名
            'middleware' => [], //中间件
        ],
        'end' => [
            'via_integrate' => true,    //是否使用集成的
            'group_namespace' => '',  //使用集成,此命名空间将无效
            'group_prefix' => '', //组前缀
            'group_domain' => '', //组域名
            'group_as' => 'zhangmazi_end',      //组里的别名
            'group_middleware' => [],       //组里的中间件
            'uri' => 'end/ueditor/service',     //路由URI
            'uses' => 'UeditorEndController@service',   //引用控制器以及方法
            'as' => 'zhangmazi_end_ueditor_service',    //别名
            'middleware' => [],
        ],
    ],
    'upload_field_name' => 'upload_files',  //上传表单中的name
    'is_catch_images' => false,  //是否自动抓取远程图片
    'imageManagerListSize' => 20,   //每次列出文件数量
    'upload_max_size_by_k' => 20480,    //上传最大限制,单位KB
];
