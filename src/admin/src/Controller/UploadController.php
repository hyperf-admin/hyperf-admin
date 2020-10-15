<?php
namespace HyperfAdmin\Admin\Controller;

use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Log;
use HyperfAdmin\BaseUtils\Scaffold\Controller\Controller;

class UploadController extends Controller
{
    public function image()
    {
        $bucket = $this->request->input('bucket', 'local');
        $private = $this->request->input('private', false);

        $file = $this->request->file('file');
        if (!$file->isValid()) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM);
        }

        $tmp_file = $file->toArray()['tmp_file'];
        $md5_filename = md5_file($tmp_file);
        $path = '1/' . date('Ym') . '/' . $md5_filename . '.' . $file->getExtension();

        try {
            $uploaded = move_local_file_to_filesystem($tmp_file, $path, $private, $bucket);
            if ($uploaded === false) {
                return $this->fail(ErrorCode::CODE_ERR_SERVER, '上传失败');
            }
            [$width, $height] = getimagesize($tmp_file);
            $info = [
                'path' => $uploaded['path'],
                'url' => $uploaded['file_path'],
                'key' => 'file',
                'size' => $file->toArray()['size'],
                'width' => $width,
                'height' => $height,
            ];

            return $this->success($info);
        } catch (\Exception $e) {
            Log::get('upload')->error($e->getMessage());

            return $this->fail(ErrorCode::CODE_ERR_SERVER, $e->getMessage());
        }
    }

    public function privateFileUrl()
    {
        $oss_path = $this->request->input('key');
        $bucket = $this->request->input('storage', config('file.default'));

        if (!$oss_path) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM);
        }
        $private_url = filesystem_private_url($oss_path, MINUTE * 5, $bucket);
        if (!$private_url) {
            return $this->fail(ErrorCode::CODE_ERR_SYSTEM);
        }

        return $this->response->redirect($private_url);
    }
}
