<?php

class db {
  private $user = "u5dobrzanska" ;
  private $pass = "5dobrzanska";
  private $host = "pascal.fis.agh.edu.pl";
  private $base = "u5dobrzanska";
  private $dbh;


  function __construct() {
    try {
      $this->dbh = new PDO("pgsql:dbname=".$this->base.";host=".$this->host, $this->user, $this->pass);
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
      die("Connection failed! " . $e->getMessage());
    }
    $this->dbh->exec('SET search_path TO projekt;');
  }

  //Pobranie koktajli
  //Zamiana booleanow na wartości przyjemne dla użytkownika
  public function getKoktajl() {
    $sth = $this->dbh->prepare("SELECT koktajl.id_koktajl, koktajl.nazwa as \"nazwa\", koktajl.weganski, koktajl.alkohol , rodzaj.nazwa as \"rodzaj\", trudnosc.nazwa as \"trudnosc\", szklanki.nazwa as \"szklanka\" FROM projekt.koktajl join projekt.rodzaj using (id_rodzaj) join projekt.trudnosc using (id_trudnosc) join projekt.szklanki using (id_szklanki) where koktajl.id_rodzaj=rodzaj.id_rodzaj ;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    foreach($result as &$koktajl) {
      if($koktajl['weganski'] == true){
        $koktajl['weganski'] = 'tak';
      }
      else 
        $koktajl['weganski'] = '-';
      
      $koktajl['weganski'] = explode(' ', $koktajl['weganski'])[0];
      if($koktajl['alkohol'] == true){
        $koktajl['alkohol'] = 'tak';
      }
      else 
        $koktajl['alkohol'] = '-';
      $koktajl['alkohol'] = explode(' ', $koktajl['alkohol'])[0];
    }
    return $result;
  }

    //Wyciaganie wszystkich restauracji
  public function getRestauracjaWszystkie() {
    $sth = $this->dbh->prepare("SELECT id_restauracje, nazwa FROM projekt.restauracje;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }


    //Wyciaganie wszystkich restauracji  
  private function _getRestauracjaWszystkie() {
    if ($this->get_request_method() != "GET")
      $this->response('', 406);
    $result = $this->db->getRestauracjaWszystkie();
    $this->response($this->json($result), 200);
  }
  //Pobieranie składników koktaji
  //Zamiana booleanow na wartości przyjemne dla użytkownika
  public function getSkladnik() {
    $sth = $this->dbh->prepare("SELECT koktajl.id_koktajl, skladniki.nazwa as \"produkty\", koktajl.nazwa as \"nazwa\", napoje.nazwa as \"napoje\", alkohole.nazwa as \"alkohol\", round(((skladniki.cena*skladniki_koktajlu.waga)+napoje.cena/20+alkohole.cena/20),2) as \"cena\", round((skladniki.kalorie*skladniki_koktajlu.waga+napoje.kalorie/20+alkohole.kalorie/20),0) as \"kcal\", koktajl_srodek.czas_przygotowania, koktajl_srodek.liczba_porcji FROM projekt.koktajl full join projekt.skladniki_koktajlu using (id_koktajl) join projekt.koktajl_srodek using (id_koktajl)  join projekt.skladniki using (id_skladniki) join projekt.napoje using (id_napoje) join projekt.alkohole using (id_alkohole) where  koktajl.id_koktajl=skladniki_koktajlu.id_koktajl ;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    foreach($result as &$koktajl) {
      if($koktajl['alkohol'] == 'brak'){
        $koktajl['alkohol'] = '-';
      }
      $koktajl['alkohol'] = explode(' ', $koktajl['alkohol'])[0];

    }
    return $result;
  }

  public function checkSession($sessionid) {
    $sth = $this->dbh->prepare("SELECT uzytkownik.id_uzytkownik, uzytkownik.imie, uzytkownik.nazwisko, uzytkownik.login FROM projekt.uzytkownik JOIN projekt.sesja USING (id_uzytkownik) WHERE sesja.sessionid = :sessionid AND sesja.czas > :czas;");
    $sth->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
    $date = date('Y-m-d H:i:s');
    $sth->bindParam(':czas', $date);
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    if (!$result) {
      $result = array(array('id_uzytkownik' => 0, 'imie' => "", 'nazwisko' => "", 'login' => ""));
    }
    $sth = $this->dbh->prepare("UPDATE projekt.sesja SET czas = :czas WHERE sessionid = :sessionid;");
    $sth->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
    $expires = date('Y-m-d H:i:s', time() + (60*60));
    $sth->bindParam(':czas', $expires);
    $sth->execute();

    return $result;
  }

  public function addDieta($array) {
    $loggedUser = $this->checkSession($array['sessionid']);
    $loggedUser = $loggedUser[0];
    if ($loggedUser['id_uzytkownik'] == 0)
      return 'no_permissions';
    $sth = $this->dbh->prepare('INSERT INTO projekt.dieta (id_dieta, koktajl, kalorie) VALUES (default, :koktajl, :kalorie);');
    $sth->bindParam(':koktajl', $array['koktajl']);
    $sth->bindParam(':kalorie', $array['kalorie']);
    try {
      $sth->execute();
      return 'ok';
    }
    catch (PDOException $e) {
      return 'incorrect_data';
    }
  }

   //wyciaganie wszystkich ocen
  public function getOcena() {
    $sth = $this->dbh->prepare("SELECT restauracje.nazwa as \"nazwa\", restauracje.ocena as \"ocena\", round(avg(cena),2) as srednia_cena from projekt.menu join projekt.restauracje on restauracje.id_restauracje=menu.id_restauracje group by restauracje.id_restauracje order by ocena desc;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }


   public function login($array) {
    $login = $array['login'];
    $password = $array['haslo'];
    $sth = $this->dbh->prepare("SELECT id_uzytkownik, imie, nazwisko FROM projekt.uzytkownik WHERE login = :login AND haslo = :haslo LIMIT 1;");
    $sth->bindParam(':login', $login);
    $sth->bindParam(':haslo', $password);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    $pracownik_id = $result['id_uzytkownik'];
    if ($pracownik_id) {
      $sth = $this->dbh->prepare("INSERT INTO projekt.sesja (id_uzytkownik, sessionid, czas) VALUES (:id_sesja, :sessionid, :czas);");
      $sth->bindParam(':id_sesja', $pracownik_id, PDO::PARAM_INT);
      $sesid = md5($pracownik_id.md5(time()));
      $sth->bindParam(':sessionid', $sesid, PDO::PARAM_STR);
      $expires = date('Y-m-d H:i:s', time() + (2*60*60));
      $sth->bindParam(':czas', $expires, PDO::PARAM_STR);
      $res = $sth->execute();
      $result = array(array('sessionid' => $sesid, 'id_uzytkownik' => $pracownik_id, 'login' => $login, 'imie' => $result['imie'], 'nazwisko' => $result['nazwisko']));
      return $result;
    }
  }

   //Dodawanie rejestracji
  public function addUzytkownik($array) {
      $sth = $this->dbh->prepare("INSERT INTO projekt.uzytkownik (login, haslo, imie, nazwisko, id_restauracje)
        VALUES (:login, :haslo, :imie, :nazwisko, :id_restauracje);");
      $sth->bindParam(":login", $array['login']);
      $sth->bindParam(":haslo", $array['haslo']);
      $sth->bindParam(":imie", $array['imie']);
      $sth->bindParam(":nazwisko", $array['nazwisko']);
      $sth->bindParam(":id_restauracje", $array['id_restauracje'], PDO::PARAM_INT);
      $sth->execute();
      return 'ok';
  }


   public function logout($sessionid) {
    $sth = $this->dbh->prepare("DELETE FROM projekt.sesja WHERE sessionid = :sessionid;");
    $sth->bindParam(':sessionid', $sessionid);
    $sth->execute();
    return array(array('status' => 'ok'));
  }

  //Skorzystanie z widoku - wyliczenie wartosci min i maksymalnych
  //Zamiana booleanow na wartości przyjemne dla użytkownika
  public function getMin() {
    $sth = $this->dbh->prepare("SELECT restauracja, min(cena) as cena1, max(cena) as cena2 from projekt.menurest group by restauracja ;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
   
    return $result;
  }

   public function getDiet() {
    $sth = $this->dbh->prepare("SELECT koktajl, kalorie from projekt.dieta ;");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
   
    return $result;
  }




}


?>