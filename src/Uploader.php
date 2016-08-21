<?php
/**
 * 描述.
 *
 * @author ninja911<ninja911@qq.com>
 * @date   2016-08-21 00:42
 */

namespace Zhangmazi\Ueditor;

use Illuminate\Support\Facades\File;

class Uploader
{
    private $ext2mime = [];
    public $maxSize = 0;
    public $allowExts = [];
    public $allowMimes = [];
    public $isSaveOriginFile = false;
    public $imageHelper = null; //图形处理助手,推荐使用Intervention\Image第三方包
    public $thumb = false;      //是否缩略图
    public $thumbMaxWidth = array();    //缩略图最大宽度
    public $thumbMaxHeight = array();   //缩略图最大高度
    public $thumbAppointed = array();  //是否固定宽高大小
    public $thumbWater = array();       //是否水印
    public $waterType = '';      //水印类型
    public $waterPicture = '';          //水印图片路径
    public $waterPosition = 9;          //水印位置

    /**
     * 执行上传文件
     * @param mixed  $file           文件对象
     * @param string $save_root_path 保存资源的物理根目录,一般为public('')
     * @param string $relative_dir   相对目录路径
     * @return array|bool
     */
    public function uploadFile($file, $save_root_path = '', $relative_dir = '')
    {
        $is_pic = false;
        $file_origin_ext = $file->getClientOriginalExtension();
        $file_ext = strtolower($file_origin_ext);
        $file_mime = $file->getMimeType();
        $file_origin_name = $file->getClientOriginalName();
        $save_file_name = $this->makeFileTime() . '.' . $file_ext;
        $save_origin_file_name = 'original_' . $save_file_name;
        $file_origin_size = $file->getSize();

        $check_result = $this->check($file_ext, $file_origin_size, $file_mime);
        if ($check_result !== true) {
            return $check_result;
        }
        $save_tmp_dir = $this->initSaveLocalDir($save_root_path, $relative_dir);
        //将文件从\tmp目录移动到本地物理路径
        $file->move($save_tmp_dir, $save_origin_file_name);
        //本地临时原始文件路径
        $tmp_file_path = $save_tmp_dir . '/' . $save_origin_file_name;

        $is_pic = in_array($file_ext, $this->pictureExts());

        $lists = [];
        if ($is_pic) {
            $images_info = getimagesize($tmp_file_path);    //无需依赖GD库
            if (false !== $images_info) {   //有效的图片
                //如果有图片助手
                if ($this->imageHelper) {
                    $count = count($this->thumbMaxWidth);
                    for ($i = 0; $i < $count; $i++) {
                        $img = null;
                        $file_thumb_name = 't_' . substr($save_file_name, 0, strrpos($save_file_name, '.')) .
                            '_'. $i .'.' . $file_ext;
                        $local_thumb_path = $save_tmp_dir . '/' . $file_thumb_name;
                        File::copy($save_origin_file_name, $local_thumb_path);
                        if (isset($this->thumbMaxWidth[$i]) && isset($this->thumbMaxHeight[$i]) &&
                            $this->thumbMaxWidth[$i] > 0 && $this->thumbMaxHeight[$i]) {
                            //对是否是固定尺寸进行处理
                            $thumb_appointed = (bool)$this->thumbAppointed[$i];
                            $img = $this->imageHelper->make($local_thumb_path)->resize(
                                $this->thumbMaxWidth[$i],
                                $this->thumbMaxHeight[$i],
                                function ($constraint) use ($thumb_appointed) {
                                    if ($thumb_appointed) {
                                        $constraint->upsize();
                                    } else {
                                        $constraint->aspectRatio();
                                    }
                                }
                            );
                        } else {
                            $img = $this->imageHelper->make($local_thumb_path);
                        }

                        //水印处理
                        if ($this->thumbWater[$i] && $this->waterType == 'picture' && !empty($this->waterPicture)) {
                            $native_thumb_image = getimagesize($tmp_file_path);
                            $img->insert($this->waterPicture, 0, 0, $this->getWaterPosition($this->waterPosition));
                        }
                        $img->save();
                        $img->destroy();    //主动释放助手资源

                        $thumb_native_size = File::size($local_thumb_path);
                        $thumb_native_image = getimagesize($local_thumb_path);
                        $thumb_native_width = $thumb_native_image[0];
                        $thumb_native_height = $thumb_native_image[1];

                        $item_info = [
                            'file_origin_name' => $file_origin_name,
                            'file_name' => $file_thumb_name,
                            'file_size' => $thumb_native_size,
                            'file_path' => $relative_dir . '/' . $local_thumb_path, //相对于域名跟目录
                            'file_ext' => $file_ext,
                            'file_mime' => $file_mime,
                            'width' => $thumb_native_width,
                            'height' => $thumb_native_height,
                            'file_native_path' => $local_thumb_path,
                            'is_pic' => $is_pic,
                            'origin_pic_relative_path' => '',
                            'origin_pic_native_path' => '',
                            'origin_pic_size' => 0,
                            'origin_pic_name' => '',
                            'origin_pic_width' => 0,
                            'origin_pic_height' => 0,
                        ];
                        if ($this->isSaveOriginFile) {
                            $item_info['origin_pic_relative_path'] = $relative_dir . '/' . $save_origin_file_name;
                            $item_info['origin_pic_native_path'] = $tmp_file_path;
                            $item_info['origin_pic_size'] = $file_origin_size;
                            $item_info['origin_pic_name'] = $file_origin_name;
                            $item_info['origin_pic_width'] = $images_info[0];
                            $item_info['origin_pic_height'] = $images_info[1];
                        }
                        $lists[] = $item_info;
                    }
                    if (!$this->isSaveOriginFile) {
                        File::delete($tmp_file_path);
                    }
                } else {
                    //没有图片处理助手
                    $lists[] = [
                        'file_origin_name' => $file_origin_name,
                        'file_name' => $save_file_name,
                        'file_size' => $file_origin_size,
                        'file_path' => $relative_dir . '/' . $save_file_name, //相对于域名跟目录
                        'file_ext' => $file_ext,
                        'file_mime' => $file_mime,
                        'width' => $images_info[0],
                        'height' => $images_info[0],
                        'file_native_path' => $tmp_file_path,
                        'is_pic' => $is_pic,
                        'origin_pic_relative_path' => $relative_dir . '/' . $save_file_name,
                        'origin_pic_native_path' => $tmp_file_path,
                        'origin_pic_size' => $file_origin_size,
                        'origin_pic_name' => $file_origin_name,
                        'origin_pic_width' => $images_info[0],
                        'origin_pic_height' => $images_info[1],
                    ];
                }
            }
        } else {
            //非图片类的处理流程
            $lists[] = [
                'file_origin_name' => $file_origin_name,
                'file_name' => $save_file_name,
                'file_size' => $file_origin_size,
                'file_path' => $relative_dir . '/' . $save_file_name, //相对于域名跟目录
                'file_ext' => $file_ext,
                'file_mime' => $file_mime,
                'width' => 0,
                'height' => 0,
                'file_native_path' => $tmp_file_path,
                'is_pic' => $is_pic,
                'origin_pic_relative_path' => '',
                'origin_pic_native_path' => '',
                'origin_pic_size' => 0,
                'origin_pic_name' => '',
                'origin_pic_width' => 0,
                'origin_pic_height' => 0,
            ];
        }
        return $lists;
    }

    /**
     * 获取文件的扩展名，不包含.
     * @param string $filename  文件名
     * @return string
     */
    public function fileExt($filename)
    {
        return strtolower(trim(substr(strrchr($filename, '.'), 1)));
    }

    /**
     * 产生以时间随机的文件名
     * @return string
     */
    public function makeFileTime()
    {
        return date("YmdHis") . '_' . str_random(6);
    }

    /**
     * 检查MIME
     * @param string $mime MIME
     * @return bool
     */
    public function checkMime($mime)
    {
        if (!$this->allowMimes) {
            // 如果没有配置, 为了安全,则使用内置默认的mime校验
            $this->allowMimes = $this->getDefaultMimeTypes();
        }
        return in_array($mime, $this->allowMimes);
    }

    /**
     * 检查扩展名
     * @param string $ext 扩展名
     * @return bool
     */
    public function checkExtension($ext)
    {
        if (!$this->allowExts) {
            // 如果没有配置,为了安全,则使用图片类扩展名校验
            $this->allowExts = $this->getAllowExtsByType(1);
        }
        return in_array($ext, $this->allowExts);
    }

    /**
     * 检查文件大小
     * @param int $size 文件大小
     * @return bool
     */
    public function checkSize($size)
    {
        if ($this->maxSize > 0) {
            return !($size > $this->maxSize);
        } else {
            return true;
        }
    }

    /**
     * 综合检查
     * @param string $file_ext  扩展名
     * @param int    $file_size 大小
     * @param string $file_mime MIME
     * @return array|bool
     */
    public function check($file_ext, $file_size, $file_mime)
    {
        if (!$this->checkExtension($file_ext)) {
            return $this->error('不允许上传['. $file_ext .']扩展的文件');
        }
        if (!$this->checkSize($file_size)) {
            return $this->error('文件太大了');
        }
        if (!$this->checkMime($file_mime)) {
            return $this->error('不合法的文件头');
        }
        return true;
    }

    /**
     * 初始化本地临时保存目录
     * @return string
     */
    public function initSaveLocalDir($save_root_path, $relative_dir)
    {
        $path = empty($save_root_path) ? storage_path('app/public/ueditor/tmp') : $save_root_path;
        if (empty($relative_dir)) {
            $path .= '/'. $relative_dir;
        }
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 755, true);
        }
        return $path;
    }

    /**
     * 图片类扩展名
     * @return array
     */
    protected function pictureExts()
    {
        return ['jpg', 'jpeg', 'gif', 'bmp', 'png'];
    }

    public function imageThumb($img_local_path, $tmp_dir)
    {
        $count_thumb = count($this->thumbMaxWidth);
    }

    /**
     * 处理图片水印
     * @param mixed $img
     * @param array $water_config  水印配置,比如位置,字体,颜色等
     * @return mixed
     */
    protected function imageWater($img, $water_config)
    {
        return $img;
    }

    /**
     * 错误信息
     * @param $msg
     * @return array
     */
    protected function error($msg)
    {
        return ['err' => $msg];
    }

    /**
     * 获取转换源位置
     * @return array
     */
    protected function getWaterPosition($p)
    {
        $arr = [
            '1' => 'top-left',
            '2' => 'top',
            '3' => 'top-right',
            '4' => 'left',
            '5' => 'center',
            '6' => 'right',
            '7' => 'bottom-left',
            '8' => 'bottom',
            '9' => 'bottom-right'
        ];
        return !empty($arr[$p]) ? $arr[$p] : 'bottom-right';
    }

    /**
     * 获取允许的扩展名
     * @param int $ext_type       扩展类型
     * @return array
     */
    public function getAllowExtsByType($ext_type)
    {
        $arr_exts = array(
            'picture' => array('jpg', 'gif', 'png', 'jpeg', 'bmp'),
            'compress' => array('zip', 'rar', '7z', 'tar', 'bz2', 'gz', 'apk'),
            'office' => array('docx', 'xlsx', 'pptx', 'doc', 'xls', 'pdf',
                'ppt', 'pps', 'wps', 'et', 'dps', 'xml', 'kml'),    //办公
            'flash' => array('swf', 'flv', 'as', 'fla'),    //FLASH
            'media' => array("ra","rm","rmvb","avi","mpeg","mid","wma","wav","mp3","wmv","mov", 'mp4', 'lrc'),    //多媒体
        );
        $return_ext = array();
        switch ($ext_type) {
            case 1:
                $return_ext = $arr_exts['picture'];
                break;
            case 2:
                $return_ext = array_merge($arr_exts['compress'], $arr_exts['office']);
                break;
            case 3:
                $return_ext = $arr_exts['flash'];
                break;
            case 4:
                $return_ext = $arr_exts['media'];
                break;
            case 5:
                $return_ext = array_merge($arr_exts['picture'], $arr_exts['flash']);
                break;
            case 6:
                $return_ext = array_merge($arr_exts['flash'], $arr_exts['media']);
                break;
            case 100:
                foreach ($arr_exts as $k => $v) {
                    $return_ext = array_merge($v, $return_ext);
                }
                break;
            default:
                break;
        }
        return $return_ext;
    }

    /**
     * 获取默认有效合法的MIME类型
     * @return mixed
     */
    public function getDefaultMimeTypes()
    {
        if (!$this->ext2mime) {
            $this->ext2mime = config('zhangmazi.ext2mime', []);
        }
        return !empty($this->ext2mime) ? $this->ext2mime : ['jpg' => 'image/jpeg'];
    }
}
