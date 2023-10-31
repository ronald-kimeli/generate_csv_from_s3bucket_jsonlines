<?php

use CurrencyDetector\Detector;
use Rs\JsonLines\JsonLines;

require_once 'vendor/autoload.php';

class Csv_ZeroSplit
{

    public $rows;
    public $path;

    public static function createCSV($rows, $path)
    {
        echo ("[\033[0;37m" . current_time() . "\033[1;37m] Generating..\n");
        $json_lines = (new JsonLines())->delineEachLineFromFile($path);

        //defines the folder name
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $csvFile = "$filename.csv";
        $handle = fopen($csvFile, "w");
        if ($handle === false) {
            exit("Error creating $csvFile");
        }
        // CREATE CSV HEADERS
        $headers = array(
            'Id', 'b6b2caf5-61f7-4d43-bb11-c62a40b4f70f', 'Title', 'Team_members', 'Type',
            '9cffb821-231e-4cd4-a010-4863536d3539', 'Salary_low', 'Salary_high', 'Salary_currency', 'Salary_time_unit', 'Career_Website', 'Job_Status', 'Location',
            'Category0', 'Category1', 'Job_Seniority', 'Company_Domain',
            'Company_Name', 'Location_Type', 'Description'
        );
        // APPEND HEADERS
        fputcsv($handle, $headers);

        $count = 0;
        foreach ((object)$json_lines as $json_line) {
            $count++;
            $json_line_arr = json_decode($json_line, true);
            $attributes = $json_line_arr['data'][0]['attributes'];
            $contract  = $attributes['contract_types'];
            $is_job_closed = $attributes['job_opening_closed'];

            $checkdays = strtotime('-15 days');
            $last_seen_at = strtotime($attributes['last_seen_at']);

            if ($last_seen_at >= $checkdays) {
                $id = clean_text($json_line_arr['data'][0]['id']);
                $title = clean_text($attributes['title']);
                $type = clean_text($json_line_arr['data'][0]['type']);
                $salary = clean_text($attributes['salary']);

                $salary_low = clean_text($attributes['salary_data']['salary_low']);
                $salary_high = clean_text($attributes['salary_data']['salary_high']);

                $salary_currency = clean_text($attributes['salary_data']['salary_currency']);
                $salary_time_unit = clean_text($attributes['salary_data']['salary_time_unit']);


                if (empty($salary_low)  || empty($salary_high) || empty($salary_currency || empty($salary_time_unit))) {
                    $salary_currency = (new Detector())->getCurrency($salary);


                    if (str_contains($salary, 'day') || str_contains($salary, 'daily')) {
                        $salary_time_unit = 'day';
                    }

                    if (str_contains($salary, 'hr') || str_contains($salary, 'hour')) {
                        $salary_time_unit = 'hour';
                    }

                    if (str_contains($salary, 'month')) {
                        $salary_time_unit = 'month';
                    }

                    if (str_contains($salary, 'year') || str_contains($salary, 'MX') || str_contains($salary, 'Annual') || str_contains($salary, 'annual') || str_contains($salary, 'CAD')) {
                        $salary_time_unit = 'year';
                    }

                    if (str_contains($salary, 'stipend') || str_contains($salary, 'Stipend')) {
                        $salary_time_unit = 'stipend';
                    }

                    $str = $salary;
                    $re = '/([£\$\€])*([\d,.]*)/';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER);
                    foreach ($matches as $key => $value) {
                        if (empty($value[0])) {
                            unset($matches[$key]);
                        }
                    }
                    $array = array_values($matches);

                    if (count($array) > 1) {

                        $salary_low = filter_var($array[0][0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        $salary_high = filter_var($array[1][0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                        if ($salary_low > $salary_high) {
                            $salary_high = $salary_low;
                            $salary_low = $salary_high;
                        } else {
                            $salary_low = $salary_low;
                            $salary_high = $salary_high;
                        }
                    } elseif (count($array) === 1) {
                        $salary_low = filter_var($array[0][0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        $salary_high = filter_var($array[0][0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                        if ($salary_low > $salary_high) {
                            $salary_high = $salary_low;
                            $salary_low = $salary_high;
                        } else {
                            $salary_low = $salary_low;
                            $salary_high = $salary_high;
                        }
                    } else {

                        $salary_low = filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        $salary_high = filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                        if ($salary_low > $salary_high) {
                            $salary_high = $salary_low;
                            $salary_low = $salary_high;
                        } else {
                            $salary_low = $salary_low;
                            $salary_high = $salary_high;
                        }
                    }
                } else {
                    $salary_low = $attributes['salary_data']['salary_low'];
                    $salary_high = $attributes['salary_data']['salary_high'];
                    $salary_currency = clean_text($attributes['salary_data']['salary_currency']);
                }

                $location_type = clean_text(implode(',', $contract));
                $career_website_url = clean_text($attributes['url']);
                $is_job_closed = clean_text($attributes['job_opening_closed']);
                $location = clean_text($attributes['location']);
                $category0 = clean_text(@$attributes['categories'][0]);
                $category1 = clean_text(@$attributes['categories'][1]);
                $job_seniority = clean_text($attributes['additional_data']['job_title_seniority']);
                $company_domain = clean_text($json_line_arr['included'][0]['attributes']['domain']);
                $company_name = clean_text($json_line_arr['included'][0]['attributes']['company_name']);
                $description = clean_text($attributes['description']);

                $status = '';
                if ($is_job_closed == false) {
                    $status = 'active';
                } else {
                    $status = 'closed';
                }

                // Total funding
                $totalFunding = funding($company_domain, $rows);
                $fundingtxt = '';
                if ($totalFunding > 5000000) {
                    $fundingtxt = clean_text('> 5 million $');
                } elseif ($totalFunding > 1000000 && $totalFunding <= 5000000) {
                    $fundingtxt = clean_text('1-5 million $');
                } elseif ($totalFunding < 1000000) {
                    $fundingtxt = clean_text('< 1 million $');
                } else {
                    $fundingtxt = clean_text('Bootstrapped');
                }

                //Team Members
                $teamMembers = employee_range($company_domain, $rows);
                $membernotext = '';
                if ($teamMembers === '0-10') {
                    $membernotext = clean_text('0-10');
                } elseif ($teamMembers === '11-50') {
                    $membernotext = clean_text('11-50');
                } elseif ($teamMembers === '51-100') {
                    $membernotext = clean_text('51-100');
                } elseif ($teamMembers === '101-250' || $teamMembers === '251-500') {
                    $membernotext = clean_text('101-500');
                } elseif ($teamMembers === '501-1000') {
                    $membernotext = clean_text('501-1000');
                } elseif ($teamMembers === '1001-5000') {
                    $membernotext = clean_text('1001-5000');
                } else {
                    $membernotext = clean_text('>5000');
                }
                //APPEND DATA TO CSV FILE 
                fputcsv($handle, [$id, $fundingtxt, $title, $membernotext, $type, $salary, $salary_low, $salary_high, $salary_currency, $salary_time_unit, $career_website_url, $status, $location, $category0, $category1, $job_seniority, $company_domain, $company_name, $location_type, $description]);
            }
        }

        fclose($handle);

        echo ("[\033[0;37m" . current_time() . "\033[1;37m] Csv File Generated Successfully!\n");

        echo ("[\033[0;37m" . current_time() . "\033[1;37m] Number of lines: " . $count . " \n");

        echo ("[\033[0;37m" . current_time() . "\033[1;37m] File Name:" . $csvFile . "\n");

        $zipped_file = exec("zip -r $filename $csvFile");
        echo ("[\033[0;37m" . current_time() . "\033[1;37m] Zip file :" . $zipped_file . "\n");

        $file = filesize($csvFile);
        // Filesize in MB
        $filesize = $file / 1024 / 1024;
        $mbs = number_format($filesize, 2);
        echo ("[\033[0;37m" . current_time() . "\033[1;37m] File Size: " . $mbs . "mbs \n\n");
    }
}