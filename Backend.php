<?php

require 'simple_html_dom.php';
require 'RedBean/rb.php';

R::setup('mysql:host=localhost;dbname=plan', 'root', '');
R::useWriterCache(true);
ini_set('max_execution_time', 300); //300 seconds = 5 minutes


/**
 * Class for updating database
 */
Class SheduleManager {

    /**
     * Member to store list of hours
     * @var array
     */
    private $hours_list = array();
    
    /**
     *Member to store list of shedules
     * @var array
     */
    private $shedules_list = array();

    /**
     * Set Hourts List
     * @param array $hours_list
     */
    private function setHoursList($hours_list) {
        $this->hours_list = $hours_list;
    }

    
    /**
     * Updating all data in database
     */
    public function updateAllData() {
        // TODO make another function for creating copy of plan table
        $plany = $this->downloadShedulesList(0);
        if($this->downloadInfo($plany[0])) {
        
        
        R::wipe('plan');

        $this->updateTeachersList($this->downloadShedulesList(1));
        $this->updateRoomList($this->downloadShedulesList(2));
        $klasy = $this->downloadShedulesList(0);
        foreach ($this->downloadShedulesList(0) as $klasa) {
            $this->updateClassShedule($klasa[0]);
        }
        }
    }
    
  
    /**
     * Downloading shedules list
     * @param string $i
     * @return array
     */
    private function downloadShedulesList($i) {
        $html = file_get_html('http://www.sci.edu.pl/plan/lista.html');

        $list = array();
        $ul = $html->find('ul', $i);
        foreach ($ul->find('li') as $li) {
            $row = array();
            $a = $li->find('a', 0);
            $row[] = "http://www.sci.edu.pl/plan/" . $a->href;
            $row[] = $a->plaintext;

            $list[] = $row;
        }
        $this->shedules_list = $list;
        return $list;
    }

    /**
     * Updating teachers list in database
     * @param array $array Array of teachers
     */
    public function updateTeachersList($array) {
        R::wipe('nauczyciele');

        foreach ($array as $row) {

            $nauczyciel = R::dispense('nauczyciele');

            $legacy_id = $row[0];
            $legacy_id = substr($legacy_id, 0, strlen($legacy_id) - 5);
            $legacy_id = substr($legacy_id, 34);

            $test = 'test';
            $nauczyciel->legacy_id = (string) $legacy_id;
            $nauczyciel->skrot = (string) $row[1];
            $nauczyciel->pelna_nazwa = (string) '';

            $id = R::store($nauczyciel);
        }
    }

    /**
     * Updating hourts list in database
     * @param type $example_plan_url
     */
    public function updateHoursList($example_plan_url) {
        $html = file_get_html($example_plan_url);
        $plan = $html->find('.tabela', 0);
        $hours_list = array();
        for ($hours_row = 1; $hours_row <= 11; $hours_row++) {
            $td = $plan->find('tr', $hours_row)->find('td', 1);

            $hours_pair = $td->plaintext;

            if (strpos($hours_pair, " ")) {
                if (strpos($hours_pair, " ") > strpos($hours_pair, "-")) {
                    $start = substr($hours_pair, 0, strpos($hours_pair, "-"));
                    $stop = substr($hours_pair, strpos($hours_pair, " ") + 1);
                } else {
                    $start = substr($hours_pair, 0, strpos($hours_pair, " "));
                    $stop = substr($hours_pair, strpos($hours_pair, "-") + 1);
                }
            } else {
                $start = substr($hours_pair, 0, strpos($hours_pair, "-"));
                $stop = substr($hours_pair, strpos($hours_pair, "-") + 1);
            }


            $start = trim($start);
            $stop = trim($stop);

            $houers_row = array();
            $houers_row[0] = $start;
            $houers_row[1] = $stop;

            $hours_list[] = $houers_row;
        }


        R::wipe('godziny');
        foreach ($hours_list as $hours_row) {
            R::useWriterCache(true);

            $houers_row = R::dispense('godziny');

            $houers_row->start = (string) $hours_row[0];
            $houers_row->stop = (string) $hours_row[1];

            $id = R::store($houers_row);
        }
        $this->setHoursList($hours_list);
    }
    
    public function downloadInfo($example_plan_url) {
        //echo $example_plan_url[0];
        $html = file_get_html($example_plan_url[0]);
        //html/body/div/table/tbody/tr[2]/td
        $info = $html->find('table' , 1)->children(1)->children(0)->plaintext;
        $wygenerowane = $html->find('table' , 1)->children(2)->children(1)->plaintext;
        
        //$info = $html->find('div' , 0)->find('table', 0)->find('tbody', 0)->find('tr', 1)->find('td', 0)->plaintext;
//        $div = $html->find('div', 0);
//        $table = $div->find('table', 0);
//        $tbody = $table->find('tbody', 0);
//        //var_dump($table);
//        $tr = $table->find('tr', 0);
//        $info = $tr->find('td', 0)->plaintext;
        //echo 'info: '.$info;
        
        $infos_prev = R::findAll( 'info' , ' ORDER BY id DESC LIMIT 10 ' );
        $last = next($infos_prev);
        $last_updated = $last->updated;
        $last_id = $last->id;
        $table_name = "plan".$last_id;
        echo $table_name;
        echo $info.' '. $last_updated;
        if ($info != $last_updated ) {
             R::begin();
            try{
                R::exec("CREATE TABLE ". $table_name ."LIKE plan;");
                R::exec("INSERT ".$table_name." SELECT * FROM plan;");
                R::commit();
            }
            catch(Exception $e) {
                 echo 'CO JEST KURWA';
                R::rollback();
            }
           
            $info_db = R::dispense('info');
            $info_db->updated = (string)$info;
            $info_db->created = (string)$wygenerowane;
            $id = R::store($info_db);
            echo 'nowy plan!';
            return true;
        } else {
            echo 'stary plan';
            return false;
        }
    }

    /**
     * Updating room list in database
     * @param type $room_list
     */
    public function updateRoomList($room_list) {
        R::wipe('sale');

        foreach ($room_list as $room_row) {
            R::useWriterCache(true);

            $room = R::dispense('sale');

            $legacy_id = $room_row[0];
            $legacy_id = substr($legacy_id, 0, strlen($legacy_id) - 5);
            $legacy_id = substr($legacy_id, 34);

            $test = 'test';
            $room->legacy_id = (string) $legacy_id;
            $room->skrot = (string) $room_row[1];
            $room->pelna_nazwa = (string) '';

            $id = R::store($room);
        }
    }

    /**
     * Dispensing shedule row into database
     * @param type $klasa
     * @param type $dzien
     * @param type $godzina
     * @param type $nauczyciel
     * @param type $przedmiot
     * @param type $sala
     */
    private function putSheduleRow($klasa, $dzien, $godzina, $nauczyciel, $przedmiot, $sala) {
        R::useWriterCache(true);

        $row = R::dispense('plan');
        $row->klasa = (string) $klasa;
        $row->dzien = (string) $dzien;
        $row->godzina = (string) $godzina;
        if (!$nauczyciel == '') {
            $row->przedmiot = (string) $przedmiot;
            $row->nauczyciel = (string) $nauczyciel;
            $row->sala = (string) $sala;
        }

        $id = R::store($row);
    }

    /**
     * Updating shedule for class
     * @param type $class_shedule_link
     */
    private function updateClassShedule($class_shedule_link) {
        echo $class_shedule_link;
        $html = file_get_html($class_shedule_link);
        $plan = $html->find('.tabela', 0);
        $klasa = $class_shedule_link;
        $klasa = substr($klasa, 34);
        $klasa = substr($klasa, 0, strlen($klasa) - 5);
        $array = array();
        for ($i = 1; $i <= 11; $i++) {
            for ($j = 2; $j < 7; $j++) {
                $row = array();
                if ($plan->find('tr', $i)) {
                    $td = $plan->find('tr', $i)->find('td', $j);

                    $dzien = $j - 1;
                    $godzina = $i - 1;

                    if ($td->find('span', 0)) {
                        if($td->find('br', 0)) {
                            for($i=0; $i<2; $i++) {
                                $span = $td->find('span', $i);
                                $przedmiot = $span->children(0)->plaintext;
                                $nauczyciel = $span->children(1)->href;
                                if ($span->children(2))
                                    $sala = $span->children(2)->href;

                                $nauczyciel = substr($nauczyciel, 0, strlen($nauczyciel) - 5);
                                $sala = substr($sala, 0, strlen($sala) - 5);

                                $nauczyciel = substr($nauczyciel, 1);
                                $sala = substr($sala, 1);

                                $this->putSheduleRow($klasa, $dzien, $godzina, $nauczyciel, $przedmiot, $sala);
                            }
                        } else {
                        $przedmiot = $td->find('span', 0)->plaintext;
                        $nauczyciel = $td->find('a', 0)->href;
                        if ($td->find('a', 1))
                            $sala = $td->find('a', 1)->href;

                        $nauczyciel = substr($nauczyciel, 0, strlen($nauczyciel) - 5);
                        $sala = substr($sala, 0, strlen($sala) - 5);

                        $nauczyciel = substr($nauczyciel, 1);
                        $sala = substr($sala, 1);

                        $this->putSheduleRow($klasa, $dzien, $godzina, $nauczyciel, $przedmiot, $sala);
                        }
                    } else {
                        $this->putSheduleRow($klasa, $dzien, $godzina, '', '', '');
                    }

                    if ($i == 11 && empty($this->hours_list)) {
                        $this->updateHoursList($class_shedule_link);
                    }
                }
            }
        }
    }

}
