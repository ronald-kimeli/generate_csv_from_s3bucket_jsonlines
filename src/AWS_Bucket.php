<?php
require_once "vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AWS_Bucket
{
    /**
     * @return mixed|void
     */
    public static function recent_object($key, $secret, $region, $version, $Bucket, $endpoint)
    {
        try {

            //Create an S3Client| fundedclub
            $s3Client = new S3Client([
                'credentials' => [
                    "key" => $key,
                    "secret" => $secret,
                ],
                'region' => $region,
                'version' => $version,
                'Bucket' => $Bucket,
            ]);
            $contents = $s3Client->listObjects([
                'Bucket' => $Bucket,
            ]);
            $mostRecent = 0;
            $mostRecentObjectURL = null;
            //loop jobs
            //jobs=index 1 while companies=index 0
            $jobs = $contents['Contents'];
            // var_dump($jobs);exit;
            function filterArray($needle, $haystack)
            {
                foreach ($haystack as $v) {
                    if (stripos($v, $needle) !== false) return true;
                };
                return false;
            }

            $items = array_filter($jobs, function ($v) {
                return filterArray('jobs', $v);
            });

            foreach ($items as $item) {
                $date = $item['LastModified']->format(DateTimeInterface::ISO8601);
                $curDate = strtotime($date);
                //check unix timestamps
                if ($curDate > $mostRecent) {
                    $mostRecent = $curDate;
                    //check if it has .zst extension
                    $file_parts = pathinfo($item['Key']);
                    if ($file_parts['extension'] === 'zst') {
                        $mostRecentObjectURL = $item['Key'];
                    }
                }
            }
            //download file locally
            $path = "$endpoint/$mostRecentObjectURL";

            //Manually
            //$path="https://fundedclub.s3.eu-central-1.amazonaws.com/2022/11/25/jobs_2022_11_25.jsonl.zst";

            //check
            $zip = basename($path);
            if (!file_exists(basename($path))) {
                //bash scripts
                exec("wget $path");
                //decompress the file
                exec("unzstd $zip");
            }
            // unlink($zip);
            return pathinfo($zip, PATHINFO_FILENAME);
        } catch (AwsException $e) {
            echo $e->getMessage();
        }
    }
}