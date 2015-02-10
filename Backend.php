<?php

require 'simple_html_dom.php';
require 'RedBean/rb.php';

R::setup('mysql:host=localhost;dbname=plan', 'root', '');
ini_set('max_execution_time', 300); //300 seconds = 5 minutes

Class Backend {
    private function getList($i) {
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
        
//        $teachers = array();
//        $ul = $html->find('ul', 1);
//        foreach ($ul->find('li') as $li) {
//            $row = array();
//            $a = $li->find('a', 0);
//            $nauczyciel = $a->href;
//            $nauczyciel = substr($nauczyciel, 0, strlen($nauczyciel) - 5);
//            $nauczyciel = substr($nauczyciel, 1);
//            
//            $row[] = $nauczyciel;
//            $row[] = $a->plaintext;
//
//            $teachers[] = $row;
//        }
//        
//        $this->listaNauczycieli($techears);
        
        
        return $list;
    }

    public function listaNauczycieli($array) {
        R::wipe('nauczyciele');
        
        print_r($array);
        foreach($array as $nauczycielx) {
            print_r($nauczycielx);
            R::useWriterCache(true);
        
            $nauczyciel = R::dispense('nauczyciele');
            
            $legacy_id = $nauczycielx[0];
            $legacy_id = substr($legacy_id, 0, strlen($legacy_id) - 5);
            $legacy_id = substr($legacy_id, 34);
            
            $test = 'test';
            $nauczyciel->legacy_id = (string) $legacy_id;
            $nauczyciel->skrot = (string) $nauczycielx[1];
            $nauczyciel->pelna_nazwa = (string) '';
            
            $id = R::store($nauczyciel);
        }
    }
    
    public function listaSal($array) {
        R::wipe('sale');
        
        print_r($array);
        foreach($array as $salax) {
            print_r($salax);
            R::useWriterCache(true);
        
            $sala = R::dispense('sale');
            
            $legacy_id = $salax[0];
            $legacy_id = substr($legacy_id, 0, strlen($legacy_id) - 5);
            $legacy_id = substr($legacy_id, 34);
            
            $test = 'test';
            $sala->legacy_id = (string) $legacy_id;
            $sala->skrot = (string) $salax[1];
            $sala->pelna_nazwa = (string) '';
            
            $id = R::store($sala);
        }
    }
    
    public function getData() {
        R::wipe('plan');
        
        $this->listaNauczycieli($this->getList(1));
        $this->listaSal($this->getList(2));
        
        foreach ($this->getList(0) as $klasa) {
            $this->parseKlasa($klasa[0]);
        }
    }

    private function dodajWiersz($klasa, $dzien, $godzina, $nauczyciel, $przedmiot, $sala) {
        R::useWriterCache(true);
        
        $wiersz = R::dispense('plan');
        $wiersz->klasa = (string) $klasa;
        $wiersz->dzien = (string) $dzien;
        $wiersz->godzina = (string) $godzina;
        if(!$nauczyciel=='') {
            $wiersz->przedmiot = (string) $przedmiot;
            $wiersz->nauczyciel = (string) $nauczyciel;
            $wiersz->sala = (string) $sala;
        } 

        $id = R::store($wiersz);
    }
    private function parseKlasa($link) {
        echo $link;
        $html = file_get_html($link);
        $plan = $html->find('.tabela', 0);
        $klasa = $link;
        $klasa = substr($klasa, 34);
        $klasa = substr($klasa, 0, strlen($klasa) - 5);
        $array = array();
        for ($i = 1; $i <= 11; $i++) {
            for ($j = 2; $j < 7; $j++) {
                $row = array();
                if($plan->find('tr', $i)) {
                $td = $plan->find('tr', $i)->find('td', $j);

                $przedmiot = $td->find('span', 0)->plaintext;
                $dzien = $j - 1;
                $godzina = $i - 1;

                if ($przedmiot) {
                    $nauczyciel = $td->find('a', 0)->href;
                    $sala = $td->find('a', 1)->href;

                    $nauczyciel = substr($nauczyciel, 0, strlen($nauczyciel) - 5);
                    $sala = substr($sala, 0, strlen($sala) - 5);

                    $nauczyciel = substr($nauczyciel, 1);
                    $sala = substr($sala, 1);

                    $this->dodajWiersz($klasa, $dzien, $godzina, $nauczyciel, $przedmiot, $sala);
                } else {
                    $this->dodajWiersz($klasa, $dzien, $godzina, '', '', '');
                }
            }
            }
        }
    }

}
