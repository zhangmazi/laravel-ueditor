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
            'namespace' => '',  //使用集成,此命名空间将无效
            'group_as' => 'zhangmazi_front',
            'uri' => 'front/ueditor/service',
            'uses' => 'UeditorFrontController@service',
            'as' => 'zhangmazi_front_ueditor_service',
            'middleware' => '',
            'prefix' => '',
            'domain' => '',
        ],
        'end' => [
            'via_integrate' => true,    //是否使用集成的
            'namespace' => '',  //使用集成,此命名空间将无效
            'group_as' => 'zhangmazi_end',
            'uri' => 'end/ueditor/service',
            'uses' => 'UeditorEndController@service',
            'as' => 'zhangmazi_end_ueditor_service',
            'middleware' => '',
            'prefix' => '',
            'domain' => '',
        ],
    ],
    'filesystems' => [
        'disks' => [
            'public_attachment' => [
                'driver' => 'local',
                'root'   => public_path(),
                'visibility' => null,
                'url_root' => '',   //项目附件服务器URL根,如果是第三方比如S3等,请在默认项目目录config下,填写补充这个url_root
            ],
        ],
        'ueditor_disk_name' => 'public_attachment',
    ],
    'is_catch_images' => false,  //是否自动抓取远程图片
    'imageManagerListSize' => 20,   //每次列出文件数量
    'upload_max_size_by_k' => 20480,    //上传最大限制,单位KB
];
