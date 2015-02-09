<?php
require 'simple_html_dom.php';
require 'RedBean/rb.php';

R::setup('mysql:host=localhost;dbname=plan','root','');

Class Backend {
    
    private $tablicaPlanow;
    
    
    /* gets the data from a URL */
//    private function getData($url) {
//        $ch = curl_init();
//        $timeout = 10;
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
//        $data = curl_exec($ch);
//        curl_close($ch);
//        return $data;
//    }
    
    private function getList() {
        //$listaPlanow = $this->getData('http://www.sci.edu.pl/plan/lista.html');
        $html = file_get_html('http://www.sci.edu.pl/plan/lista.html');
        
        $list = array();
        $ul = $html->find('ul', 0);
        foreach($ul->find('li') as $li) {
             $row = array();
             $a = $li->find('a', 0);
             $row[] = "http://www.sci.edu.pl/plan/".$a->href;
             $row[] = $a->plaintext;
             
             $list[] = $row;
       }
       
       return $list;
    }
    
    public function getData() {
        R::wipe('plan');
        foreach ($this->getList() as $klasa) {
            $this->parseKlasa($klasa[0]);
        }
    }
    
    private function parseKlasa($link) {
        $html = file_get_html($link);
        $plan = $html->find('.tabela', 0);
        $klasa = $link;
        $klasa = substr($klasa, 34);
        $klasa = substr($klasa, 0, strlen($klasa)-5);
        $array = array();
        for($i = 1; $i<11; $i++) {
            for($j = 2; $j<7; $j++) {
                $row = array();
                $td = $plan->find('tr', $i)->find('td', $j);

                $przedmiot = $td->find('span', 0)->plaintext;
                $row[] = $klasa;
                $dzien = $j-1;
                $row[] = $dzien;
                $row[] = $i;
                
                $plany = R::dispense('plan');
                $plany->klasa = (string)$klasa;
                $plany->dzien = (string)$dzien;
                $plany->godzina = (string)$i;
                
                if($przedmiot) {
                    $nauczyciel = $td->find('a', 0)->href;
                    $sala = $td->find('a', 1)->href;
                    
                    $nauczyciel = substr($nauczyciel, 0, strlen($nauczyciel)-5);
                    $sala = substr($sala, 0, strlen($sala)-5);
                    
                    $nauczyciel = substr($nauczyciel, 1);
                    $sala = substr($sala, 1);
                    
                    $plany->przedmiot = (string)$przedmiot;
                    $plany->nauczyciel = (string)$nauczyciel;
                    $plany->nauczyciel = (string)$sala;
                    
                    $row[] = $przedmiot;
                    $row[] = $nauczyciel;
                    $row[] = $sala;
                } else {
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                }
                $array[] = $row;
                $id = R::store($plany);
            }
        }   
        print_r($array);
    }
}