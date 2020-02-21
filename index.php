<!DOCTYPE html>
<html lang="nl" dir="ltr">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- Verschillende favicons door realfavicongenerator.net -->
   <link rel="apple-touch-icon" sizes="180x180" href="./content/icons/favicon/apple-touch-icon.png">
   <link rel="icon" type="image/png" sizes="32x32" href="./content/icons/favicon/favicon-32x32.png">
   <link rel="icon" type="image/png" sizes="16x16" href="./content/icons/favicon/favicon-16x16.png">
   <link rel="manifest" href="./content/icons/favicon/site.webmanifest">
   <link rel="mask-icon" href="./content/icons/favicon/safari-pinned-tab.svg" color="#5bbad5">
   <link rel="shortcut icon" href="/content/icons/favicon/favicon.ico">
   <meta name="msapplication-TileColor" content="#2b5797">
   <meta name="msapplication-TileImage" content="./content/icons/favicon/mstile-144x144.png">
   <meta name="msapplication-config" content="./content/icons/favicon/browserconfig.xml">
   <meta name="theme-color" content="#ffffff">

   <meta name="description" content="Yet Another Weather Website">
   <meta name="keywords" content="GrimSky, Weather, Weer">
   <meta name="author" content="Aron, Cor">
   <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/vue@2.6.11"></script>
   <script>typeof jQuery === "undefined" ? document.write('<script src="./content/lib/jQuery-3.4.1.min.js"><\/script>') : ''</script>
   <script>typeof Vue === "undefined" ? document.write('<script src="./content/lib/Vue-2.6.11.min.js"><\/script>') : ''</script>
   <script src="./content/script.js"></script>
   <link rel="stylesheet" href="./content/style.css">
   <title>GrimSky | Home</title>
</head>

<body>
   <div id="loader" style="display: none"><span></span></div>
   <div id="container">
      <header style="--color: #fff">
         <a id="logo" href="javascript:loadPage('home')">
            <p>GrimSky</p>
         </a>
         <nav>
            <a @click="sluitMobileNav" href="javascript:loadPage('home')">
               <span>Huidig</span>
            </a>
            <a @click="sluitMobileNav" href="javascript:loadPage('voorspelling')">
               <span>Voorspelling</span>
            </a>
            <a @click="sluitMobileNav" href="javascript:loadPage('instellingen')" class="instellingen">
               <img src="./content/icons/settings.svg" alt="Instellingen">
               <span>Instellingen</span>
            </a>
            <a class="openPopupMobile" href="javascript:setPopupStatus(true)"><span>Verander plaats</span></a>
         </nav>
         <button class="openPopup" type="button" onclick="setPopupStatus(true)">{{ city[0] }}</button>
         <div id="hamburger" onclick="toggleMobileNav()">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
         </div>
      </header>
      <main>
         <article id="huidig">
            <section id="frontpage">
               <div id="welkom-container">
                  <img :src="huidig.icon | path" :alt="huidig.icon">
                  <p>
                     <span>{{ welkomBericht }}</span>
                     <br>
                     Het is <b>{{ huidig.temperatuur | temperatuurEenheid }}</b>
                  </p>
               </div>
               <div id="windmolen-container">
                  <p>{{ huidig.windKracht }}{{ huidig.windRichting }}</p>
                  <!-- Original windmill by @msvaljek -->
                  <div id="windmolen">
                     <div id="windmolen-paal"></div>
                     <div id="windmolen-motor"></div>
                     <div id="windmolen-wieken">
                        <div></div>
                        <div></div>
                        <div></div>
                     </div>
                  </div>
               </div>
               <a id="scroll" href="javascript:window.scroll({top: $(window).height(),behavior: 'smooth'})"><span></span></a>
               <p>Laatste update om {{ laatsteUpdate | tijd }}</p>
            </section>
            <section id="meerWeer">
               <div>
                  <fieldset id="weerbericht">
                     <legend>Wat doet het weer?<span v-if="city[1] != 'NL'" style="font-size: 0.9rem;">&nbsp;(in Nederland)</span></legend>
                     <h2>{{ verhaal.titel | capitalize }}</h2>
                     <h3>Door <b>{{ verhaal.auteur }}</b></h3>
                     <!-- v-html omdat er nog weleens htmlentities worden gebruikt in de buienradar api -->
                     <p v-html="verhaal.samenvatting"></p>
                     <h4><span>Vandaag</span></h4>
                     <div class="voorspellingIcons">
                        <div>
                           <img src="./content/icons/thermometer.svg" alt="Temperatuur">
                           <span class="tooltip" data-tooltip="De minimumtemperatuur is de laagste temperatuur die 's ochtends vroeg gemeten wordt (rond zonsopgang)&#13;&#10;&#13;&#10;De maximumtemperatuur is de hoogste temperatuur die 's middags, meestal om 3 uur (winter) of 4/5 uur (zomer) gemeten wordt.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].minTemperatuur | temperatuurEenheid }} / {{ dagen[0].maxTemperatuur | temperatuurEenheid }}</p>
                        </div>
                        <div>
                           <img src="./content/icons/wind.svg" alt="Wind">
                           <span class="tooltip" data-tooltip="De windkracht in beaufort is hoe snel de wind overdag waait op een open gebied.&#13;&#10;&#13;&#10;De windrichting is de windstreek waar de wind vandaan komt.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].windKracht }}{{ dagen[0].windRichting }} ({{ dagen[0].windSnelheid | snelheidEenheid }})</p>
                        </div>
                        <div>
                           <img src="./content/icons/waterdrop.svg" alt="Regen">
                           <span class="tooltip" data-tooltip="De hoeveelheid neerslag wordt gegeven in millimeters. 1mm komt overeen met 1 liter op 1 vierkante meter.&#13;&#10;Als de neerslag in vaste vorm uit de lucht valt wordt het smeltwater gemeten. 1mm water komt dan overeen met 1cm sneeuw&#13;&#10;&#13;&#10;Aan de neerslagkans is te zien hoe groot de kans is dat er neerslag komt gedurende de 24 uur.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].neerslagHoeveelheid }}mm / {{ dagen[0].neerslagKans }}%</p>
                        </div>
                     </div>
                     <h4><span>Morgen</span></h4>
                     <div class="voorspellingIcons">
                        <div>
                           <img src="./content/icons/thermometer.svg" alt="Temperatuur">
                           <span class="tooltip" data-tooltip="De minimumtemperatuur is de laagste temperatuur die 's ochtends vroeg gemeten wordt (rond zonsopgang)&#13;&#10;&#13;&#10;De maximumtemperatuur is de hoogste temperatuur die 's middags, meestal om 3 uur (winter) of 4/5 uur (zomer) gemeten wordt.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].minTemperatuur | temperatuurEenheid }} / {{ dagen[1].maxTemperatuur | temperatuurEenheid }}</p>
                        </div>
                        <div>
                           <img src="./content/icons/wind.svg" alt="Wind">
                           <span class="tooltip" data-tooltip="De windkracht in beaufort is hoe snel de wind overdag waait op een open gebied.&#13;&#10;&#13;&#10;De windrichting is de windstreek waar de wind vandaan komt.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].windKracht }}{{ dagen[1].windRichting }} ({{ dagen[1].windSnelheid | snelheidEenheid }})</p>
                        </div>
                        <div>
                           <img src="./content/icons/waterdrop.svg" alt="Regen">
                           <span class="tooltip" data-tooltip="De hoeveelheid neerslag wordt gegeven in millimeters. 1mm komt overeen met 1 liter op 1 vierkante meter.&#13;&#10;Als de neerslag in vaste vorm uit de lucht valt wordt het smeltwater gemeten. 1mm water komt dan overeen met 1cm sneeuw&#13;&#10;&#13;&#10;Aan de neerslagkans is te zien hoe groot de kans is dat er neerslag komt gedurende de 24 uur.">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].neerslagHoeveelheid }}mm / {{ dagen[1].neerslagKans }}%</p>
                        </div>
                     </div>
                     <p>Klik <a href="javascript:loadPage('voorspelling')">hier</a> om het verdere weerbericht te zien</p>
                  </fieldset>
               </div>
               <fieldset id="weerRadar">
                  <legend>Weerradar</legend>
                  <img src="https://image.buienradar.nl/2.0/image/animation/RadarMapRainNL?height=512&width=500&extension=gif&renderBackground=True&renderBranding=False&renderText=True&history=3&forecast=3&skip=1" alt="Fout met het inladen van de radarbeelden van buienradar">
               </fieldset>
            </section>
         </article>
         <article id="voorspelling">
            <fieldset id="voorspellingDagen">
               <legend>Dagelijkse voorspelling</legend>
               <div v-for="dag in dagen">
                  <img :src="dag.icon | path" :alt="dag.icon">
                  <p class="dag">{{ dag.timestamp | dag }}</p>
                  <p class="datum">{{ dag.timestamp | datum }}</p>
                  <p class="korteDatum">{{ dag.timestamp | dag(true) }} {{ dag.timestamp | datum }}</p>
                  <div>
                     <p class="item minTemperature">{{ dag.minTemperatuur | temperatuurEenheid }}</p>
                     <p class="item maxTemperature">{{ dag.maxTemperatuur | temperatuurEenheid }}</p>
                  </div>
                  <p class="item neerslagKans">{{ dag.neerslagKans }}%</p>
                  <p class="item neerslagHoeveelheid">{{ dag.neerslagHoeveelheid }}mm</p>
                  <p class="item wind">{{ dag.windKracht }}{{ dag.windRichting }}</p>
               </div>
            </fieldset>
            <fieldset id="voorspellingUren">
               <legend>Uurlijkse voorspelling</legend>
               <p class="scrollTip">Scroll horizontaal om meer uren te zien</p>
               <div>
                  <div v-for="(uur, index) in uren" v-if="index % settings.ElkeXUren == 0" :data-verschilDagen="uur.timestamp | verschilDagen">
                     <p class="tijd">{{ uur.timestamp | uur }}</p>
                     <img :src="uur.icon | path" :alt="uur.icon">
                     <p class="item Temperature">{{ uur.temperatuur | temperatuurEenheid }}</p>
                     <img class="neerslag" src="./content/icons/waterdrop.svg" :alt="uur.neerslagHoeveelheid" :data-neerslagLevel="uur.neerslagHoeveelheid | neerslagLevel">
                     <div class="wind item" :style="{ transform: 'rotate(' + uur.windGraden + 'deg)' }">
                        <p :style="{ transform: 'rotate(' + -uur.windGraden + 'deg)' }">{{ uur.windKracht }}</p>
                     </div>
                  </div>
               </div>
               <p>Meer of minder uren? Ga naar <a href="javascript:loadPage('instellingen')">instellingen</a>.</p>
            </fieldset>
         </article>
         <article id="instellingen">
            <fieldset id="settings">
               <legend>Instellingen</legend>
               <div>
                  <p>Met deze instellingen kunt u GrimSky helemaal naar uw wensen instellen.</p>
                  <div>
                     <p>Welke eenheid voor <i>temperatuur</i> wilt u gebruiken?</p>
                     <div class="conSelect">
                        <select data-setting="temperatuurEenheid" @change="updateSetting($event)">
                           <option v-for="unit in ['Celsius', 'Fahrenheit', 'Kelvin']" :value="unit.charAt(0)" :selected="unit.charAt(0) == settings.temperatuurEenheid">{{ unit }}</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
                  <div>
                     <p>Welke eenheid voor <i>snelheid</i> wilt u gebruiken?</p>
                     <div class="conSelect">
                        <select data-setting="snelheidEenheid" @change="updateSetting($event)">
                           <option value="kmu" :selected="'kmu' == settings.snelheidEenheid">Kilometer per uur</option>
                           <option value="ms" :selected="'ms' == settings.snelheidEenheid">Meter per seconde</option>
                           <option value="mph" :selected="'mph' == settings.snelheidEenheid">Mijlen per uur</option>
                           <option value="kt" :selected="'kt' == settings.snelheidEenheid">Knopen</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
                  <div>
                     <p>Wat is de frequentie van de urenvoorspelling?</p>
                     <div class="conSelect">
                        <select data-setting="ElkeXUren" @change="updateSetting($event)">
                           <option value="1" :selected="1 == settings.ElkeXUren">Elk uur</option>
                           <option v-for="number in [2,3,4,5,6,7,8]" :value="number" :selected="number == settings.ElkeXUren">Elke {{ number }} uur</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
               </div>
               <p id="settingChange" :style="{ transform: 'scale(0)' }">Uw instellingen zijn opgeslagen.</p>
            </fieldset>
            <fieldset>
               <legend>Over GrimSky</legend>
               <p>
                  GrimSky is gemaakt voor het vak informatica. Deze weersite is ertoe in staat om het weer tot 48 uur uurlijks en 7 dagen dagelijks in de toekomst te laten zien.
                  <br>
                  Om het weer te krijgen kunt u de plaats intypen of simpelweg uw locatie automatisch laten bepalen. Kortom: alles wat een goede weerwebsite te bieden moet hebben.
                  <br><br>
                  Een droge dag gewenst,<br>&nbsp;&nbsp;De makers van GrimSky
                  <br><br>
                  Voor dit project zijn de APIs van <a href="https://darksky.net/" target="_blank">DarkSky</a>, <a href="https://www.buienradar.nl/" target="_blank">Buienradar</a>, <a href="https://opencagedata.com/" target="_blank">OpenCage</a> en <a href="http://geolocation-db.com/" target="_blank">Geolocation DB</a> gebruikt.
                  <br>
                  Daarnaast zijn de JavaScript libaries <a href="https://jquery.com/" target="_blank">jQuery</a> en <a href="https://vuejs.org/" target="_blank">Vue</a> gebruikt.
                  <br>
                  De weericoontjes komen van <a href="https://github.com/erikflowers/weather-icons">erikflowers</a>.
               </p>
               <p>De code van deze website is beschikbaar op <a href="https://github.com/Rijkaron/GrimSky" target="_blank">GitHub</a> onder de MIT licentie</p>
            </fieldset>
         </article>
      </main>
      <footer>
         <strong>GrimSky</strong>
         <p>Gemaakt door Aron en Cor</p>
      </footer>
   </div>
   <div style="display: none" id="cityPopup">
      <div>
         <p style="font-size: 1.1rem;">Voer je plaats in:</p>
         <input id="inputPlaats" type="text">
         <p>Plaats is niet gevonden.</p>
         <div>
            <button class="bevestigen" onclick="setCityByInput()">Bevestigen</button>
            <button class="annuleren" onclick="setPopupStatus(false)">Annuleren</button>
         </div>
         <fieldset><legend>OF</legend></fieldset>
         <button id="krijgLocatie" onclick="getLocation()">Gebruik uw huidige locatie</button>
         <p>Er ging iets fout met het ophalen van uw locatie, probeer uw plaats handmatig in te voeren.</p>
      </div>
   </div>
   <div id="onbruikbaar" style="display: none">
      <h1>GrimSky is weer te gebruiken over</h1>
      <p id="countdown"></p>
   </div>
</body>

</html>