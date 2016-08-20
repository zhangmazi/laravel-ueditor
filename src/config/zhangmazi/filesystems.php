<?php
/**
 * 描述.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-21 06:47
 */
return [
    'disks' => [
        'public_attachment' => [
            'driver' => 'local',
            'root'   => public_path(),
            'visibility' => null,
            'url_root' => '',   //项目附件服务器URL根,如果是第三方比如S3等,请在默认项目目录config下,填写补充这个url_root
        ],
    ],
    'ueditor_disk_name' => 'public_attachment',
];