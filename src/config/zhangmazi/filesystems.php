<?php
/**
 * 文件存储配置.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-21 06:47
 */
return [
    'disks' => [
        'public_attachment' => [
            'driver' => 'local',    //驱动类型,local=本地,s3=亚马逊,当然可以Stoarge::extend扩展比如七牛类似的第三方服务
            'root'   => storage_path('app/'),  //文件存储的本地物理根目录, 如果是S3驱动,请设置为null值
            'visibility' => null,   //对外可视,比如亚马逊S3服务,这里就会填写public,一般本地local,填写null即可
            'url_root' => '/',   //项目附件服务器URL根,如果是第三方比如S3等,请在默认项目目录config下,填写补充这个url_root
        ],
        'china_s3' => [
            'driver' => 's3',    //驱动类型,local=本地,s3=亚马逊,当然可以Stoarge::extend扩展比如七牛类似的第三方服务
            'root'   => null,  //文件存储的本地物理根目录, 如果是S3驱动,请设置为null值
            'visibility' => 'public',   //对外可视,比如亚马逊S3服务,这里就会填写public,一般本地local,填写null即可
            'url_root' => '//s3.cn-north-1.amazonaws.com.cn/hellophp/',   //项目附件服务器URL根,如果是第三方比如S3等,请在默认项目目录config下,填写补充这个url_root
            'key'    => '',
            'secret' => '',
            'region' => 'cn-north-1',
            'bucket' => '',
        ],
    ],
    'ueditor_disk_name' => 'public_attachment', // 百度编辑器所使用的文件磁盘名, 这个定义名必须包含在filesystems.php配置[disks]节点里
];
