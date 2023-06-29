<?php
/**
 * Plugin Name: Analyze loading speed
 * Description: Analyze loading speed of WP for debug purposes
 * Version:     1.0.10
 * Author:      Me
 */


class WPSpeedAnalyzer{
	
	private $times = array();
	private $memory = array();
	private $tests = array();
	private $current = '';
	
	private $events = array(
			'muplugins_loaded'				=> '',
			'registered_taxonomy'			=> 'Taxonomies loaded',
			'registered_post_type'			=> 'Post types registered',
			'plugins_loaded'				=> 'Plugins Loaded',
			'after_setup_theme'				=> 'Theme setup',
			'set_current_user'				=> 'User and stuff',
			'init'							=> 'WP initalized',
			'wp_register_sidebar_widget'	=> 'Widgets and stuff',
			'wp_loaded'						=> 'WP Loaded',
			'send_headers'					=> 'Headers sent',
			'parse_query'					=> 'Main query vars',
			'template_redirect'				=> '',
			'wp_default_styles'				=> 'Scripts and styles init',
			'add_admin_bar_menus'			=> 'Admin bar',
			'get_header'					=> 'Before header',
			'wp_head'						=> 'WP Head',
			'wp_print_scripts'				=> 'Scripts and styles output',
			'pre_get_search_form'			=> 'Sidebar, searcha nd stuff',
			'loop_start'					=> 'Loop start',
			'the_post'						=> 'The Post',
			'loop_end'						=> 'Loop end',
			'wp_meta'						=> 'WP meta',
			'get_footer'					=> 'A lot of stuff before footer'
		);
	private $custom = array(
		'acf/init' => 'ACF init'
	);
	
	public function __construct(){
		$this->times['start'] = microtime(true);
		$this->memory['start'] = memory_get_usage();
		
		foreach ($this->events as $hook => $label){
			add_action($hook,array($this,'log_time'));
			add_action($hook,array($this,'log_memory'));
		}
		foreach ($this->custom as $hook => $label){
			add_action($hook,array($this,'log_time'),1);
			add_action($hook,array($this,'log_time'),100);
		}
		
		add_action('wp_footer',array($this,'print_times'));
		
		// copy plugin to mu-plugins folder
		if($_GET['mu_plugin'] == 'write'){
			$this->write_mu_plugin();
		}
		// remove plugin from mu-plugins folder
		if($_GET['mu_plugin'] == 'remove'){
			$this->remove_mu_plugin();
		}
		
		register_shutdown_function(array($this, 'shutdown')); 
	}
	
	public function log_time(){
		$hook = current_filter();
		$this->current = $hook;
		if(isset($this->events[$hook])){
			$this->times[$hook] = microtime(true);
		}
		else {
			$this->times['some_other_hooks'][$hook] = microtime(true);
		}
	}
	
	public function log_memory(){
		$hook = current_filter();
		
		if(isset($this->events[$hook])){
			$this->memory[$hook] = memory_get_usage();
		}
		else {
			$this->memory['some_other_hooks'][$hook] = memory_get_usage();
		}
	}
	
	public function shutdown(){
		$this->print_times(true);
		$this->print_memory(true);
	}
	
	public function print_times($loud = false){
		$nl = '
';
		if(!$loud){
			echo $nl.'<!-- WP Loading times: '.$nl.$nl;
		}
		else {
			echo $nl.'<h2> WP Loading times: </h2><pre>'.$nl.$nl;
		}
			
		$lasttime = $this->times['start'];
		foreach($this->times as $hook=>$microtime){
			if(is_array($microtime)){
				foreach($microtime as $subhook => $sub_time){
					$label = (isset($this->events[$subhook]) && $this->events[$subhook]) ? $this->events[$subhook] : $subhook;
					$time = ($sub_time - $lasttime)/1000;
					echo '- - '.$label.': '.number_format($time,5).'s'.$nl;
				}
			}
			else {
				$label = (isset($this->events[$hook]) && $this->events[$hook]) ? $this->events[$hook] : $hook;
				$time = ($microtime - $lasttime)/1000;
				echo '- '.$label.': '.number_format($time,5).'s'.$nl;
			}
			//$lasttime = $microtime;
		}
		$microtime = microtime(true);
		$time = ($microtime - $lasttime)/1000;
		echo '- End: '.number_format($time,5).'s'.$nl;
		$time = ($microtime - $this->times['start'])/1000;
		echo '- Total: '.number_format($time,5).'s'.$nl.$nl;
		
		if(!$loud){
			echo '-->'.$nl;
		}
		else {
			echo '</pre>';
		}
	}
	
	public function print_memory($loud = false){
		$nl = '
';
		if(!$loud){
			echo $nl.'<!-- WP memory usage: '.$nl.$nl;
		}
		else {
			echo $nl.'<h2> WP memory usage: </h2><pre>'.$nl.$nl;
		}
			
		$lasttime = $this->memory['start'];
		foreach($this->memory as $hook=>$memory){
			$label = (isset($this->events[$hook]) && $this->events[$hook]) ? $this->events[$hook] : $hook;
			$memorymb = $memory/1048576;
			echo '- '.$label.': '.number_format($memorymb,5).'Mb'.$nl;
			//$lasttime = $microtime;
		}
		$memorymb = memory_get_peak_usage()/1048576;
		echo 'Peak usage: '.number_format($memorymb,5).'Mb'.$nl;
		//$time = ($microtime - $this->times['start'])/1000;
		//echo '- Total: '.number_format($time,5).'s'.$nl.$nl;
		
		if(!$loud){
			echo '-->'.$nl;
		}
		else {
			echo '</pre>';
		}
	}
	
	public function test_db_speed(){
		
	}
	
	public function write_mu_plugin(){
		if(strpos(__DIR__,'mu-plugins')){
			//return;
		}
		$file = fopen(__DIR__.'/analyze-loading.php','r') or Die('cannot open file');
		$content = fread($file, filesize(__DIR__.'/analyze-loading.php'));
		fclose($file);
		$content = str_replace('WPSpeedAnalyzer', 'WPSpeedAnalyzer2', $content);
		$file = fopen(__DIR__.'/../mu-plugins/analyze-loading2.php','w');
		fwrite($file, $content);
		fclose($file);
	}
	
	public function remove_mu_plugin(){
		if(strpos(__DIR__,'mu-plugins')){
			return;
		}
		$file = fopen(__DIR__.'/../mu-plugins/analyze-loading.php','r');
		$content = fread($file, filesize(__DIR__.'/analyze-loading.php'));
		fclose($file);
		$file = fopen(__DIR__.'/../mu-plugins/analyze-loading2.php','w');
		fwrite($file, $content);
		fclose($file);
	}
}

$WPSpeedAnalyzer = new WPSpeedAnalyzer();