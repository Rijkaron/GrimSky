'use strict';

var dataSite = {settings: {}};
var vm = null;

var errorInterval;
var dataInterval;

// Stel standaard instellingen in.
if (localStorage.getItem('plaats') === null) localStorage.setItem('plaats', 'Amsterdam');
if (getSetting('ElkeXUren') === null) setSetting('ElkeXUren', 3);
if (getSetting('temperatuurEenheid') === null) setSetting('temperatuurEenheid', '°C');
if (getSetting('snelheidEenheid') === null) setSetting('snelheidEenheid', 'kmu');

// Vue opties instellen
var filters = {
  // Zet de temperatuur in de goede eenheid
  temperatuurEenheid: function(temperatuur) {
    var temperatureUnit = getSetting('temperatuurEenheid').toLowerCase();
    if (temperatureUnit == 'k') return (temperatuur + 273) + 'K';
    else if (temperatureUnit == 'f') return Math.round(temperatuur * 1.8 + 32) + '°F';
    else  return (temperatuur) + '°C';
  },
  // Zet de snelheid in de goede eenheid
  snelheidEenheid: function(snelheid) {
    var speedUnit = getSetting('snelheidEenheid').toLowerCase();
    if (speedUnit == 'ms') return Math.round(snelheid / 3.6) + 'm/s';
    else if (speedUnit == 'mph') return Math.round(snelheid * 0.62138817810781) + 'mpu';
    else if (speedUnit == 'kt') return Math.round(snelheid * 0.5399568) + ' knopen';
    else return snelheid + 'km/u';
  },
  neerslagLevel: function(neerslagHoeveelheid) {
    if (neerslagHoeveelheid <= 0.1) return '0';
    else if (neerslagHoeveelheid <= 0.75) return '1';
    else if (neerslagHoeveelheid <= 1.5) return '2';
    else return '3';
  },
  // Zet een timestamp om in de dag van de week (bijvoorbeeld Maandag). Als met de param 'kort' true wordt meegegeven krijgen we de eerste 2 letters van de dag (bijvoobeeld Ma) 
  dag: function(timestamp, kort = false) {
    var dagen = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];
    var dag = dagen[new Date(timestamp * 1000).getDay()];
    return kort ? dag.slice(0, 2) : dag;
  },
  // Zet een timestamp om in een datum (bijvoorbeeld 01-11)
  datum: function(timestamp) {
    var date = new Date(timestamp * 1000);
    return leadingZero(date.getDate()) + '-' + leadingZero(date.getMonth() + 1);
  },
  // Zet een timestamp om in een digitale kloktijd (bijvoorbeeld 11:20)
  tijd: function(timestamp) {
    var date = new Date(timestamp * 1000);
    return leadingZero(date.getHours()) + ':' + leadingZero(date.getMinutes())
  },
  // Zet een timestamp om in het uur (bijvoorbeeld 19u)
  uur: function(timestamp) {
    var date = new Date(timestamp * 1000);
    return leadingZero(date.getHours()) + 'u';
  },
  // Zet een timestamp om in het verschil in dagen tussen nu en de timestamp
  verschilDagen: function(timestamp) {
    var cDate = new Date();
    var nDate = new Date(timestamp * 1000);
    // Kijk of de timestamp nog in deze maand ligt
    if (cDate.getMonth() != nDate.getMonth() || cDate.getFullYear() != nDate.getFullYear()) {
      var daysInMonth = new Date(cDate.getFullYear(), cDate.getMonth() + 1, 0).getDate();
      return nDate.getDate() + daysInMonth - cDate.getDate();
    } else {
      return nDate.getDate() - cDate.getDate();
    }
  },
  // Krijg het pad van een weer icoontje
  path: function(image) {
    return './content/icons/weather/' + image + '.svg';
  },
  // Zet de eerste letter van de string als hoofdletter
  capitalize: function(text) {
    return text.slice(0, 1).toUpperCase() + text.slice(1).toLowerCase();
  }
}

var methods = {
  // Update een instelling op basis van een 'select' verander event en gooi een signaal terug naar de gebruiker
  updateSetting: function(e) {
    setSetting(e.target.dataset.setting, e.target.value);
    $("#settingChange").css('transition', 'none');
    $("#settingChange").css('transform', 'scale(0)');
    setTimeout(function() {
      $("#settingChange").css('transition', 'transform 0.5s cubic-bezier(0.36,0.5,0.63,1.47)');
      $("#settingChange").css('transform', 'scale(1)');
    }, 100)
  },
  // Sluit de mobile navigatie als er op de link is geklikt
  sluitMobileNav: function() {
    if ($("nav").hasClass('active')) toggleMobileNav();
  }
}

$(document).ready(function() {
  // Vraag de goede pagina aan op basis van de hash
  loadPage(location.hash.substr(1));

  // Als er naast de city popup wordt geklikt of als er op de Escape knop wordt gedrukt, sluit dan de popup 
  $("body").keydown(function(e) {
    if (e.key == 'Escape' && $("#cityPopup").css('display') == 'block') setPopupStatus(false);
  })
  $("#cityPopup > div").click(function(e) { e.stopPropagation() });
  $("#cityPopup").click(function() { setPopupStatus(false) });

  // Als er op enter wordt gedrukt in het city input veld, probeer dan de stad te veranderen
  $("#inputPlaats").keydown(function(e) {
    if (e.key == 'Enter') setCityByInput();
  })

  // Laad de weerdata in en zet een interval om elke 10 seconden de weerdata in te laden 
  loadData(true);
  dataInterval = setInterval(loadData, 10 * 1000);
})

// Laad hiermee de verschillende pagina's in
function loadPage(page) {
  // Scroll naar boven
  window.scroll(0, 0);
  switch (page) {
    case 'voorspelling':
      location.hash = '#voorspelling';
      document.title = 'GrimSky | Voorspelling';
      $("#huidig").css('display', 'none');
      $("#voorspelling").css('display', 'block');
      $("#instellingen").css('display', 'none');
      break;
    case 'instellingen':
      location.hash = '#instellingen';
      document.title = 'GrimSky | Instellingen';
      $("#huidig").css('display', 'none');
      $("#voorspelling").css('display', 'none');
      $("#instellingen").css('display', 'block');
      break;
    default:
      location.hash = '#home';
      document.title = 'GrimSky | Home';
      $("#huidig").css('display', 'block');
      $("#voorspelling").css('display', 'none');
      $("#instellingen").css('display', 'none');
  }
}

// Zet de mobile header aan/uit
function toggleMobileNav() {
  if (!window.matchMedia('(max-width: 750px)').matches) return false;
  var nav = $("nav");
  var header = $("header");
  var hamburger = $("#hamburger");
  if (nav.hasClass('active')) {
    hamburger.removeClass('active');
    nav.removeClass('active');
    // Verander de header weer naar normaal
    header.css('background-color', '');
    header[0].style.setProperty('--color', '#fff');
  } else {
    hamburger.addClass('active');
    nav.addClass('active');
    // Verander de header weer naar zwarte tekst op wit
    header.css('background-color', '#fff');
    header[0].style.setProperty('--color', '#000');
  }
}

// Zet het laadbalkje aan/uit met eventueel een witte achtergrond
function setLoader(status, bg = false) {
  var loader = $("#loader");
  if (status) {
    loader.css('display', 'block');
    if (bg) loader.css('background-color', 'rgb(255, 255, 255');
    else loader.css('background-color', 'unset');
  }
  else loader.css('display', 'none');
}

/* Krijg de weerdata. Als het de eerste keer is, zet dan het laadbalkje aan.
 * Als er vandaag al te veel requests zijn gedaan naar de server, zet de site dan voor vandaag onbruikbaar
 * Als de response code geen 200 is EN het is de eerste keer, zet hem dan weer op de standaard stad en probeer het opnieuw
 * Anders gooi een alert naar de gebruiker
 */
function loadData(first = false) {
  if (first) setLoader(true, true);
  $.getJSON('getData.php?plaats=' + localStorage.getItem('plaats'), function(response) {
    if (response.code == 403) {
      setBruikbaar(false);
    } else if (response.code == 200) {
      setData(response);
      if (first) setLoader(false);
    } else if (first) {
      localStorage.setItem('plaats', 'Amsterdam');
      loadData();
    } else {
      alert('Er is iets foutgegaan, probeer het later nog een keer.');
    }
  })
}

// Zet de ingeladen weerdata + de instellingen van de gebruiker in de website
function setData(weerdata) {
  setWindspeed(weerdata.huidig.windKracht);
  setTemperature(weerdata.huidig.temperatuur);

  dataSite.settings.ElkeXUren = getSetting('ElkeXUren');
  dataSite.settings.temperatuurEenheid = getSetting('temperatuurEenheid');
  dataSite.settings.snelheidEenheid = getSetting('snelheidEenheid');

  dataSite.laatsteUpdate = weerdata.time;
  dataSite.city = weerdata.plaats.split(',');
  dataSite.welkomBericht = getWelcomeMessage();

  dataSite.huidig = weerdata.huidig;

  dataSite.verhaal = weerdata.voorspelling.verhaal;

  dataSite.dagen = weerdata.voorspelling.dagelijks;
  dataSite.uren = weerdata.voorspelling.uurlijks;

  // Setup Vue als dat nog niet gedaan is
  if (vm === null) {
    vm = new Vue({
      el: '#container',
      data: dataSite,
      filters: filters,
      methods: methods
    })
  }
}

// Zet de snelheid van de windmolen op basis van de windkracht
function setWindspeed(windkracht) {
  var wieken = document.getElementById('windmolen-wieken');
  var snelheid = (windkracht <= 0) ? 0 : (1 / (windkracht / 10));
  wieken.style.animationDuration = snelheid + 's';
}

// Zet de achtergrond van de website op basis van de temperatuur
function setTemperature(temperature) {
  var H = Math.max(10, 144 - temperature * 3.6);
  $("#container").css('background-color', 'hsl(' + H + ', 70%, 75%)');
}

// Functie geschreven door Cor :)
function getWelcomeMessage() {
  var tijdInUren;
  var d = new Date();
  var tijdInUren = d.getHours();

  if (tijdInUren < 6) {
    return 'Goedenacht';
  } else {
    if (tijdInUren < 12) {
      return 'Goedemorgen';
    } else {
      if (tijdInUren < 18) {
        return 'Goedemiddag';
      } else {
        return 'Goedenavond';
      }
    }
  }
}

// Zet de popup om de stad in te voeren aan/uit
function setPopupStatus(status) {
  if (status) {
    $("#inputPlaats").val('');
    $("#cityPopup").css('display', 'block');
    $("#inputPlaats").focus();
  } else {
    $("#cityPopup").css('display', 'none');
    $("#krijgLocatie").removeClass('error');
    $("#inputPlaats").removeClass('valid');
    $("#inputPlaats").removeClass('invalid');
  }
}

// Zet de plaats van het weer aan de hand van de input van de popup
function setCityByInput() {
  setCity($("#inputPlaats").val(), function(success) {
    if (success) {
      setValidCity(true);
      setTimeout(function() { setPopupStatus(false) }, 500);
    } else {
      setValidCity(false);
    }
  })
}

// Zet de plaats van het waar aan de hand van de locatie van het IP van de gebruiker
function getLocation() {
  $.getJSON('https://geolocation-db.com/json/', function(response) {
    var country = response.country_code != null ? ',' + response.country_code : '';
    setCity(response.city + country, function(success) {
      if (success) {
        $("#krijgLocatie").removeClass('error');
        setTimeout(function() { setPopupStatus(false) }, 500);
      } else $("#krijgLocatie").addClass('error');
    })
  })
}

// Functie om de stad in te stellen. De callback gooit false terug als het niet kan, anders true
function setCity(city, callback) {
  setLoader(true, false);

  $.getJSON('getData.php?plaats=' + city, function(response) {
    setLoader(false);
    // Als de response code niet 200 is OF als de response plaats overeenkomt met ',AB'
    if (response.code != '200' || /^(,\w\w)/.test(response.plaats)) {
      setValidCity(false);
      callback(false);
    } else {
      localStorage.setItem('plaats', city);
      setData(response);
      if (typeof callback == 'function') callback(true);
    }
  })
}

// Gooi terug naar de gebruiker of de plaats geldig is of niet.
function setValidCity(status) {
  if (status) {
    $("#inputPlaats").removeClass('invalid');
    $("#inputPlaats").addClass('valid');
  } else {
    $("#inputPlaats").removeClass('valid');
    $("#inputPlaats").addClass('invalid');
  }
}

// Zet of de site vandaag requests kan uitvoeren naar de server
function setBruikbaar(status) {
  if (status) {
    $("#onbruikbaar").css('display', 'none');
    $("body").css('overflow', 'hidden auto');
    // Laad data en interval weer in
    loadData();
    dataInterval = setInterval(loadData, 10 * 1000);
  }
  else {
    clearInterval(dataInterval);
    $("#onbruikbaar").css('display', 'flex');
    $("body").css('overflow', 'hidden hidden');
    // Update de timer en stel een interval in die de timer bijhoudt.
    setCooldown();
    errorInterval = setInterval(setCooldown, 1000);
  }
}

// Houdt de timer bij
function setCooldown() {
  var cDate = new Date();
  var nDate = Date.UTC(cDate.getFullYear(), cDate.getMonth(), cDate.getDate() + 1, 0, 0, 0);
  var difference = (nDate - cDate.getTime()) / 1000;

  if (difference <= 0) {
    clearInterval(errorInterval);
    setBruikbaar(true);
    return;
  }

  var uren = leadingZero(Math.floor(difference / (60 * 60)));
  var min = leadingZero(Math.floor((difference % (60 * 60)) / 60));
  var sec = leadingZero(Math.floor(difference % 60));

  $("#countdown").text(uren + ':' + min + ':' + sec);
}

// Zet nullen voor een getal als dat nodig is (9 -> 09, 19 -> 19)
function leadingZero(number) {
  return ('0' + number).slice(-2);
}

// Krijg een instelling
function getSetting(setting) {
  return localStorage.getItem('setting_' + setting);
}

// Stel een instelling in (+ update de setting in de Vue data)
function setSetting(setting, value) {
  localStorage.setItem('setting_' + setting, value);
  
  dataSite.settings[setting] = value;
}