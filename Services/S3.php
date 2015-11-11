<?php

/**
 * @author: Jovani F. Alferez <vojalf@gmail.com>
 */

namespace jarrus90\AwsComponents\Services;

use yii\base\Component;
use Aws\S3\S3Client;
use yii\helpers\FileHelper;
use Aws\Credentials\Credentials;
/**
 * A Yii2-compatible component wrapper for Aws\S3\S3Client.
 * Just add this component to your configuration providing this class,
 * key, secret and bucket.
 *
 * ~~~
 * 'components' => [
 *     'storage' => [
 *          'class' => '\jovanialferez\yii2s3\AmazonS3',
 *          'key' => 'AWS_ACCESS_KEY_ID',
 *          'secret' => 'AWS_SECRET_ACCESS_KEY',
 *          'bucket' => 'YOUR_BUCKET',
 *          'prefix' => 'YOUR_PREFIX', (any, if you wish)
 *     ],
 * ],
 * ~~~
 *
 * You can then start using this component as:
 *
 * ```php
 * $storage = \Yii::$app->storage;
 * $url = $storage->uploadFile('/path/to/file', 'unique_file_name');
 * ```
 */
class S3 extends Component {

    public $bucket;
    public $key;
    public $secret;
    public $region;
    public $prefix = '';
    private $_client;

    public function init() {
        parent::init();
        $credentials = new Credentials($this->key, $this->secret);
        $this->_client = S3Client::factory([
                    'region' => $this->region,
                    'scheme' => 'http',
                    'version' => '2006-03-01',
                    'credentials' => $credentials
        ]);
    }

    /**
     * Uploads the file into S3 in that bucket.
     *
     * @param string $filePath Full path of the file. Can be from tmp file path.
     * @param string $fileName Filename to save this file into S3. May include directories.
     * @param bool $bucket Override configured bucket.
     * @return bool|string The S3 generated url that is publicly-accessible.
     */
    public function uploadFile($filePath, $fileName, $bucket = false) {
        if (!$bucket) {
            $bucket = $this->bucket;
        }
        try {
            $result = $this->_client->putObject([
                'ACL' => 'public-read',
                'Bucket' => $bucket,
                'Key' => $this->prefix . $fileName,
                'SourceFile' => $filePath,
                'ContentType' => FileHelper::getMimeType($filePath),
            ]);
            return $result->get('ObjectURL');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Removes the file from S3 in that bucket.
     *
     * @param string $fileName Filename to save this file into S3. May include directories.
     * @param bool $bucket Override configured bucket.
     * @return bool|string The S3 generated url that is publicly-accessible.
     */
    public function removeFile($fileName, $bucket = false) {
        if (!$bucket) {
            $bucket = $this->bucket;
        }
        try {
            $result = $this->_client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $this->prefix . $fileName,
            ]);
            return $result->get('DeleteMarker');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listFiles($prefix = false, $bucket = false) {
        if (!$bucket) {
            $bucket = $this->bucket;
        }
        if (!$prefix) {
            $prefix = $this->prefix;
        }
        try {
            $result = $this->_client->getIterator('ListObjects', [
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ]);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getObjectUrl($bucket = false, $key = ''){
        if (!$bucket) {
            $bucket = $this->bucket;
        }
        return $this->_client->getObjectUrl($bucket, $key);
    }
}
