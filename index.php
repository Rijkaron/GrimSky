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
   <!-- Probeer eerst CDN's, misschien staan die nog gecached bij de gebruiker. -->
   <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/vue@2.6.11"></script>
   <!-- Als CDN's niet beschikbaar zijn pak ze dan maar van hier -->
   <script>typeof $ === "undefined" ? document.write('<script src="./content/lib/jQuery-3.4.1.min.js"><\/script>') : ''</script>
   <script>typeof Vue === "undefined" ? document.write('<script src="./content/lib/Vue-2.6.11.min.js"><\/script>') : ''</script>
   <?php
      // include language files
      foreach (glob('./content/lang/*.lang.js') as $langFile) {
         echo "<script src=\"$langFile\"></script>";
      }
   ?>
   <script src="./content/script.js"></script>
   <link rel="stylesheet" href="./content/style.css">
   <title>GrimSky | Home</title>
</head>

<body>
   <div id="loader" style="display: none"><span></span></div>
   <!-- Container voor Vue -->
   <div id="container">
      <header style="--color: #fff">
         <a id="logo" href="javascript:loadPage('home')">
            <p>GrimSky</p>
         </a>
         <nav>
            <a @click="sluitMobileNav" href="javascript:loadPage('home')">
               <span>{{ 'header.current' | lang }}</span>
            </a>
            <a @click="sluitMobileNav" href="javascript:loadPage('voorspelling')">
               <span>{{ 'header.forecast' | lang }}</span>
            </a>
            <a @click="sluitMobileNav" href="javascript:loadPage('instellingen')" class="instellingen">
               <img src="./content/icons/settings.svg" alt="Instellingen">
               <span>{{ 'header.settings' | lang }}</span>
            </a>
            <a class="openPopupMobile" href="javascript:setPopupStatus(true)"><span>{{ 'header.change_location' | lang }}</span></a>
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
                     <span>{{ 'frontpage.welcome_message' | getWelcomeMessage | lang }}</span>
                     <br>
                     {{ 'frontpage.it_is' | lang }} <b>{{ huidig.temperatuur | temperatuurEenheid }}</b>
                     <br>
                     <small style="font-size: 1.75rem;">{{ 'frontpage.in' | lang }} {{ city[0] }}</small>
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
               <p>{{ 'frontpage.lastUpdate' | lang }} {{ laatsteUpdate | tijd }}</p>
            </section>
            <section id="meerWeer">
               <div>
                  <fieldset id="weerbericht">
                     <legend>{{ 'more_weather.more_weather' | lang }}</legend>
                     <div v-if="city[1] == 'NL'">
                        <h2>{{ verhaal.titel | capitalize }}</h2>
                        <h3>{{ 'more_weather.summary_by' | lang }} <b>{{ verhaal.auteur }}</b></h3>
                        <!-- v-html omdat er nog weleens htmlentities worden gebruikt in de buienradar api -->
                        <p v-html="verhaal.samenvatting"></p>
                     </div>
                     <h4><span>{{ 'other.days_relative.0' | lang }}</span></h4>
                     <div class="voorspellingIcons">
                        <div>
                           <img src="./content/icons/thermometer.svg" alt="Temperatuur">
                           <span class="tooltip" :data-tooltip="'other.tooltips.temperature' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].minTemperatuur | temperatuurEenheid }} / {{ dagen[0].maxTemperatuur | temperatuurEenheid }}</p>
                        </div>
                        <div>
                           <img src="./content/icons/wind.svg" alt="Wind">
                           <span class="tooltip" :data-tooltip="'other.tooltips.wind' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].windKracht }}{{ dagen[0].windRichting }} ({{ dagen[0].windSnelheid | snelheidEenheid }})</p>
                        </div>
                        <div>
                           <img src="./content/icons/waterdrop.svg" alt="Regen">
                           <span class="tooltip" :data-tooltip="'other.tooltips.precipitation' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[0].neerslagHoeveelheid }}mm / {{ dagen[0].neerslagKans }}%</p>
                        </div>
                     </div>
                     <h4><span>{{ 'other.days_relative.1' | lang }}</span></h4>
                     <div class="voorspellingIcons">
                        <div>
                           <img src="./content/icons/thermometer.svg" alt="Temperatuur">
                           <span class="tooltip" :data-tooltip="'other.tooltips.temperature' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].minTemperatuur | temperatuurEenheid }} / {{ dagen[1].maxTemperatuur | temperatuurEenheid }}</p>
                        </div>
                        <div>
                           <img src="./content/icons/wind.svg" alt="Wind">
                           <span class="tooltip" :data-tooltip="'other.tooltips.wind' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].windKracht }}{{ dagen[1].windRichting }} ({{ dagen[1].windSnelheid | snelheidEenheid }})</p>
                        </div>
                        <div>
                           <img src="./content/icons/waterdrop.svg" alt="Regen">
                           <span class="tooltip" :data-tooltip="'other.tooltips.precipitation' | lang">
                              <img src="./content/icons/info.svg" alt="i">
                           </span>
                           <p>{{ dagen[1].neerslagHoeveelheid }}mm / {{ dagen[1].neerslagKans }}%</p>
                        </div>
                     </div>
                     <!-- Filters werken niet in v-html -->
                     <p v-html="$options.filters.lang('more_weather.further_forecast', [&quot;javascript:loadPage('voorspelling')&quot;])"></p>
                  </fieldset>
               </div>
               <fieldset id="weerRadar">
                  <legend>{{ 'more_weather.weatherradar' | lang }}</legend>
                  <img src="https://image.buienradar.nl/2.0/image/animation/RadarMapRainNL?height=512&width=500&extension=gif&renderBackground=True&renderBranding=False&renderText=True&history=3&forecast=3&skip=1" :alt="$options.filters.lang('more_weather.weatherradar_error')">
               </fieldset>
            </section>
         </article>
         <article id="voorspelling">
            <fieldset id="voorspellingDagen">
               <legend>{{ 'forecasts.daily_forecast' | lang }}</legend>
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
               <legend>{{ 'forecasts.hourly_forecast' | lang }}</legend>
               <p class="scrollTip">{{ 'forecasts.scroll_tip' | lang }}</p>
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
               <p v-html="$options.filters.lang('forecasts.more_less_hours', [&quot;javascript:loadPage('instellingen')&quot;])">Meer of minder uren? Ga naar instellingen</a>.</p>
            </fieldset>
         </article>
         <article id="instellingen">
            <fieldset id="settings">
               <legend>{{ 'settings.settings' | lang }}</legend>
               <div>
                  <p>{{ 'settings.info' | lang }}</p>
                  <div>
                     <button onclick="setPopupStatus(true)">{{ 'settings.change_city' | lang }}</button>
                  </div>
                  <div>
                     <p>{{ 'settings.language' | lang }}</p>
                     <div class="conSelect">
                        <select data-setting="taal" @change="updateSetting($event)">
                           <option v-for="t in talen" :value="t[0]" :selected="t[0] == settings.taal">{{ t[1] }}</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
                  <div>
                     <p>{{ 'settings.temperature' | lang }}</p>
                     <div class="conSelect">
                        <select data-setting="temperatuurEenheid" @change="updateSetting($event)">
                           <option v-for="unit in ['Celsius', 'Fahrenheit', 'Kelvin']" :value="unit.charAt(0)" :selected="unit.charAt(0) == settings.temperatuurEenheid">{{ unit }}</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
                  <div>
                     <p>{{ 'settings.speed' | lang }}</p>
                     <div class="conSelect">
                        <select data-setting="snelheidEenheid" @change="updateSetting($event)">
                           <option value="kpu" :selected="'kpu' == settings.snelheidEenheid">{{ 'other.units.speed.kpu' | lang }}</option>
                           <option value="ms" :selected="'ms' == settings.snelheidEenheid">{{ 'other.units.speed.ms' | lang }}</option>
                           <option value="mph" :selected="'mph' == settings.snelheidEenheid">{{ 'other.units.speed.mpu' | lang }}</option>
                           <option value="kt" :selected="'kt' == settings.snelheidEenheid">{{ 'other.units.speed.kt' | lang }}</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
                  <div>
                     <p>{{ 'settings.frecuency_hours' | lang }}</p>
                     <div class="conSelect">
                        <select data-setting="ElkeXUren" @change="updateSetting($event)">
                           <option value="1" :selected="1 == settings.ElkeXUren">{{ 'settings.every_hour' | lang }}</option>
                           <option v-for="number in [2,3,4,5,6,7,8]" :value="number" :selected="number == settings.ElkeXUren">{{ 'settings.every_x_hours' | lang([], [number]) }}</option>
                        </select>
                        <div></div>
                     </div>
                  </div>
               </div>
               <p id="settingChange" :style="{ transform: 'scale(0)' }">{{ 'settings.saved' | lang }}</p>
            </fieldset>
            <fieldset>
               <legend>{{ 'about.about' | lang }}</legend>
               <p v-html="$options.filters.lang('about.text')"></p>
               <p v-html="$options.filters.lang('about.project_info', [&quot;https://darksky.net/&quot;,&quot;https://www.buienradar.nl/&quot;,&quot;https://opencagedata.com/&quot;,&quot;http://geolocation-db.com/&quot;,&quot;https://mymemory.translated.net/&quot;,&quot;https://jquery.com/&quot;,&quot;https://vuejs.org/&quot;,&quot;https://github.com/erikflowers/weather-icons&quot;,&quot;https://github.com/Rijkaron/GrimSky&quot;])"></p>
            </fieldset>
         </article>
      </main>
      <footer>
         <strong>GrimSky</strong>
         <p>{{ 'footer.credits' | lang }}</p>
      </footer>
      <div style="display: none" id="cityPopup" onclick="setPopupStatus(false)">
         <div @click="stopPropagation($event)">
            <p style="font-size: 1.1rem;color: #000;">{{ 'city_popup.info' | lang }}</p>
            <input @keydown="testInputKeydown($event)" id="inputPlaats" type="text">
            <p>{{ 'city_popup.city_not_found' | lang }}</p>
            <div>
               <button class="bevestigen" onclick="setCityByInput()">{{ 'city_popup.submit' | lang }}</button>
               <button class="annuleren" onclick="setPopupStatus(false)">{{ 'city_popup.cancel' | lang }}</button>
            </div>
            <fieldset><legend>{{ 'city_popup.or' | lang }}</legend></fieldset>
            <button id="krijgLocatie" onclick="getLocation()">{{ 'city_popup.current_location' | lang }}</button>
            <p>{{ 'city_popup.current_location_error' | lang }}</p>
         </div>
      </div>
      <div id="onbruikbaar" style="display: none">
         <h1>{{ 'cooldown.info' | lang }}</h1>
         <p id="countdown"></p>
      </div>
   </div>
</body>

</html>