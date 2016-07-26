<?php
/*
Plugin Name: Currency (CB RF) and Weather (by OpenWeatherMap)
Plugin URI: http://izevg.ru/blog/
Description: Виджет курса валют и погоды для города, указанного в настройках виждета. Поддерживается кэширование результатов запроса погоды.
Version: 1.0.0
Author: iZEvg
Author URI: http://izevg.ru
License: GPLv3
*/

# 2016, Eugene Zhukov, http://izevg.ru, <jack.zhukov@gmail.com>


class Currency_and_Weather_Widget extends WP_Widget {

    # Код валюты в архиве данных cbr.ru
    //$currency_code='R01235';
    private $dollar_code = 'R01235';
    private $euro_code = 'R01239';
    private $weather_API;
    private $cache_expires = 3600;
    private $cache_folder = WP_TEMP_DIR;

  function __construct() {
    parent::__construct(
      'currency_and_weather', // Base ID
      __("Currency and Weather", "text_domain"), // Name
      array('description' => __( 'Виджет курса валют и погоды для города, указанного в настройках виждета', "text_domain" ), $args)
    );
    wp_enqueue_style( "currency_and_weather_styles", WP_PLUGIN_URL . '/currency_and_weather/CAW_style.css' );

  }

  /**
   * Check if weather data is now cached
   * @param $file   filename for check
   * @return        cached file, or not (bool)
   */
  private function is_cached($file) {
	global $cache_folder, $cache_expires;
	$cachefile = $cache_folder . $file;
	$cachefile_created = (file_exists($cachefile)) ? @filemtime($cachefile) : 0;
	return ((time() - $cache_expires) < $cachefile_created);
  }

  /**
   * Read cached weather data from file
   * @param $file   filename for read from
   * @return        weather data string
   */
  private function read_weather_cache($file) {
	global $cache_folder;
	$cachefile = $cache_folder . $file;
	return file_get_contents($cachefile);
  }

  /**
   * Write current weather data to cache (file)
   * @param $filename   filename for write to
   * @param $content    data for writing
   */
  private function cache_weather_file($filename, $content) {
    global $cache_folder;
	$cachefile = $cache_folder . $file;
	$fp = fopen($cachefile, 'w');
	fwrite($fp, $out);
	fclose($fp);
  }

  /**
   * Get current weather data for city
   * @param $city_name      City name for weather request
   * @param $region         City region (two lowercase letters)
   * @param $weather_api    OpenWeatherMap API Key value
   * @return                Weather data in basic String format
   */
  private function get_current_weather($city_name, $region, $weather_api) {
      $result = "";
      $request_URI = "http://api.openweathermap.org/data/2.5/weather?q=$city_name,$region&units=metric&lang=$region&appid=$weather_api";
      $cache_file = "/wp_weather_cache.json";

      if ($this->is_cached($cache_file)) {
	         $result = $this->read_weather_cache($cache_file);
             exit();
      } else {
          if (!empty($weather_api)) {
              $result = file_get_contents($request_URI);
              $this->cache_weather_file($cache_file, $result);
          }
      }

      return $result;
  }

  /**
   * Encode weather data from basic String to valid JSON and then to array
   * @param $weather_string Raw weather data string
   * @return                Associate array with weather data
   */
  private function translate_weather_to_array($weather_string) {
      $valid_json = json_decode($weather_string, true);
      return $valid_json;
  }

  /**
   * Get currency value by it code from Central Bank of Russia
   * @param $currency_code  Currency code. All currencies available on http://www.cbr.ru/scripts/XML_val.asp?d=0
   * @param $date           (optional) Date for request currency value (not today, for example)
   * @return                Currency value on the last known moment or selected date
   */
  private function get_currency_value($currency_code, $date = "") {
      $result = "";
      $request_URI = (empty($date)) ? "http://www.cbr.ru/scripts/XML_daily.asp" : "http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$date;
      $raw_XML = file_get_contents($request_URI);
      $parsed_XML = new SimpleXMLElement($raw_XML);
      foreach ($parsed_XML->Valute as $valute) {
          switch($valute["ID"]) {
              case $currency_code:
                $result = $valute->Value;
                break;
              default:
                break;
          }
      }
      return $result;
  }

  // Here and below - standart widget register and use functions, check it at https://codex.wordpress.org/Widgets_API
  public function widget($args, $instance) {
      echo $args['before_widget'];
      $weather = $this->get_current_weather($instance['city'], $instance['region'], $instance['weather_api']);
      $weather = $this->translate_weather_to_array($weather);
      $weather_icon_uri = "http://openweathermap.org/img/w/";
      if ($instance['head_checkbox'] != "") {
          echo $args["before_title"] . apply_filters( 'widget_title', __('Курс валют и погода на ', 'text_domain') ) . apply_filters( 'widget_title', $date ) . $args["after_title"];
      }
      ?>
      <div id="currency-and-weather">
          <div id="weather">
              <?php if ($weather != "") {
                  ?>
                  <img class="weather-details" id="weather-icon" src="<?php echo $weather_icon_uri . $weather['weather'][0]['icon']. ".png"?>" alt="<?php echo __("Текущая погода в городе ", "text_domain") . $instance['city'] ?>">
                  <div class="weather-details">
                      <div id="weather-temperature" class="weather-info"><b><?php echo $weather['main']['temp'] ?> °C</b></div>
                      <div id="weather-description" class="weather-info"><b><?php echo $weather['weather'][0]['description']?></b></div>
                  </div>
                  <?php
              } else echo __('', 'text_') ?>
          </div>
          <div id="currency">
              <div id="dollar">
                  <div class="currency-name">USD</div>
                  <div class="currency-value"><b><?php echo $this->get_currency_value($this->dollar_code)?></b></div>
                  <div class="currency-dynamic"></div>
              </div>
              <div id="euro">
                  <div class="currency-name">EUR</div>
                  <div class="currency-value"><b><?php echo $this->get_currency_value($this->euro_code)?></b></div>
                  <div class="currency-dynamic"></div>
              </div>
          </div>
      </div>
      <?php
      echo $args['after_widget'];
  }

  public function form( $instance ) {
    $head_checkbox = $instance[ 'head_checkbox' ] ? 'true' : 'false';
    $weather_API = ! empty( $instance['weather_api'] ) ? $instance['weather_api'] : __( 'OpenWeatherMap API Key (обязательно!)', 'text_domain' );
    $city = ! empty( $instance['city'] ) ? $instance['city'] : __( 'Город для отображения погоды', 'text_domain' );
    $region = ! empty( $instance['region'] ) ? $instance['region'] : __( 'Регион (страна) города', 'text_domain' );
    ?>
    <p>
        <input class="checkbox" type="checkbox" <?php checked( $instance[ 'head_checkbox' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'head_checkbox' ); ?>" name="<?php echo $this->get_field_name( 'head_checkbox' ); ?>" />
        <label for="<?php echo esc_attr( $this->get_field_id( 'head_checkbox' ) ); ?>"><?php _e( esc_attr( __('Отображать заголовок', 'text_domain') ) ); ?></label><br><br>
        <label for="<?php echo esc_attr( $this->get_field_id( 'weather_api' ) ); ?>"><?php _e( esc_attr( __('OpenWeatherMap API Key:', 'text_domain') ) ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'weather_api' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'weather_api' ) ); ?>" type="text" value="<?php echo esc_attr( $weather_API ); ?>">
        <label for="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>"><?php _e( esc_attr( __('Город:', 'text_domain') ) ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city' ) ); ?>" type="text" value="<?php echo esc_attr( $city ); ?>">
        <label for="<?php echo esc_attr( $this->get_field_id( 'region' ) ); ?>"><?php _e( esc_attr( __('Регион:', 'text_domain') ) ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'region' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'region' ) ); ?>" type="text" value="<?php echo esc_attr( $region ); ?>">
		</p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['city'] = ( ! empty( $new_instance['city'] ) ) ? strip_tags( $new_instance['city'] ) : '';
        $instance['region'] = ( ! empty( $new_instance['region'] ) ) ? strip_tags( $new_instance['region'] ) : '';
        $instance['head_checkbox'] = ( ! empty( $new_instance['head_checkbox'] ) ) ? strip_tags( $new_instance['head_checkbox'] ) : '';
        $instance['weather_api'] = ( ! empty( $new_instance['weather_api'] ) ) ? strip_tags( $new_instance['weather_api'] ) : '';
        // Update weather cache file
        unlink(WP_TEMP_DIR . "/wp_weather_cache.json");

		return $instance;
	}

}
function register_weather_and_currency_widget() {
    register_widget( 'Currency_and_Weather_Widget' );
}
add_action( 'widgets_init', 'register_weather_and_currency_widget' );
?>
