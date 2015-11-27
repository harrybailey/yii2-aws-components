<?php

namespace jarrus90\AwsComponents\Services;

use vova07\imperavi\Widget;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use Yii;

/**
 * Class UploadAction
 * @package vova07\imperavi\actions
 *
 * UploadAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-image' => [
 *             'class' => 'vova07\imperavi\actions\UploadAction',
 *             'url' => 'http://my-site.com/statics/',
 *             'path' => '/var/www/my-site.com/web/statics',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'vova07\imperavi\actions\UploadAction',
 *             'url' => 'http://my-site.com/statics/',
 *             'path' => '/var/www/my-site.com/web/statics',
 *             'uploadOnlyImage' => false,
 *             'validatorOptions' => [
 *                 'maxSize' => 40000
 *             ]
 *         ]
 *     ];
 * }
 * ```
 *
 * @author Vasile Crudu <bazillio07@yandex.ru>
 *
 * @link https://github.com/vova07
 */
class S3ActionUpload extends Action {

    public $storage;

    /**
     * @var string Validator name
     */
    public $uploadOnlyImage = true;
    
    /**
     * @var string Is image name unique
     */
    public $unique = true;

    /**
     * @var string Variable's name that Imperavi Redactor sent upon image/file upload.
     */
    public $uploadParam = 'file';

    /**
     * @var array Model validator options
     */
    public $validatorOptions = [];
	
    /**
     * @var array Action additional options
     */
    public $options = [];

    /**
     * @var string Model validator name
     */
    private $_validator = 'image';

    /**
     * @inheritdoc
     */
    public function init() {
        if ($this->storage === null) {
            throw new InvalidConfigException('The "storage" attribute must be set.');
        }
        if ($this->uploadOnlyImage !== true) {
            $this->_validator = 'file';
        }
        Widget::registerTranslations();
    }

    /**
     * @inheritdoc
     */
    public function run() {
        if (Yii::$app->request->isPost) {
            $file = UploadedFile::getInstanceByName($this->uploadParam);
            $model = new DynamicModel(compact('file'));
            $model->addRule('file', $this->_validator, $this->validatorOptions)->validate();
            if ($model->hasErrors()) {
                $result = [
                    'error' => $model->getFirstError('file')
                ];
            } else {
                if ($this->unique === true && $model->file->extension) {
                    $md5Filename = md5($model->file->name . '_' . rand(100000, 999999) . '_' . time());
                    $filename = $md5Filename[0] . '/' . $md5Filename[1] . '/' . $md5Filename[2] . '/' . $md5Filename . '.' . $model->file->extension;
                    $model->file->name = $filename;
                }
                $uploadResult = $this->storage->uploadFile($model->file->tempName, $model->file->name);
                if ($uploadResult) {
                    if(isset($this->options['baseUrl'])){
                        $uploadResult = Yii::getAlias($this->options['baseUrl'] . $filename);
                    }
                    $result = ['filelink' => $uploadResult];
                    if ($this->uploadOnlyImage !== true) {
                        $result['filename'] = $model->file->name;
                    }
                } else {
                    $result = [
                        'error' => Yii::t('vova07/imperavi', 'ERROR_CAN_NOT_UPLOAD_FILE')
                    ];
                }
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        } else {
            throw new BadRequestHttpException('Only POST is allowed');
        }
    }

}
