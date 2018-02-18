var objJSON;
var id_mongo;
var userID=0;
var userData = {};
var baseURL = window.location.href;
baseURL = baseURL.substr(0, baseURL.lastIndexOf("/")) + "/rest/";
var websiteName = "Koktajle";

var indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB || window.shimIndexedDB;
var IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction;
var open = indexedDB.open("BazaKoktajli", 1);

open.onupgradeneeded = function() {
    var db = open.result;
    var store = db.createObjectStore("Dieta", {keyPath: "id_dieta", autoIncrement: true});
    store.createIndex("koktajl", "koktajl", { unique: false });        
    store.createIndex("kalorie", "kalorie", { unique: false });
};


open.onsuccess = function() {
  var db = open.result;
  document.getElementById('_addDieta').onclick = function() {
    var dieta = {};
    dieta.dieta = document.getElementById('koktajl');
    dieta.kalorie = document.getElementById('kalorie');
    var status = document.getElementById('status');
    status.innerHTML = '';
    if (!dieta.dieta.checkValidity()) {
      status.innerHTML += 'Dieta: ' + dieta.dieta.validationMessage + '<br />';
    }
     if (!dieta.kalorie.checkValidity()) {
      status.innerHTML += 'Dieta: ' + dieta.kalorie.validationMessage + '<br />';
    }
    if (status.innerHTML.length > 0)
      return;
    dieta.dieta = dieta.dieta.value;
    dieta.kalorie = dieta.kalorie.value;

    var tx = db.transaction("Dieta", "readwrite");
    var store = tx.objectStore("Dieta");
    adding = store.put({koktajl: dieta.dieta, kalorie: dieta.kalorie});
    adding.onsuccess = function() {
      alert('Pomyslnie dodano koktajl do bazy dietetycznej.');
      document.getElementById('addNewDieta').style.display = "none";
    }
  }
  document.getElementById('synchronizacja').onclick = function() {
    document.getElementById('addNewDieta').style.display = "none";
    if (userID == 0) {
      alert('Nie jestes zalogowany');
      return;
    }
    var transaction = db.transaction("Dieta", 'readwrite');
    var objectStore = transaction.objectStore("Dieta");
    var cursorRequest = objectStore.openCursor();
    var sync = 0;
    cursorRequest.onsuccess = function(event) {
      cursor = event.target.result;
      if (cursor) {
        var request = getRequestObject();
        request.onreadystatechange = function() {
          if (request.readyState == 4) {
            objJSON = JSON.parse(request.response);
            status = objJSON['status'];
            if (status == 'ok') {
              sync = sync + 1;
              cursor.delete();
            }
            else if (status == 'no_permissions') {
              alert("Brak uprawnień");
              return;
            }
            else
            {
              alert("Błąd danych - brak synchronizacji");
              return;
            }
          }
        }
        request.open("PUT", baseURL+"addDieta", false);
        request.setRequestHeader('Authenticate', readCookie());
        request.send(JSON.stringify(cursor.value));
        cursor.continue();
      }
      else {
        if (sync > 0)
          alert("Zsynchronizowano "+sync+" rekordów.");
        else
          alert("Brak rekordów do synchronizacji.");
        sync = 0;
      }
    }
  }
  document.getElementById('dietaOffline').onclick = function() {
    document.getElementById('addNewDieta').style.display = "none";
    var transaction = db.transaction("Dieta", 'readonly');
    var objectStore = transaction.objectStore("Dieta");
    objectStore.getAll().onsuccess = function(event) {
      var array = event.target.result;
      var sync = 0;
      var txt = `<table class="dietaTable">
          <thead><tr>
            <td width="200px">Nazwa</td>
            <td width="200px">Kalorie</td>
          </tr></thead>`;
      for (var i = 0; i < array.length; i++) {
        sync = sync + 1;
        txt += `</td><td>`+array[i]['koktajl']+`</td><td>`+array[i]['kalorie']+`</td></tr>`;
      }
      if (sync == 0) {
        txt += "<tr><td colspan='3'><center>Brak rekordów</center></td></tr>";
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    };
  }
}

/*
* Funkcja do zakładki Składniki
* Tworzy tabelę, w której wpisywane są później wygenerowane wartości
* Jest w niej zawarta funkcja getSkladnik
*/

function _listaSkladnikow() {
  document.getElementById('addNewDieta').style.display = "none";
  document.getElementById('result').innerHTML = '';
  request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      var txt = `
      <table class="SkladnikTable">
        <thead><tr>
          <td width="15%">Nazwa</td>
          <td width="10%">Napoje</td>
          <td width="10%">Alkohol</td>
          <td width="10%">Składniki</td>
          <td width="15%">Czas przygotowania</td>
          <td width="10%">Liczba porcji</td>
          <td width="10%">Srednia cena</td>
          <td width="10%">Liczba kcal</td>
        </tr></thead>
      `;
      for (var id in objJSON) {
        obj = objJSON[id];
        txt += `
        <tr>
          <td>`+obj['nazwa']+`</td>
          <td>`+obj['napoje']+`</td>
          <td>`+obj['alkohol']+`</td>
          <td>`+obj['produkty']+`</td>
          <td>`+obj['czas_przygotowania']+` min</td>
          <td>`+obj['liczba_porcji']+` szt</td>
          <td>`+obj['cena']+` zł</td>
          <td>`+obj['kcal']+` kcal</td>
        </tr>
        `;
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    }
  }
  request.open("GET", baseURL+"getSkladnik");
  request.send(null);
}
/*
* Funkcja do pierwszej zakładki Koktajle
* Tworzy tabelę, w której wpisywane są później wygenerowane wartości
* Jest w niej zawarta funkcja getKoktajl
*/

function _listaKoktajli() {
  document.getElementById('addNewDieta').style.display = "none";
  document.getElementById('result').innerHTML = '';
  request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      var txt = `
      <table class="koktajlTable">
        <thead><tr>
          <td width="30%">Nazwa</td>
          <td width="10%">Weganski</td>
          <td width="10%">Alkohol</td>
          <td width="10%">Rodzaj</td>
          <td width="10%">Trudnosc</td>
          <td width="20%">Szklanka</td>
        </tr></thead>
      `;
      for (var id in objJSON) {
        obj = objJSON[id];
        txt += `
        <tr>
          <td>`+obj['nazwa']+`</td>
          <td>`+obj['weganski']+`</td>
          <td>`+obj['alkohol']+`</td>
          <td>`+obj['rodzaj']+`</td>
          <td>`+obj['trudnosc']+`</td>
          <td>`+obj['szklanka']+`</td>
        </tr>
        `;
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    }
  }
  request.open("GET", baseURL+"getKoktajl");
  request.send(null);
}

function getRequestObject() {
  if ( window.ActiveXObject) {
    return (new ActiveXObject("Microsoft.XMLHTTP"));
  }
  else if (window.XMLHttpRequest) {
    return (new XMLHttpRequest());
  }
  else {
    return (null);
  }
}

function writeCookie(value) {
  document.cookie = "sesja=" + value + "; path=/";
}

function readCookie() {
  var i, c, ca, nameEQ = "sesja=";
  ca = document.cookie.split(';');
  for(i=0;i < ca.length;i++) {
    c = ca[i];
    while (c.charAt(0)==' ') {
      c = c.substring(1,c.length);
    }
    if (c.indexOf(nameEQ) == 0) {
      return c.substring(nameEQ.length,c.length);
    }
  }
  return '';
}



function checkSession(isAccessingUserPanel = false) {
  var request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      if (userID != parseInt(objJSON[0]['id_uzytkownik'])) {
        userID = parseInt(objJSON[0]['id_uzytkownik']);
        userData = objJSON[0];
      }
      if (isAccessingUserPanel) {
        if (userID == 0) {
          _panelLogowania();
        }
        else {
          _panel();
        }
      }
    }
  }
  var req = {};
  req.sesja = readCookie();
  sesja = JSON.stringify(req);
  request.open("POST", baseURL+"checkSession", true);
  request.send(sesja);
}



function _userPanel() {
  document.getElementById('result').innerHTML = '';
  checkSession(true);
}


function _panelLogowania() {
  document.getElementById('addNewDieta').style.display = "none";
  document.title = websiteName + " - Logowanie";
  document.getElementById('data').innerHTML = `
  <div style="margin-left: 360px; width: 300px; align: center;">
  <table class="loginForm" style="width: 100%; text-align: center;">
  <thead><tr><td colspan="2">Zaloguj się</td></tr></thead>
    <form method='post'>
      <tr><td width="40%">Login:</td><td><input type='text' value='' name='login' id='login' /></td></tr>
      <tr><td>Hasło:</td><td><input type='password' value='' name='password' id='password' /></td></tr>
      <tr><td colspan="2"><input type='submit' name='submitLogin' class="button button3" onclick='_login(this.form);' value='Zaloguj' /></td></tr>
    </form>
  </table></div>
  `;
}

function _panel() {
  document.getElementById('addNewDieta').style.display = "none";
  document.title = websiteName + " - Panel użytkownika";
  document.getElementById('data').innerHTML = `
  <div style="margin: auto; width: 500px; align: center;">
  <table class="loginForm" style="width: 100%; text-align: center;">
  
 <thead><tr><td colspan="2"> Co chcesz zrobic `+userData['imie']+' '+userData['nazwisko']+`?</td></tr></thead>
    <form method='post'>
      <tr>
        <td width="50%"><button class="button button3" onclick="_addDietaForm();" value="Dodaj">Dodaj nowe koktajle dietetyczne </button></td>
        <td width="50%"><button class="button button3" onclick="_listaOcen();" value="Dodaj">Lista ocen restauracji w Krakowie</button></td>
      </tr>
      <thead><tr><td colspan="2">Wylogowywanie</td></tr></thead>
     <tr> <td colspan="2"><input type='submit' class="button button3" name='logout' onclick='_logout();' value='Wyloguj' /></td></tr>
     
    </form>
  </table></div>
  `;
}

/*
* Funkcja do zakładki Wykaz restauracji - oceny
* Tworzy tabelę, w której wpisywane są później wygenerowane wartości
* Jest w niej zawarta funkcja getOcena
*/

function _listaOcen() {
  document.getElementById('result').innerHTML = '';
  request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      var txt = `
      <table class="OcenyTable">
        <thead><tr>
          <td width="50%">Restauracja</td>
          <td width="25%">Ocena</td>
          <td width="25%">Średnia cena</td>
        </tr></thead>
      `;
      for (var id in objJSON) {
        obj = objJSON[id];
        txt += `
        <tr>
          <td>`+obj['nazwa']+`</td>
          <td>`+obj['ocena']+`</td>
          <td>`+obj['srednia_cena']+` zł</td>
        </tr>
        `;
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    }
  }
  request.open("GET", baseURL+"getOcena");
  request.send(null);
}

function _login(form) {
  login = document.getElementById('login').value;
  password = document.getElementById('password').value;
  if (login == "" || password == "") {
    document.getElementById('result').innerHTML = "Uzupełnij wszystkie pola. Czegoś brakuje.";
    return;
  }
  var request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      writeCookie(objJSON[0]['sessionid']);
      delete objJSON[0]['sessionid'];
      userData = objJSON[0];
      userID = parseInt(objJSON[0]['id_uzytkownik']);
      if (userID != 0) {
        document.getElementById('result').innerHTML = "Pomyślnie zalogowano!";
        _panel();
      }
      else{
        document.getElementById('result').innerHTML = "Niepoprawny login lub hasło!";
      
      }
    }
  }
  var req = {};
  req.login = login;
  req.haslo = password;
  input = JSON.stringify(req);
  request.open("POST", baseURL+"login", true);
  request.send(input);
}


function _logout() {
  var sessionId = readCookie();
  if (sessionId.length > 0) {
    var request = getRequestObject();
    request.onreadystatechange = function() {
      if (request.readyState == 4) {
        writeCookie('0');
        document.getElementById('result').innerHTML = 'Zostałeś wylogowany.';
        _panelLogowania();
      }
    }
    var req = {};
    request.open("POST", baseURL+"logout", true);
    request.send(null);
  }
}

/*
* Funkcja do pokazania koktajli o najniższych cenach 
* Tworzy tabelę, w której wpisywane są później wygenerowane wartości
* Jest w niej zawarta funkcja getKoktajl
*/

function _listaMin() {
  document.getElementById('addNewDieta').style.display = "none";
  document.getElementById('result').innerHTML = '';
  request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      var txt = `
      <table class="minTable">
        <thead><tr>
          <td width="30%">Restauracja</td>
          <td width="20%">Cena minimalna</td>
          <td width="10%">Cena maksymalna</td>
        </tr></thead>
      `;
      for (var id in objJSON) {
        obj = objJSON[id];
        txt += `
        <tr>
          <td>`+obj['restauracja']+`</td>
          <td>`+obj['cena1']+` zł</td>
          <td>`+obj['cena2']+` zł</td>
        </tr>
        `;
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    }
  }
  request.open("GET", baseURL+"getMin");
  request.send(null);
}


/*
* Funkcja do pokazania koktajli o najniższych cenach 
* Tworzy tabelę, w której wpisywane są później wygenerowane wartości
* Jest w niej zawarta funkcja getKoktajl
*/

function _listaDiet() {
  document.getElementById('addNewDieta').style.display = "none";
  document.getElementById('result').innerHTML = '';
  request = getRequestObject();
  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      objJSON = JSON.parse(request.response);
      var txt = `
      <table class="minTable">
        <thead><tr>
          <td width="30%">Koktajl</td>
          <td width="20%">Kalorie</td>
        </tr></thead>
      `;
      for (var id in objJSON) {
        obj = objJSON[id];
        txt += `
        <tr>
          <td>`+obj['koktajl']+`</td>
          <td>`+obj['kalorie']+` kcal</td>
        </tr>
        `;
      }
      txt += `</table>`;
      document.getElementById('data').innerHTML = txt;
    }
  }
  request.open("GET", baseURL+"getDiet");
  request.send(null);
}


function _addDietaForm() {
  if (document.getElementById('addNewDieta').style.display == "none") {
    document.getElementById('addNewDieta').style.display = "block";
    document.getElementById('result').innerHTML = "";
  }
  return;
}



