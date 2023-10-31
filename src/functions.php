<?php

function clean_text($string)
{
$string = trim($string);
$string = stripslashes($string);
return $string;
}

//sets timezone
date_default_timezone_set("Africa/Nairobi");
//gets the current time for outputting
function current_time() {
// Get the current time
return date("m/d/Y h:i:s a", time());
}

function employee_range($company_domain,$rows){
  foreach($rows as $row){
    if($row['website_domain'] === $company_domain) {
        $range = $row['number_of_employees'];
        return $range;
    }
   }	
}

function funding($company_domain,$rows){
    foreach($rows as $row){
       if($row['website_domain'] === $company_domain) {
         $amount = (int) $row['total_funding'];
        return $amount;
       }
    }	
}

