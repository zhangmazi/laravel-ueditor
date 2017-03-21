<?php
/**
 * 上传工具.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-20 22:35
 */
namespace Zhangmazi\Ueditor;

use Storage;
use File;
use Log;

trait UeditorUploaderAbstract
{
    protected $storage = null;
    /**
     * 插入一条记录
     * @param array $setarr  提交数据
     * @param mixed $request 请求
     * @return mixed
     */
    protected function insertRecord($setarr, $request = null)
    {
        // todo 如果需要,请自行重写覆盖此方法
        return true;
    }

    protected function deleteByPks($pks)
    {
        $result = [
            'total_sucess' => 0,    //统计成功数量
            'total_failture' => 0,  //统计失败数据量
            'amount' => 0,      //$pks总量
            'status' => 'success',  //最后处理状态, success=成功, failture=失败
        ];
        if (is_array($pks)) {
            foreach ($pks as $k => $v) {
                $delete_status = $this->deleteByPks($v);
            }
        } else {
            //todo 请自行重写逻辑根据主键获取上传附件的基础信息,可以是对象也可以是对象
            $info = $this->getRecordInfo($pks);
            $file_status = $this->deleteFiles($info['file_relative_path']);
        }
    }

    /**
     * 删除物理文件
     * @param $files
     * @return mixed
     */
    protected function deleteFiles($files)
    {
        return $this->getStorage()->delete($files);
    }

    /**
     * 获取附件数据库中的信息.
     * @param int|string $pk 主键
     * @return array|object
     */
    protected function getRecordInfo($pk)
    {
        // todo 如果需要,请自行重写覆盖此方法
        $info = [
            // 文件相对路径, 比如http://www.ninja911.com/attachments/xxxxxxx.jpg
            // attachments/xxxxxxx.jpg 就是相对路径
            'file_relative_path' => '',
        ];
        return $info;
    }

    /**
     * 获取文件系统管理者对象
     * @return mixed
     */
    protected function getStorage()
    {
        if (!$this->storage) {
            $disk_name = $this->getStorageDiskName();
            $this->storage = Storage::disk($disk_name);
        }
        return $this->storage;
    }

    /**
     * 获取文件系统使用的磁盘名
     * @return mixed
     */
    protected function getStorageDiskName()
    {
        $config = $this->getStorageConfig();
        if (!empty($config['ueditor_disk_name'])) {
            return $config['ueditor_disk_name'];
        } else {
            return config('filesystems.default', 'local');
        }
    }

    /**
     * 获取合并后的文件系统配置
     * @return array
     */
    protected function getStorageConfig()
    {
        $c1 = config('zhangmazi.filesystems', []);
        $c2 = config('filesystems', []);
        $c2['ueditor_disk_name'] = $c1['ueditor_disk_name'];
        return array_merge($c1, $c2);
    }

    /**
     * 对外控制器需要的服务,百度请求统一URL地址所映射的控制器的方法
     * @return \Illuminate\Http\JsonResponse
     */
    public function service()
    {
        //验证的一个钩子
        if (!$this->checkGuard()) {
            return response()->json(['state' => '拒绝请求']);
        }
        //获取来自editor js 拼接的一个action
        $action = request('action', '');
        switch ($action) {
            case 'config':
                $cfg = $this->getJsonConfig();
                return response()->json($cfg);
                break;
            case 'UploadFile':
            case 'UploadImage':
                return $this->actionUploadImage();
                break;
            case 'UploadScrawl':
            case 'UploadSnapScreen':
            case 'UploadVideo':
                $ue_result = array(
                    'state' => '出错啦',
                    'url' => '',
                    'title' => '',
                    'original' => '',
                );
                return response()->json($ue_result);
                break;
            case 'CatchImage':
                if (config('zhangmazi.ueditor.is_catch_images', false)) {
                    return $this->actionCatchImage(); //启用远程采集图片，取消这个注释
                } else {
                    $ue_result = array(
                        'state' => 'ERROR',
                        'list' => array(),
                    );
                    return response()->json($ue_result);
                }
                break;
            case 'ListImage':
                return $this->actionListImage(1);
                break;
            case 'ListFile':
                return $this->actionListImage(2);
                break;
            default:
                $ue_result = array(
                    'state' => '非法请求Action',
                );
                return response()->json($ue_result);
                break;
        }
    }

    /**
     * 执行上传
     * @param string $upload_field_name 表单中的form name 上传字段
     * @return array
     */
    protected function uploadFile($upload_field_name = 'upload_files')
    {
        $request = request();
        if (!$request->hasFile($upload_field_name)) {
            return ['state' => '请选择文件'];
        }
        $arr_return = [];
        $arr_files = $this->dealFiles($request, $upload_field_name);

        $uploader = app()->make('zhangmazi.ueditor.uploader');
        $params = $this->getUploaderParams();
        $arr_exts = $uploader->getAllowExtsByType($params['ext_type']);
        $uploader->maxSize = 0; //不限制
        $uploader->allowExts = $arr_exts;
        $uploader->isSaveOriginFile = false;
        $uploader->allowExts = $arr_exts;
        $uploader->thumb = true;
        $uploader->thumbMaxWidth = $params['arr_thumb_max_width'];
        $uploader->thumbMaxHeight = $params['arr_thumb_max_height'];
        $uploader->thumbAppointed = $params['arr_thumb_appointed'];
        $uploader->thumbWater = $params['arr_thumb_water'];
        $uploader->waterType = $params['water_picture_exists'] ? 'picture' : '';
        $uploader->waterPicture = $params['water_picture'];
        $uploader->waterPosition = 9;
        $uploader->isSaveOriginFile = $params['need_origin_pic'];
        if (app()->bound('image') && class_exists('\Intervention\Image\ImageManager')) {
            $uploader->imageHelper = app()->make('image');
        }
        if ($arr_files) {
            //$storage_driver = $this->getStorage()->getDriver();
            $storage_config = $this->getStorageConfig();
            $disk_name = $this->getStorageDiskName();
            $storage_driver = $storage_config['disks'][$disk_name]['driver'];
            $save_root_path = $this->getSaveRootPath($storage_driver);
            $relative_dir = $this->getRelativeDir();
            $visibility = !empty($storage_config['disks'][$disk_name]['visibility']) ?
                $storage_config['disks'][$disk_name]['visibility'] : null;
            $url_root = $storage_config['disks'][$disk_name]['url_root'];
            foreach ($arr_files as $file) {
                $res = $uploader->uploadFile($file, $save_root_path, $relative_dir);
                if (!empty($res[0]['file_size'])) {
                    $img_url = $relative_dir . '/'. $res[0]['file_name'];
                    if (File::exists($res[0]['file_native_path']) && $this->getStorage()->put(
                        $img_url,
                        File::get($res[0]['file_native_path']),
                        $visibility
                    )) {
                        $res[0]['link_url'] = $url_root . $img_url;
                        $arr_return[] = $res[0];
                        //删除原始文件
                        $this->deleteOriginFile($res[0]);
                        //写入DB记录
                        $this->insertRecord($res[0], $request);
                    } else {
                        $arr_return[] = ['err' => '上传失败'];
                    }
                } elseif (!empty($res['err'])) {
                    $arr_return[] = $res;
                }
            }
        }

        return $arr_return;
    }

    /**
     * 获取上传请求参数
     * @return array
     */
    protected function getUploaderParams()
    {
        $thumb_appointed = request('thumb_appointed', ''); //是否强制尺寸高宽
        $thumb_water = request('thumb_water', '');     //是否加水印
        $thumb_num = intval(request('thumb_num', 0));   //缩略图数量
        $thumb_max_width = request('thumb_max_width', '');  //缩略图最大宽度，多组用半角逗号分隔开
        $thumb_max_height = request('thumb_max_height', '');//缩略图最大高度，多组用半角逗号分隔开
        $ext_type = intval(request('ext_type', 100)); //文件扩展名限制类型 0-不限制  1-图片 2-附件 3-flash 4-多媒体
        $need_origin_pic = intval(request('need_origin_pic', 0)); //是否需要保留原图

        $water_picture = public_path('/assets/sys/watermark.png');    //水印图
        $water_picture_exists = File::exists($water_picture) ? true : false;
        $arr_thumb_max_width = $arr_thumb_max_height = $arr_thumb_water = $arr_thumb_appointed = array();
        $arr_thumb_max_width = explode(',', $thumb_max_width);
        $arr_thumb_max_height = explode(',', $thumb_max_height);
        $arr_thumb_water = explode(',', $thumb_water);
        $arr_thumb_appointed = explode(',', $thumb_appointed);
        return [
            'arr_thumb_appointed' => $arr_thumb_appointed,
            'arr_thumb_max_width' => $arr_thumb_max_width,
            'arr_thumb_max_height' => $arr_thumb_max_height,
            'arr_thumb_water' => $arr_thumb_water,
            'water_picture_exists' => $water_picture_exists,
            'water_picture' => $water_picture,
            'ext_type' => $ext_type,
            'need_origin_pic' => $need_origin_pic,
        ];
    }

    /**
     * 复文件处理获取
     * @param $request
     * @param string $file_field 表单中上传字段
     * @return array
     */
    protected function dealFiles($request, $file_field)
    {
        $arr_files = [];
        if (is_array($request->file($file_field))) {
            foreach ($request->file($file_field) as $file) {
                $arr_files[] = $file;
            }
        } else {
            $arr_files[] = $request->file($file_field);
        }
        return $arr_files;
    }

    /**
     * 编辑器上传图片
     * @return \Illuminate\Http\JsonResponse
     */
    protected function actionUploadImage()
    {
        $arr_result = $this->uploadFile('upload_files');
        $ue_result = array(
            'state' => '',  //状态
            'url' => '',    //地址
            'title' => '',  //生成的文件名
            'original' => '',   //原文件名
        );
        if (empty($arr_result[0]['err']) && !empty($arr_result[0]['link_url'])) {
            $ue_result['state'] = 'SUCCESS';
            $ue_result['url'] = $arr_result[0]['link_url'];
            $ue_result['title'] = $arr_result[0]['file_origin_name'];
            $ue_result['original'] = $arr_result[0]['file_origin_name'];
        } else {
            $ue_result['state'] = !empty($arr_result[0]['err']) ? $arr_result[0]['err'] : '上传有误';
        }
        return response()->json($ue_result);
    }

    /**
     * 编辑器上传文件
     * @return \Illuminate\Http\JsonResponse
     */
    protected function actionUploadFile()
    {
        $js_config = $this->getJsonConfig();
        $upload_field_name = $js_config[''];
        $arr_result = $this->uploadFile('upload_files');
        $ue_result = array(
            'state' => '',  //状态
            'url' => '',    //地址
            'title' => '',  //生成的文件名
            'original' => '',   //原文件名
        );
        if (empty($arr_result[0]['err']) && !empty($arr_result[0]['link_url'])) {
            $ue_result['state'] = 'SUCCESS';
            $ue_result['url'] = $arr_result[0]['link_url'];
            $ue_result['title'] = $arr_result[0]['file_origin_name'];
            $ue_result['original'] = $arr_result[0]['file_origin_name'];
        } else {
            $ue_result['state'] = !empty($arr_result[0]['err']) ? $arr_result[0]['err'] : '上传有误';
        }
        return response()->json($ue_result);
    }

    /**
     * 编辑器专区远程图片到本地
     * @return \Illuminate\Http\JsonResponse
     */
    protected function actionCatchImage()
    {
        $arr_list = array();
        $arr_return = array(
            'state' => 'SUCCESS',
            'list' => $arr_list,
        );
        $cfg = $this->getJsonConfig();
        $sources = request($cfg['catcherFieldName'], array());
        if ($sources) {
            $uploader = app()->make('zhangmazi.ueditor.uploader');
            $params = $this->getUploaderParams();
            $arr_exts = $uploader->getAllowExtsByType($params['ext_type']);
            $uploader->maxSize = 0; //不限制
            $uploader->allowExts = $arr_exts;
            $uploader->isSaveOriginFile = false;
            $uploader->allowExts = $arr_exts;
            $uploader->thumb = true;
            $uploader->thumbMaxWidth = '';
            $uploader->thumbMaxHeight = '';
            $uploader->thumbAppointed = '';
            $uploader->thumbWater = '';
            $uploader->waterType = '';
            $uploader->waterPicture = '';
            $uploader->waterPosition = 9;
            foreach ($sources as $k => $url) {
                $arr_head = $this->getRemoteFileInfo($url, 'all');
                if ($arr_head) {    //已经存在
                    $file = File::get($url);
                    //$storage_driver = $this->getStorage()->getDriver();
                    $storage_config = $this->getStorageConfig();
                    $disk_name = $this->getStorageDiskName();
                    $storage_driver = $storage_config['disks'][$disk_name]['driver'];
                    $save_root_path = $this->getSaveRootPath($storage_driver);
                    $relative_dir = $this->getRelativeDir();
                    $visibility = !empty($storage_config[$disk_name]['visibility']) ?
                        $storage_config[$disk_name]['visibility'] : null;
                    $url_root = !empty($storage_config[$disk_name]['url_root']) ?
                        trim($storage_config[$disk_name]['url_root'], '/') : '';

                    $res = $uploader->uploadFile($file, $save_root_path, $relative_dir);
                    if (!empty($res[0][0])) {
                        if ($this->getStorage()->put(
                            $res[0][0]['file_path'],
                            File::get($res[0][0]['file_native_path']),
                            $visibility
                        )) {
                            $res[0][0]['link_url'] = $url_root . '/' . $res[0][0]['file_path'];
                            $arr_return[] = [
                                'state' => 'SUCCESS',
                                'url' => $res[0][0]['link_url'],
                                'size' => $res[0][0]['file_size'],
                                'title' => htmlspecialchars($res[0][0]['file_origin_name']),
                                'original' => htmlspecialchars($res[0][0]['file_origin_name']),
                                'source' => htmlspecialchars($url),
                            ];
                        }
                    } elseif (!empty($res['err'])) {
                        $arr_return[] = ['state' => $res['err']];
                    }
                }
            }
        }
        return response()->json($arr_return);
    }

    /**
     * 编辑器获取图库清单
     * @param int $type
     * @return \Illuminate\Http\JsonResponse
     */
    protected function actionListImage($type = 1)
    {
        $new_start = 0;
        $page = 1;
        $arr_list = []; // ['url', 'mtime']
        $arr_return = array(
            'state' => 'SUCCESS',
            'list' => array(),
            'start' => 0,
            'total' => 0,
        );

        $cfg = $this->getJsonConfig();
        $page_size = $cfg['imageManagerListSize'] ? $cfg['imageManagerListSize'] : 20;
        $size = (int)request('size', $page_size);
        if ($size < 0) {
            $size = $page_size;
        }
        $start = (int)request('start', 0);
        if ($start < 0) {
            $start = 0;
        }

        $page = ceil($start / $size) + 1;
        if ($page < 1) {
            $page = 1;
        }
        $arr_return['list'] = $arr_list;
        $arr_return['page'] = $page;
        $arr_return['page_size'] = $page_size;

        $arr_return['total'] = 0;
        $arr_return['start'] = $start;
        return response()->json($arr_return);
    }

    /**
     * 编辑器所需要的json前端配置
     * @return array
     */
    protected function getJsonConfig()
    {
        $page_size = config('zhangmazi.ueditor.imageManagerListSize', 20);
        $upload_max_size = config('zhangmazi.ueditor.upload_max_size_by_k', 20480) * 1024;
        $upload_field_name = config('zhangmazi.ueditor.upload_field_name', 'upload_files');
        $arr_config = array(
            //上传图片配置项
            'imageActionName' => 'UploadImage', //执行上传图片的action名称
            'imageFieldName'  => $upload_field_name,  //提交的图片表单名称
            'imageMaxSize' => $upload_max_size,    //上传大小限制，单位B
            'imageAllowFiles' => array('.jpg', '.jpeg', '.png', '.gif', '.bmp'), //上传图片格式显示
            'imageCompressEnable' => true, //是否压缩图片,默认是true
            'imageCompressBorder' => 3600, //图片压缩最长边限制
            'imageInsertAlign' => 'none',    //插入的图片浮动方式
            'imageUrlPrefix' => '',  //图片访问路径前缀
            'imagePathFormat' => '', //上传保存路径,可以自定义保存路径和文件名格式

            //涂鸦图片上传配置项
            'scrawlActionName' => 'UploadScrawl',    //执行上传涂鸦的action名称
            'scrawlFieldName'  => $upload_field_name, //提交的图片表单名称
            'scrawlPathFormat' => '',    //上传保存路径,可以自定义保存路径和文件名格式
            'scrawlMaxSize' => $upload_max_size,   //上传大小限制，单位B
            'scrawlUrlPrefix' => '',    //图片访问路径前缀
            'scrawlInsertAlign' => 'none',   //插入的图片浮动方式

            //截图工具上传
            'snapscreenActionName' => 'UploadSnapScreen',    //执行上传截图的action名称
            'snapscreenPathFormat' => '',    //上传保存路径,可以自定义保存路径和文件名格式
            'snapscreenUrlPrefix' => '', //图片访问路径前缀
            'snapscreenInsertAlign' => 'none',   //插入的图片浮动方式

            //抓取远程图片配置
            'catcherLocalDomain' => array(
                '127.0.0.1',
                'localhost',
                'static.oschina.net',
                '127.net',
                'cms-bucket.nosdn.127.net',
                'blog.ninja911.com'
            ),
            'catcherActionName' => 'CatchImage',    //执行抓取远程图片的action名称
            'catcherFieldName'  => $upload_field_name,    //提交的图片列表表单名称
            'catcherPathFormat' => '',   //上传保存路径,可以自定义保存路径和文件名格式
            'catcherUrlPrefix' => '',    //图片访问路径前缀
            'catcherMaxSize' => $upload_max_size,  //上传大小限制，单位B
            'catcherAllowFiles' => array('.jpg', '.jpeg', '.png', '.gif', '.bmp'), //上传图片格式显示

            //上传视频配置
            'videoActionName' => 'UploadVideo', //执行上传视频的action名称
            'videoFieldName'  => $upload_field_name,  //提交的视频表单名称
            'videoPathFormat' => '', //上传保存路径,可以自定义保存路径和文件名格式
            'videoUrlPrefix' => '',  //视频访问路径前缀
            'videoMaxSize' => $upload_max_size,    //上传大小限制，单位B，默认100MB
            'videoAllowFiles' => array('.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
                '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid'), //上传视频格式显示

            //上传文件配置
            'fileActionName' => 'UploadFile',  //controller里,执行上传视频的action名称
            'fileFieldName'  => $upload_field_name,  //提交的文件表单名称
            'filePathFormat' => '',  //上传保存路径,可以自定义保存路径和文件名格式
            'fileUrlPrefix' => '',   //文件访问路径前缀
            'fileMaxSize' => $upload_max_size, //上传大小限制，单位B，默认50MB
            //上传文件格式显示
            'fileAllowFiles' => array(
                '.jpg', '.jpeg', '.png', '.gif', '.bmp',
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
                '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.doc', '.docx', '.xls', '.xlsx',
                '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ),

            //列出指定目录下的图片
            'imageManagerActionName' => 'ListImage',  //执行图片管理的action名称
            'imageManagerListPath' => '',    //指定要列出图片的目录
            'imageManagerListSize' => $page_size,    //每次列出文件数量
            'imageManagerUrlPrefix' => '',   //图片访问路径前缀
            'imageManagerInsertAlign' => 'none', //插入的图片浮动方式
            'imageManagerAllowFiles' => array('.gif', '.jpg', '.jpeg', '.png', '.bmp'), //列出的文件类型

            //列出指定目录下的文件
            'fileManagerActionName' => 'ListFile',   //执行文件管理的action名称
            'fileManagerListPath' => '', //指定要列出文件的目录
            'fileManagerUrlPrefix' => '',    //文件访问路径前缀
            'fileManagerListSize' => $page_size, //每次列出文件数量
            'fileManagerAllowFiles' => array(
                '.jpg', '.jpeg', '.png', '.gif', '.bmp',
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
                '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.doc', '.docx', '.xls', '.xlsx',
                '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ),
        );
        return $arr_config;
    }

    /**
     * 验证是否合法
     * @return mixed
     */
    protected function checkGuard()
    {
        return true;
    }

    /**
     * 获取远程文件的header信息
     * @param string $url url地址
     * @param string $return_type
     * @return array|bool|string
     */
    protected function getRemoteFileInfo($url, $return_type = 'exists')
    {
        $head = @get_headers($url, true);
        $request_status = false;
        if (is_array($head)) {
            $t = explode(" ", $head[0]);
            if (strtoupper($t[count($t)-1]) == "OK") {
                $request_status = true;
            }
        }
        if ($request_status) {
            if ($return_type == 'exists') {
                return  $request_status;
            } elseif ($return_type == 'size') {
                return trim($head['Content-Length']); //单位bytes 字节
            } elseif ($return_type == 'type') {
                return trim($head['Content-Type']);
            } else {
                return $head;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取保存相对路径
     * @return string
     */
    protected function getRelativeDir()
    {
        return 'attachment';
    }

    /**
     * 获取保存根目录路径
     * @paraam string $driver_name 驱动名
     * @return string
     */
    protected function getSaveRootPath($driver_name = 'local')
    {
        $disk_name = $this->getStorageDiskName();
        $storage_config = $this->getStorageConfig();
        return $driver_name != 'local' ? storage_path('app/ueditor/tmp') :
                $storage_config['disks'][$disk_name]['root'];
    }

    /**
     * 删除原始图片
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
