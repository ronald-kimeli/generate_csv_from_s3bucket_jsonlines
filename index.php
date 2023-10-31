<?php
require_once 'src/createCsv_ZeroSplit.class.php';
require_once "src/AWS_Bucket.php";
require_once 'src/functions.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$key = $_ENV['AWS_ACCESS_KEY'];
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'];
$region = $_ENV['AWS_DEFAULT_REGION'];
$version = $_ENV['AWS_VERSION'];
$Bucket = $_ENV['AWS_BUCKET'];
$endpoint = $_ENV['AWS_S3_ENDPOINT'];

$DB_HOST = $_ENV['DB_HOST'];
$DB_DATABASE = $_ENV['DB_DATABASE'];
$DB_USERNAME = $_ENV['DB_USERNAME'];
$DB_PASSWORD = $_ENV['DB_PASSWORD'];

$con = mysqli_connect($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE);
$rows =  mysqli_query($con, 'SELECT * FROM funded_club');

$path = AWS_Bucket::recent_object($key, $secret, $region, $version, $Bucket, $endpoint);

Csv_ZeroSplit::createCSV($rows, $path);
