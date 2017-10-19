#!/usr/bin/php
<?php
ini_set('memory_limit', '512M');
/*
* Parse a collection CSV file and just get the bits that you want
* Keir Winesmith, May 2017
* we'll be looking for
* 	id,
	slug,
	gender,
	life_info_birth_date_display,
	life_info_death_date_display,
	background_nationality,
	name_display,
	sfmoma_website_url,
	sfmoma_api_url,
	artwork_accession_numbers
*/


if (empty($argv[1])) die("The json file name or CSV is missed\n");
$csvFilename = $argv[1];
// $csvFilename = "../../csv/sfmoma_raw_data_exhibitions.csv";
$columnData  = array();

$row = 1;
if (($handle = fopen($csvFilename, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
    	if($row==1){
			// get title / header row
	        for ($c=0; $c < $num; $c++) {
	        	$singleColumn = array();
	        	$name = preg_replace('/\x{feff}$/u', '', $data[$c]); //remove \ufeff char, not working tho..
	        	$singleColumn['name'] = $name;
	        	$singleColumn['completionCount'] = 0;
	        	$singleColumn['mostCommonValues'] = array();
	        	$singleColumn['numDistinctValues'] = 0;
	        	$singleColumn['distinctValues'] = array();
	        	$columnData[$c] = $singleColumn;
	        }
    	}else{
	        for ($c=0; $c < $num; $c++) {
	        	if(strlen($data[$c])>1){
	        		$columnData[$c]['completionCount']++;
	        		if(($columnData[$c]['name']=='id')||($columnData[$c]['name']=='slug')
	        			||(!(false === (strpos($columnData[$c]['name'], 'accession'))))
	        			||(!(false === (strpos($columnData[$c]['name'], 'url'))))) {
	        			$columnData[$c]['numDistinctValues'] = -1;
	        			// don't record destinct ids, urls, or slugs
	        		}else{
		        		if(array_key_exists($data[$c], $columnData[$c]['distinctValues'])){
		        			$columnData[$c]['distinctValues'][$data[$c]]++;
		        		}else{
		        			$columnData[$c]['distinctValues'][$data[$c]] = 1;
		        			$columnData[$c]['numDistinctValues']++;
		        		}        			
	        		}
	        	}
	            // echo $data[$c] . ", ";    		
	    	}
        }
      	$row++;
    }
    fclose($handle);
}

$num = count($columnData);
for ($j=0; $j < $num; $j++){
	$completionPercentage =	number_format((float)($columnData[$j]['completionCount']/($row-2))*100, 2, '.', '');
	$columnData[$j]['completionPercentage'] = $completionPercentage;
	asort($columnData[$j]['distinctValues'], SORT_NUMERIC);
	if( (count($columnData[$j]['distinctValues'])) > 3){
		$columnData[$j]['mostCommonValues'] = array_slice($columnData[$j]['distinctValues'], -4, 4);
	}
	if($columnData[$j]['numDistinctValues']== -1){
		$columnData[$j]['numDistinctValues'] = $columnData[$j]['completionCount'];
	}
	unset($columnData[$j]['distinctValues']); 
}


$pathFileName = explode('/', $csvFilename);
$fileName = explode('.', $pathFileName[2]);

$columnDataJSON = json_encode($columnData, JSON_PRETTY_PRINT);

//TODO: output path to ../../csv/ or ../json/ ??
$fp = fopen('../json/' . $fileName[0] . '_completion.json', 'w');
fwrite($fp, $columnDataJSON);
fclose($fp);

// var_dump($columnDataJSON);

exit;
?>