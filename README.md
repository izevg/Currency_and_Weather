= Currency (CB RF) and Weather Widget =

== Description ==
Simple WordPress widget for display weather for selected city (with dynamic weather icon) from OpenWeatherMap, and USD-EUR currency from Central Bank of Russia.

== CSS Customization ==
HTML representation of widget has next structure:

-- div#currency-and-weather
 | -- div#weather
 |  | -- img#weather-icon.weather-details
 |  | -- div.weather-details
 |  |  | -- div#weather-temperature.weather-info
 |  |  | -- div#weather-description.weather-info
 | -- div#currency
 |  | -- div#dollar
 |  |  | -- div.currency-name
 |  |  | -- div.currency-value
 |  |  | -- div.currency-dynamic
 |  | -- div#euro
 |  |  | -- div.currency-name
 |  |  | -- div.currency-value
 |  |  | -- div.currency-dynamic

How you see, all elements are fully customizable.
DON'T edit the plugin CSS file. Write your own styles in custom CSS file (with native WordPress "Custom styles" function, for example).

== TODO ==
 * Add caching time setting
 * Add "Select currencies" ability
 * Translate widget to English (with native text_domain entries)
 * Add more weather info to widget
