<?php

namespace jarrus90\AwsComponents\Services;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Class GetAction
 * @package vova07\imperavi\actions
 *
 * GetAction returns a JSON array of the files found under the specified directory and subdirectories.
 * This array can be used in Imperavi Redactor to insert some files that have already been uploaded.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'get-image' => [
 *             'class' => S3ActionGet::className(),
 *             'url' => 'http://my-site.com/statics/',
 *             'storage' => $myBucket -- instance of s3 component
 *         ]
 *     ];
 * }
 * ```
 */
class S3ActionGet extends Action {

    /** Image type */
    const TYPE_IMAGES = 0;

    /** File type */
    const TYPE_FILES = 1;

    /**
     * [\jarrus90\AwsComponents\Services\S3] Bucket instance
     * @var S3
     */
    public $storage;
    public $type = self::TYPE_IMAGES;

    /**
     * @inheritdoc
     */
    public function init() {
        if ($this->storage === null) {
            throw new InvalidConfigException('The "storage" attribute must be set.');
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $objects = $this->storage->listFiles();
        $array = [];
        foreach ($objects as $object) {
            if (substr($object['Key'], -1) != '/') {
                $url = $this->storage->getObjectUrl(false, $object['Key']);
                if ($this->type === self::TYPE_IMAGES) {
                    $array[] = [
                        'image' => $url,
                        'thumb' => $url,
                        'title' => $object['Key'],
                    ];
                } elseif ($this->type === self::TYPE_FILES) {
                    $list[] = [
                        'title' => $object['Key'],
                        'name' => $object['Key'],
                        'link' => $url,
                        'size' => self::formatFileSize($object['Size'])
                    ];
                } else {
                    $list[] = $url;
                }
            }
        }
        return $array;
    }

    /**
     * @param string $path
     *
     * @return string filesize in(B|KB|MB|GB)
     */
    protected static function formatFileSize($size) {
        $labels = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($size) - 1) / 3);

        return sprintf("%.1f ", $size / pow(1024, $factor)) . $labels[$factor];
    }

}
