<?php

include 'query-api.php';

/**
* Dashboard object queries the SFMOMA API to get and generate json for:
        - total count for artist, artwork, exhibition
        - birth and death years of artists
        - artists' nationalities
        - artwork accessioned year
        - artwork arranged by department
        - number of exhibitions since 1935
*/
class Dashboard {
    static $collection;
    static $artistData;
    static $artworkData;
    static $exhibitionData;

    public $type = array('artists', 'artworks', 'exhibitions');
    public $urls = array ('https://www.sfmoma.org/api/collection/artists/', 
                          'https://www.sfmoma.org/api/collection/artworks/',
                          'https://www.sfmoma.org/api/collection/exhibitions/'
                          );
    public $countries_conversion = array("American" => "USA",
                                         "British" => "United Kingdom",
                                         "Japanese" => "Japan",
                                         "Chinese" => "China",
                                         "Austrian" => "Austria",
                                         "German" => "Germany",
                                         "Russian" => "Russia",
                                         "French" => "France",
                                         "Dutch" => "Netherlands",
                                         "Icelandic" => "Iceland",
                                         "Italian" => "Italy",
                                         "Korean" => "South Korea",
                                         "Spanish" => "Spain",
                                         "Swedish" => "Sweden",
                                         "Mexican" => "Mexico",
                                         "Swiss" => "Switzerland",
                                         "Canadian" => "Canada",
                                         "Belgian" => "Belgium",
                                         "Polish" => "Poland",
                                         "Yugoslavian" => "Yugoslavia",
                                         "Thai" => "Thailand",
                                         "Vietnamese" => "Vietnam",
                                         "Lithuanian" => "Lithuania",
                                         "Taiwanese" => "Taiwan",
                                         "Balinese" => "Bali",
                                         "Ukrainian" => "Ukraine",
                                         "Danish" => "Denmark",
                                         "South African" => "South Africa",
                                         "Armenian" => "Armenia",
                                         "Romanian" => "Romania",
                                         "Hungarian" => "Hungary",
                                         "Bulgarian" => "Bulgaria",
                                         "New Zealander" => "New Zealand",
                                         "Austro-Hungarian" => "Austria",
                                         "Norwegian" => "Norway",
                                         "British?" => "United Kingdom",
                                         "Dutch, Italian" => "Netherlands",
                                         "Irish" => "Ireland",
                                         "Finnish" => "Finland",
                                         "Iranian" => "Iran",
                                         "Laotian" => "Laos",
                                         "Lebanese" => "Lebanon",
                                         "Indian" => "India",
                                         "Cambodian" => "Cambodia",
                                         "Israeli" => "Israel",
                                         "Malaysian" => "Malaysia",
                                         "Greek" => "Greece",
                                         "Turkish" => "Turkey",
                                         "Persian" => "Persia",
                                         "Palestinian" => "Palestine",


                                         "America" => "USA",
                                         "United States" => "USA",
                                         "United Kingdom; England" => "United Kingdom",
                                         "United Kingdom; Isle of Man" => "United Kingdom",
                                         "United Kingdom; Scotland" => "United Kingdom",
                                         "United Kingdom; Ireland" => "United Kingdom",
                                         "United Kingdom; Wales" => "United Kingdom",
                                         "Scottish" => "United Kingdom",
                                         "Czech" => "Czech Republic",
                                         "The Netherlands" => "Netherlands",
                                         "Viet Nam" => "Vietnam",
                                         "Korea" => "South Korea",
                                         "Congo" => "Democratic Republic of the Congo",
                                         "Columbia" => "Colombia",
                                         "born Austria" => "Austria", 
                                         );

    function __construct() { 
        $this->collection = new SfmomaCollectionBase();

        $page = 1;
        $artists = array();
        $artworks = array();
        $exhibitions = array();

        // $this->artistData = $this->get_data($this->urls[0], $page, $this->collection, $artists);
        // $this->artworkData = $this->get_data($this->urls[1], $page, $this->collection, $artworks);
        // $this->exhibitionData = $this->get_data($this->urls[2], $page, $this->collection, $exhibitions);

        // comment or rm this later when api works. this is temporary using local json
        $str = file_get_contents('../json/sfmoma_raw_data_exhibitions.json');
        $exhibitionJSON = json_decode($str, true);
        $this->exhibitionData = $exhibitionJSON;
        // var_dump($this->exhibitionData);
    }

    /*
     * Get collection data from querying the SFMOMA API
    */
    public function get_data($url, $page, &$collection, &$data) {

        $query_params["page"] = $page;

        $response = $this->collection->query_collection($url,$query_params);
        $next = $response->next;

        $data[] = $response;

        if ($page < 1800 && $next) {
            $page++;
            $this->get_data($url, $page, $collection, $data);
        }
        return $data;
    }

    public function get_artist_data() {
        return $this->artistData;
    }

    public function get_artwork_data() {
        return $this->artworkData;
    }

    public function get_exhibition_data() {
        return $this->exhibitionData;
    }

    /*
     * Get total count of artists, artworks, and exhibitions in the collection
    */
    public function get_total_count() {
        $totalCount = array();
        foreach ($this->urls as $url) {
                $obj = $this->collection->query_collection($url);
                $totalCount[] = $obj->count;
        }
        $summary = array_combine($this->type, $totalCount);
        $this->generateJSON('summary', $summary);
    }

    /********* 
    * ARTIST 
    *********/
    /*
     * Process artists' year of birth, death, and groups' established years
    */
    public function process_artist_years(&$items, &$birth_dates, &$death_dates, &$group_dates) {
        if (is_array($items) || is_object($items)) {
            foreach ($items as $value) {
                // remove characters in dates
                if ($value->gender === "group") {
                    $group_date = (int)preg_replace("/[^\d]+/", "", $value->life_info->birth_date_edtf);
                    // add zero to the end if length of date is < 4  
                    $group_date = (int)substr(str_pad($group_date, 4, "0", STR_PAD_RIGHT), 0, 4);

                    if ($group_date !== 0 && $group_date >= 1900) {
                        if (is_array($group_dates) && array_key_exists($group_date, $group_dates)) {
                                $group_dates[$group_date]++;
                        } else {
                                $group_dates[$group_date] = 1;
                        }  
                    }
                } else {
                    $birth_date = (int)preg_replace("/[^\d]+/", "", $value->life_info->birth_date_edtf);
                    $birth_date = (int)substr(str_pad($birth_date, 4, "0", STR_PAD_RIGHT), 0, 4);

                    $death_date = (int)preg_replace("/[^\d]+/", "", $value->life_info->death_date_edtf);
                    $death_date = (int)substr(str_pad($death_date, 4, "0", STR_PAD_RIGHT), 0, 4);

                    // skip empty values and date < 1900  
                    if ($birth_date !== 0 && $birth_date >= 1900) {
                    // add zero to the end if length of date is < 4  
                        if (is_array($birth_dates) && array_key_exists($birth_date, $birth_dates)) {
                                $birth_dates[$birth_date]++;
                        } else {
                                $birth_dates[$birth_date] = 1;
                        }  
                    }

                    if ($death_date !== 0 && $death_date >= 1900) {
                        if (is_array($death_dates) && array_key_exists($death_date, $death_dates)) {
                                $death_dates[$death_date]++;
                        } else {
                                $death_dates[$death_date] = 1;
                        }    
                    }  
                }  

            }
        }
    }

    public function get_artist_years() {
        $birth_dates = array();
        $death_dates = array();
        $group_dates = array();

        foreach ($this->artistData as $key => $value) {
                $this->process_artist_years($value->results, $birth_dates, $death_dates, $group_dates);
        }
        ksort($birth_dates);
        ksort($death_dates);
        ksort($group_dates);
        
        $dates = array();
        $dates['items'] = array();
        $keys = array();

        //COMBINE COMMON BIRTHDATES & DEATHDATES
        foreach ($birth_dates as $date => $count) {
                $item = array();
                $values = array();
                if (array_key_exists($date, $death_dates)) {
                        $item['date'] = $date;
                        $values['birthCount'] = $count;
                        $values['deathCount'] = $death_dates[$date];
                } else {
                        $item['date'] = $date;
                        $values['birthCount'] = $count;
                        $values['deathCount'] = 0;
                }

                if (array_key_exists($date, $group_dates)) {
                        $values['groupCount'] = $group_dates[$date];        
                } else {
                        $values['groupCount'] = 0;      
                }

                $item['values'] = $values;
                array_push($keys, $date);
                array_push($dates['items'], $item);
        }

        //COMBINE COMMON DEATHDATES & GROUPDATES
        foreach ($death_dates as $date => $count) {
                if (!in_array($date, $keys)) {
                        $item = array();
                        $values = array();
                        $item['date'] = $date;
                        $values['birthCount'] = 0;
                        $values['deathCount'] = $count; 
                        if (array_key_exists($date, $group_dates)) {
                                $values['groupCount'] = $group_dates[$date];        
                        } else {
                                $values['groupCount'] = 0;      
                        }  
                        $item['values'] = $values;  
                        array_push($keys, $date);
                        array_push($dates['items'], $item);     
                }
        }

        //GROUPDATES
        foreach ($group_dates as $date => $count) {
                if (!in_array($date, $keys)) {
                        $item = array();
                        $values = array();
                        $item['date'] = $date;
                        $values['birthCount'] = 0;
                        $values['deathCount'] = 0;   
                        $values['groupCount'] = $count;   
                        $item['values'] = $values;   
                        array_push($keys, $date);
                        array_push($dates['items'], $item);     
                }
        }

        arsort($dates);
        $this->generateJSON('artists-info-count', $dates);
    }

    /*
     * Process artist' nationalities
    */
    public function process_artist_nationalities($items, &$countries, &$countries_conversion) {
        foreach ($items as $item) {
            if ($item->background->country != "") {
                $country = $item->background->country;
                if (array_key_exists($country, $this->countries_conversion)) {
                    $country = $this->countries_conversion[$country];
                }
                if (!array_key_exists($country, $countries)) {
                    $countries[$country] = 1;
                } elseif (array_key_exists($country, $countries)) {
                    $countries[$country]++;
                }
            } elseif ($item->background->nationality != "") {
                $nationality = $item->background->nationality;
                if (strpos($nationality, 'born')) {
                    $strArray = explode('born ',$nationality);
                    $country_born = $strArray[1];
                    if (array_key_exists($country_born, $this->countries_conversion)) {
                        $country_born = $this->countries_conversion[$country_born];
                    }
                    if (!array_key_exists($country_born, $countries)) {
                        $countries[$country_born] = 1;
                    } else {
                        $countries[$country_born]++;
                    } 
                } else if (strpos($nationality, 'and')) {
                    $strArray = explode(' and ',$nationality);
                    $nationality_first = $strArray[0];
                    if (array_key_exists($nationality_first, $this->countries_conversion)) {
                        $nationality_first = $this->countries_conversion[$nationality_first];
                    }
                    if (!array_key_exists($nationality_first, $countries)) {
                        $countries[$nationality_first] = 1;
                    } else {
                        $countries[$nationality_first]++;
                    }                
                } else {
                    if (array_key_exists($nationality, $this->countries_conversion)) {
                        $nationality = $this->countries_conversion[$nationality];
                    }
                    if (!array_key_exists($nationality, $countries)) {
                        $countries[$nationality] = 1;
                    } else {
                        $countries[$nationality]++;
                    }
                }    
            }
        } 
    }
    
    public function get_artist_nationalities() {
        $countries = array();

        foreach ($this->artistData as $key => $value) {
            $this->process_artist_nationalities($value->results, $countries, $this->countries_conversion);
        }

        asort($countries);

        $output_countries = array();
        $output_countries['items'] = array();

        foreach ($countries as $country => $count) {
            $item = array();
            $item['country'] = $country;
            $item['count'] = $count;
            array_push($output_countries['items'], $item);
        }

        $this->generateJSON('artists-country', $output_countries);
    }

    /********* 
    * ARTWORK 
    *********/
    /**
    * Process artwork accession year
    */
    public function process_artwork_accession_year($items, &$accession_year) {
        foreach ($items as $item) {
            $accession = $item->accession;
            if ($accession->date_year != "") {
                if (is_array($accession_year) && array_key_exists($accession->date_year, $accession_year)) {
                    $accession_year[$accession->date_year]++;
                } else {
                    $accession_year[$accession->date_year] = 1;
                }            
            } else if ($accession->date_sort != "") {
                $accession_date_sort = explode("-", $accession->date_sort);
                if (is_array($accession_year) && array_key_exists($accession_date_sort[0], $accession_year)) {
                    $accession_year[$accession_date_sort[0]]++;
                } else {
                    $accession_year[$accession_date_sort[0]] = 1;
                }
            }
        }
    }    

    public function get_artwork_accession_year() {
        $accession_year = array();

        foreach ($this->artworkData as $key => $value) {
            $this->process_artwork_accession_year($value->results, $accession_year);
        }

        //sort by key as "year" 
        ksort($accession_year);

        $output_accession_year = array();
        $output_accession_year['items'] = array();

        foreach ($accession_year as $year => $count) {
            $item = array();
            $item['year'] = $year;
            $item['count'] = $count;
            array_push($output_accession_year['items'], $item);
        }

        $this->generateJSON('artworks-accession-year', $output_accession_year);
    }

    /**
    * Process artwork accession by department
    */
    public function process_artwork_accession_department($items, &$accession_department) {
        foreach ($items as $item) {
            $department = $item->department;
            if ($department !== "") {
                if (str_word_count($department) >= 4) {
                    $cross_department = "Cross Departmental Accessions";
                    if (is_array($accession_department) && array_key_exists($cross_department, $accession_department)) {
                        $accession_department[$cross_department]++;
                    } else {
                        $accession_department[$cross_department] = 1;
                    }  
                } else {
                    if (is_array($accession_department) && array_key_exists($department, $accession_department)) {
                        $accession_department[$department]++;
                    } else {
                        $accession_department[$department] = 1;
                    }                 
                }
            }
        }
    }    

    public function get_artwork_accession_department() {
        $accession_department = array();

        foreach ($this->artworkData as $key => $value) {
            $this->process_artwork_accession_department($value->results, $accession_department);
        }

        arsort($accession_department);

        $output_accession_department = array();
        $output_accession_department['items'] = array();

        foreach ($accession_department as $department => $count) {
            $item = array();
            $item['department'] = $department;
            $item['count'] = $count;
            array_push($output_accession_department['items'], $item);
        }

        $this->generateJSON('artworks-accession-department', $output_accession_department);
    }


    /*********** 
    * EXHIBITION 
    ************/
    /**
    * Process exhibition count
    */
    public function process_exhibition_count($items, &$exhibition) {
        foreach ($items as $item) {
            $start_date = $item->start_date;
            if ($start_date != "") {
                $start_dates = explode("-", $start_date);
                $year = $start_dates[0];
                if (is_array($exhibition) && array_key_exists($year, $exhibition)) {
                    $exhibition[$year]++;
                } else {
                    $exhibition[$year] = 1;
                }            
            }
        }
    }

    /**
    * TEMP: REMOVE THIS LATER WHEN API IS AVAILABLE.
    *   Process exhibition count
    */
    public function process_exhibition_count_temp($items, &$exhibition) {
        foreach ($items as $item) {
            // error_log($item["start_date"] . "\n");
            $start_date = $item["start_date"];
            if ($start_date != "") {
                $start_dates = explode("-", $start_date);
                $year = $start_dates[0];
                if (is_array($exhibition) && array_key_exists($year, $exhibition)) {
                    $exhibition[$year]++;
                } else {
                    $exhibition[$year] = 1;
                }            
            }
        }
    }  


    public function get_exhibition_count() {
        $exhibition_count = array();

        // NOTE: comment out this when api works
        // foreach ($this->exhibitionData as $key => $value) {
        //     $this->process_exhibition_count($value->results, $exhibition_count);
        // }

        // NOTE: comment out or rm this when api works. use this for temporary local json
        $this->process_exhibition_count_temp($this->exhibitionData, $exhibition_count);

        ksort($exhibition_count);

        $output_exhibition_count = array();
        $output_exhibition_count['items'] = array();

        foreach ($exhibition_count as $year => $count) {
            $item = array();
            $item['year'] = $year;
            $item['count'] = $count;
            array_push($output_exhibition_count['items'], $item);
        }

        $this->generateJSON('exhibition-count', $output_exhibition_count);
    }

    /*
     * Generate JSON file with specified file name and json object
    */
    public function generateJSON($fileName, $JSONObject) {
        $jsonFileOutput = json_encode($JSONObject, JSON_PRETTY_PRINT);
        $fp = fopen('../json/' . $fileName . '.json', 'w');
        fwrite($fp, $jsonFileOutput);
        fclose($fp); 
    }

}

?>