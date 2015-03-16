<?php
// Copyright 2014-present kjenney. All Rights Reserved.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

/**
 * Selenium WebDriver API acccessed via PHP-WebDriver
 * Input is XML of steps to follow on site
 * Ouputs as XML unless database is specified in config
 */
class Miner {
	use Configurator;
	
	public $driver, $source, $site, $configarray, $steps, $elements, $profile, $verbose;
	
	public function __construct($verbose="false",$host="localhost",$port="4444") {
		// Set verbose mode - for development testing
		$this->verbose = $verbose;
		
		// Get the configuration from Configurator
		$this->configarray = $this->getConfig();
		$this->steps = isset($this->configarray['steps']) ? $this->configarray['steps'] : null;
		
		// Require steps now - everything will be performed thru steps
		if($this->steps==NULL) {
			return "Nothing to do. Check your configuration. Steps are required.";
		}	
		
		$this->elements = isset($this->configarray['elements']) ? $this->configarray['elements'] : null;
		if(isset($this->configarray['site'])) {
			$this->site = $this->configarray['site'];
		} else { return "Missing site. Check your config XML and correct."; }
			
		$dhost = 'http://' . $host . ':' . $port . '/wd/hub';
		$capabilities = DesiredCapabilities::firefox();
			
		// Build Firefox Profile
		// Additional Options available in Config
		$this->profile = new FirefoxProfile();
		if(isset($this->configarray['firefoxprofile'])) {
			$firefox_config = $this->configarray['firefoxprofile'];
			$this->generateProfile($firefox_config);
		}
		$this->logverbose("Firefox Profile",print_r($this->profile,true));
	
		$capabilities->setCapability(FirefoxDriver::PROFILE, $this->profile);
		
		try {
			$this->driver = RemoteWebDriver::create($dhost, $capabilities, 5000);
		} catch (Exception $e) {
			die("Please start WebDriver\n");
		}
			
		// Start by going to site specified in config
		$this->driver->get($this->site);
	}

	public function __destruct() {
       	$this->driver->quit();
   	}
 
	/**
 	* Verbose logging
 	* For development testing
 	* Logs to file with data/time stamps
 	*/      
	public function logverbose($type,$data) {
		// Create log file
		$filename = "log.log";
		$datetime = date("F j, Y, G:i:s");
		if($this->verbose=="true") {
			$logfile = fopen($filename,'a') or die("Unable to open file!");
			$message = $datetime . "\n----------------" . $type . "-------------------\n" . $data . "\n";
			fwrite($logfile,$message);
			fclose($logfile);
		}	
	}			
	/**
 	* Generate Firefox Profile
 	* Add preferences and extensions to the currrent Firefox profile
 	*/   	
   	public function generateProfile($firefox_config) {
		$profile = $this->profile;
		// Multiple extensions separated by ,
		if(isset($firefox_config['extensions'])) {
			$extensions = explode(',',$firefox_config['extensions']);
			foreach($extensions as $extension) {		
				$profile->addExtension($extension . '.xpi');
			} 
		}
		// Multiple preferences separated by ,		
		if(isset($firefox_config['preferences'])) {
			$preferences = explode(',',$firefox_config['preferences']);
			$values = explode(',',$firefox_config['values']);

			if(count($values)==0) { return "Exiting. Please check Firefox Profile values."; }
			for ($i = 0; $i < count($preferences); $i++) {
				if($values[$i]=='true') {
					$values[$i] = TRUE;
				} elseif($values[$i]=='false') {	
					$values[$i] = FALSE;
				}	
				$profile->setPreference($preferences[$i],$values[$i]);		
			}
		}
	}
	
	/**
 	* Processes Config
 	* Run thru steps
 	*/
	function run() {
		$pullcase = $this->configarray;
		$steps = $pullcase['steps'];
		foreach($steps as $step) {
			$command=$step['command'];
			$parameter = isset($step['parameter']) ? $step['parameter'] : null;
			$value = isset($step['value']) ? $step['value'] : null;
			$this->logverbose("Running " . $command,$parameter . "\n" . $value);
			$this->runCommand($command,$parameter,$value);
		}
	}
	
	/**
 	* Export to DB
 	* Imports XML to a database table per configuration file
 	*/	
	function exporttoDB($xml) {
		if(array_key_exists("database",$this->configarray)) {
				$db = new Database();
				$db->importXML($xmlout);
			} else{
				return "No database configuration given. Check config XML.";
		}
	}	
	
	/**
 	* Outputs structured XML from $html input
 	* Uses Tidy to clean up $html
 	* Uses QueryPath to get DOM elements
 	* Uses SimpleXML to output to XML
 	*/
	function outputXML() {
		$html = $this->getSource();
		$tidy = tidy_parse_string($html)->html()->value;
		$searchqp = htmlqp($tidy,'body');

		// Set XML based on elements in config XML
		$root = "root";
		$element_head = $this->configarray['element_head'];
		$xml_output = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $root . "></" . $root . ">");
		
		$elements = $this->elements;
		$input = array();
		$output = array();
		$type = array();

		foreach($elements as $element) {
			$input[] = $element['input'];
			$output[] = $element['output'];
			$type[] = isset($element['type']) ? $element['type'] : "HTML";
		}	
		
		$this->logverbose("Output Format",print_r($type,true));
		
		// Create arrays out of the values generated by the input element
		// $type is an alternative of HTML or text
		// Data not found will return empty in array
		for ($i = 0; $i < count($input); $i++) {
			if(null==$searchqp->branch($input[$i])->$type[$i]()) { 
				//${"input" . $i}[] = "NULL";
				var_dump("Incorrect search parameters. Check element/input in your XML against the page you are mining.\n");
			} else {
				foreach($searchqp->branch($input[$i]) as ${"branched" . $i}) {
					${"input" . $i}[] = ${"branched" . $i}->$type[$i]();
					//$message = ${"branched" . $i} . "\n" . $type[$i];
					//$this->logverbose("Querypath parameter " . $i,$message);
				}
			}
		}
		
		// Combine values with XML
		// Wrap the items around the first array - input0
		for ($i = 0; $i < count($input0); $i++) {
			$increment_head = $element_head . $i;
			$head = $xml_output->addChild($increment_head);
			for ($j = 0; $j < count($output); $j++) {
				$head->addChild($output[$j],${"input" . $j}[$i]);
			}
		}
		
		var_dump($xml_output->asXML());
		//$xml_output->asXML();
	}
	
	/**
 	* Run command specified by config
 	*/	
	function runCommand($command,$parameter=NULL,$value=NULL) {
		if($value==NULL) {
			if($parameter==NULL) {
				// Return XML
				return $this->$command();
			} else { $this->$command($parameter); }
		} else { $this->$command($parameter,$value); }
	}
	
	function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
	}
	
	/**
 	* Exception management function
 	*/
	function mytry($attempt) {
		echo "The title is " . $this->driver->getTitle() . "'\n";
	}

	/**
 	* Get the title of the current page
 	*/
	function getTitle() {
		echo "The title is " . $this->driver->getTitle() . "'\n";
	}

	/**
 	* Go to a specific page
 	*/
	function get($url) {
		$this->driver->get($url);
	}
	
	/**
 	* Type some $text into a $field passed by $array
 	*/	
	function type($field,$text) { 
		$this->driver->findElement(WebDriverBy::CssSelector($field))->sendKeys($text);
	}

	/**
 	* Click some $element passed by $array
 	*/
	function click($selector) {
		try {
			$this->driver->findElement(WebDriverBy::CssSelector($selector));
			$this->driver->findElement(WebDriverBy::CssSelector($selector))->click();
		}

		catch(Exception $e) {
			$error=$e->getMessage();
			echo 'Error Message: ' . substr($error,0,strpos($error,'For documentation')) . "\n";
		}
	}

	/**
 	* Get the source of the current page
 	*/
	function getSource() {
		return $this->driver->getPageSource();
	}

	/**
 	* Get the source of the current page
 	*/
	function saveSourcetoFile($filename) {
		file_put_contents($filename,$this->driver->getPageSource());
	}
	
	/**
 	* Get Captcha Value and input in box
 	*/
	function captcha($imglocation,$typeinbox) {
		$html = $this->getSource();
		$tidy = tidy_parse_string($html)->html()->value;
		$searchqp = htmlqp($tidy,'body');
		$captchaurl = $searchqp->branch($imglocation)->attr('src');
		$saveimg = '/tmp/mycaptcha.png';
		file_put_contents($saveimg, file_get_contents($captchaurl));
		$tesseract = new TesseractOCR($saveimg);
		$crackedvalue = $tesseract->recognize();
		$this->driver->findElement(WebDriverBy::CssSelector($typeinbox))->sendKeys($crackedvalue);
	}	
	
	/**
 	* Get Captcha Value and input in box
 	*/
	function _get_imagepath($content) {
		$doc = new DOMDocument();
		$doc->loadHTML($content);
		$imagepaths=array();
		$imageTags = $doc->getElementsByTagName('img');
		foreach($imageTags as $tag) {
			//$imagepaths[]=urldecode($tag->getAttribute('src')); 
			$imagepaths[]=$tag->getAttribute('src');
		}

		if(!empty($imagepaths)){
			return $imagepaths;
		} else {
			return FALSE;
		}
	}

	/**
 	* Grab every image from page
 	*/
	function grabimages($content) {
		$html = $this->getSource();
		$imagepaths = $this->_get_imagepath($html);
		foreach($imagepaths as $imagepath) {
			$saveimg = basename($imagepath);
			file_put_contents($saveimg, file_get_contents($captchaurl));
		}
	}	
	
	/**
 	* Save all images from page using traditional save as
 	*/
 	function getAllImgSave($image,$next) {
		for ($x = 0; $x <= 1000; $x++) {
			$this->getImage($image);
			//$nextbutton = $this->driver->findElement(WebDriverBy::CssSelector($next));
			if(!empty($this->click($next))) {
				$this->click($next);	
			} else {
				return "Finished";
			}
		} 
	}	
	
	/**
 	* Save image from page using traditional save as
 	*/
	function getImage($selector) {
		$element = $this->driver->findElement(WebDriverBy::CssSelector($selector));
		$this->driver->action()->contextClick($element)->perform();
		// Arrow Down a number of times
		for($i=0; $i < 5; $i++) {
			$this->driver->action()->sendKeys(NULL,WebDriverKeys::ARROW_DOWN)->perform();
		}
		$this->driver->action()->sendKeys(NULL,WebDriverKeys::ARROW_RIGHT)->perform();
		$this->driver->action()->sendKeys(NULL,WebDriverKeys::RETURN_KEY)->perform();
	}		
	
	/**
 	* Grab all images from a gallery via Scraping
 	* Used for protected contnt
 	*/
	function getAllImgScrape($next,$galleryname) {
		for ($x = 0; $x <= 1000; $x++) {
			$this->takeScreenshot($galleryname,$x);
			//$nextbutton = $this->driver->findElement(WebDriverBy::CssSelector($next));
			if(!empty($this->click($next))) {
				$this->click($next);	
			} else {
				return "Finished";
			}
		} 
	}
	
	/**
 	* Takes a single screenshot of page
 	* Path parameter to customize saving
 	*/
	function takeScreenshot($path,$filename) {
		if (!is_dir($path)) { mkdir($path); }
		$this->driver->takeScreenshot($path . '/' . $filename . '.png');
	}				
}
