<?php
    
require_once ("rest.php");
require_once ("database.php");
  
class API extends REST {

  public $data = "";
  protected $sessionid = "";

  public function __construct(){
    parent::__construct();
    $this->sessionid = $_SERVER['HTTP_AUTHENTICATE'];
    $this->db = new db() ;
  }


  public function processApi(){

    $func = "_".$this->_endpoint ; 
    if ((int)method_exists($this,$func) > 0) {
      $this->$func();
    }
    else {
      $this->response('Page not found',404);
    }
  }


  private function hasNeededKeys($array, $lookedArray) {
    foreach ($array as $key) {
      if (!array_key_exists($key, $lookedArray))
        return false;
    }
    return true;
  }


  private function returnStatus($status) {
    $array = array('status' => $status);
    $this->response($this->json($array), 200);
  }

   //Pobranie Oceny restauracji
  private function _getOcena() {
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->getOcena();
    $this->response($this->json($result), 200);
  }


  private function _checkSession() {
    if ($this->get_request_method() != "POST") {
      $this->response('',406);
    }
    if (!empty($this->_request)) {
      try {
        $json_array = json_decode($this->_request, true);
        $json_array['login'] = strtolower($json_array['login']);
        $result = $this->db->checkSession($json_array['sesja']);
        if ($result) {
          $this->response($this->json($result), 200);
        }
        else {
          $this->response('{"id_uzytkownik":"0"}', 200);
        }
      }
      catch (Exception $e) {
        $this->response('', 400);
      }
    }
  }

    //Wyciaganie wszystkich restauracji  
  private function _getRestauracjaWszystkie() {
    if ($this->get_request_method() != "GET")
      $this->response('', 406);
    $result = $this->db->getRestauracjaWszystkie();
    $this->response($this->json($result), 200);
  }

  /*
  * Dodawanie restauracji do bazy
  * Sprawdzanie, czy wprowadzane dane są prawidłowe
  */
  private function _addUzytkownik() {
    if ($this->get_request_method() != "PUT") {
      $this->returnStatus("sdf");
    }
    if (empty($this->_request)) {
      $error = array('status' => "incorrect_data");
      $this->response($this->json($error), 400);
    }

    try {
      $json_array = json_decode($this->_request, true);
     
      $neededKeys = array('login', 'haslo', 'imie', 'nazwisko', 'id_restauracje');
    
      if (!preg_match("/^[A-Za-z0-9-._ ąćęłńóśźżĄĘŁŃÓŚŹŻ]{1,}$/", $json_array['imie']))
        $this->returnStatus('incorrect_data');
      if (!preg_match("/^[A-Za-z0-9-._ ąćęłńóśźżĄĘŁŃÓŚŹŻ]{1,}$/", $json_array['nazwisko']))
        $this->returnStatus('incorrect_data');
      if (!preg_match("/^[A-Za-z0-9-._ ąćęłńóśźżĄĘŁŃÓŚŹŻ]{1,}$/", $json_array['login']))
        $this->returnStatus('incorrect_data');
      if (!preg_match("/^[A-Za-z0-9-._ ąćęłńóśźżĄĘŁŃÓŚŹŻ]{1,}$/", $json_array['haslo']))
        $this->returnStatus('incorrect_data');
      if (!is_numeric($json_array['id_restauracje']))
        $this->returnStatus('incorrect_data');
      
      $this->returnStatus($this->db->addUzytkownik($json_array));

    }
    catch (Exception $e) {
      $error = array('status' => ".");
      $this->returnStatus('.');
    }

  }


 private function _login() {
    if ($this->get_request_method() != "POST") {
      $this->response('',406);
    }
    if (!empty($this->_request)) {
      try {
        $json_array = json_decode($this->_request, true);
        $result = $this->db->login($json_array);
        $this->response($this->json($result), 200);
      }
      catch (Exception $e) {
        $this->response('', 400);
      }
    }
  }

   //wyciaganie minimum ceny z koktajli 
  private function _getMin() {
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->getMin();
    $this->response($this->json($result), 200);
  }

    private function _getDiet() {
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->getDiet();
    $this->response($this->json($result), 200);
  }


  private function _logout() {
    if ($this->get_request_method() != "POST") {
      $this->response('',406);
    }
    if (!empty($this->_request)) {
      try {
        $json_array = json_decode($this->_request, true);
        $result = $this->db->logout($json_array['sesja']);
        $this->response($this->json($result), 200);
      }
      catch (Exception $e) {
        $this->response('', 400);
      }
    }
  }

   //wyciaganie koktajli 
  private function _getKoktajl() {
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->getKoktajl();
    $this->response($this->json($result), 200);
  }

  //Pobranie Skladników
  private function _getSkladnik() {
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->getSkladnik();
    $this->response($this->json($result), 200);
  }

    //Koktajle katogoria
  private function _getProdukt() {
    if ($this->get_request_method() != "GET")
      $this->response('', 406);
    $result = $this->db->getObszar();
    $this->response($this->json($result), 200);
  }


  private function _addDieta() {
    if ($this->get_request_method() != "PUT") {
      $this->returnStatus("sdf");
    }
    if (empty($this->_request)) {
      $error = array('status' => "incorrect_data");
      $this->response($this->json($error), 400);
    }

    try {
      $json_array = json_decode($this->_request, true);
      if (strlen($this->sessionid) == 0) {
        $this->returnStatus('no_permissions');
      }
      $neededKeys = array( 'koktajl', 'kalorie');
      if (!$this->hasNeededKeys($neededKeys, $json_array))
        $this->returnStatus('incorrect_data');

     if (!preg_match("/^[A-Za-z0-9-._ ąćęłńóśźżĄĘŁŃÓŚŹŻ]{1,}$/", $json_array['koktajl']))
        $this->returnStatus('incorrect_data');
       if (!preg_match("/^\d+(?:\.\d{2})?$/", $json_array['kalorie']))
          $this->returnStatus('incorrect_data');

      $json_array['sessionid'] = $this->sessionid;

      $this->returnStatus($this->db->addDieta($json_array));

    }
    catch (Exception $e) {
      $error = array('status' => ".");
      $this->returnStatus('.');
    }
  }


  private function _save() {
    if ($this->get_request_method() != "POST") {
      $this->response('',406);
    }

    if (!empty($this->_request) ) {
      try {
        $json_array = json_decode($this->_request,true);
        $res = $this->db->insert($json_array);
        if ( $res ) {
          $result = array('return'=>'ok');
          $this->response($this->json($result), 200);
        }
        else {
          $result = array('return'=>'not added');
          $this->response($this->json($result), 200);
        }
      }
      catch (Exception $e) {
        $this->response('', 400) ;
      }
    }
    else {
      $error = array('status' => "Failed", "msg" => "Invalid send data");
      $this->response($this->json($error), 400);
    }
  }

  private function _list(){
    if ($this->get_request_method() != "GET") {
      $this->response('',406);
    }
    $result = $this->db->select() ;      
    $this->response($this->json($result), 200); 
  }

  private function _delete0() {
    $this->_delete(0);
  }

  private function _delete1() {
     $this->_delete(1);
  }

  private function _delete($flag) {
    if ($this->get_request_method() != "DELETE") {
      $this->response('',406);
    }
    $id = $this->_args[0];
    if (!empty($id)) {
      $res = $this->db->delete($id,$flag);
      if ( $res ) {
        $success = array('status' => "Success", "msg" => "Successfully one record deleted. Record - ".$id);
        $this->response($this->json($success),200);
      }
      else {
        $failed = array('status' => "Failed", "msg" => "No records deleted" );
        $this->response($this->json($failed),200);
      }
    }
    else {
      $failed = array('status' => "No content", "msg" => "No records deleted" );
      $this->response($this->json($failed),204);  // If no records "No Content" status
    }
  }
     
  private function _update0() {
     $this->_update(0);
  }

  private function _update1() {
     $this->_update(1);
  }

  private function _update($flag) {
    if ($this->get_request_method() != "PUT") {
      $this->response('',406);
    }
    $id = $this->_args[0];
    $json_array = json_decode($this->_request,true);;
    if (!empty($id)) {
      $res = $this->db->update($id,$json_array,$flag);
      if ( $res > 0 ) {
        $success = array('status' => "Success", "msg" => "Successfully one record updated.");
        $this->response($this->json($success),200);
      }
      else {
        $failed = array('status' => "Failed", "msg" => "No records updated.");
        $this->response($this->json($failed),200);
      }
      // else
        // $this->response('',204);// If no records "No Content" status		
    }
  }

  private function json($data){
    if(is_array($data)){
      return json_encode($data);
    }
  }
}

$api = new API;
$api->processApi();

?>