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
	
	public $driver, $source, $site, $configarray, $steps, $elements, $profile;
	
	public function __construct($host="localhost",$port="4444") {
		// Get the configuration from Configurator
		$this->configarray = $this->getConfig();
		$this->steps = isset($this->configarray['steps']) ? $this->configarray['steps'] : null;
		$this->elements = isset($this->configarray['elements']) ? $this->configarray['elements'] : null;
		if(isset($this->configarray['site'])) {
			$this->site = $this->configarray['site'];
		} else { return "Missing site"; }
			
		$dhost = 'http://' . $host . ':' . $port . '/wd/hub';
		$capabilities = DesiredCapabilities::firefox();
			
		// Build Firefox Profile
		// Additional Options available in Config
		$this->profile = new FirefoxProfile();
		if(isset($this->configarray['firefoxprofile'])) {
			$firefox_config = $this->configarray['firefoxprofile'];
			$this->generateProfile($firefox_config);
		}
		//var_dump($this->profile);
		//$this->profile->getProfile("testing");
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
 	* Run thru steps if any
 	* Extract Data after steps if expected
 	*/
	function run() {
		$pullcase = $this->configarray;
		// Run thru steps that are in the XML if there are any, if not continue
		if($this->steps) {
			$steps = $pullcase['steps'];
			foreach($steps as $step) {
				$command=$step['command'];
				$parameter=$step['parameter'];
				$value = isset($step['value']) ? $step['value'] : null;
				$this->runCommands($command,$parameter,$value);	
			}
		} else {
			echo "No steps to follow. Continuing..\n";
		}
		// If output is expected according to config XML
		if(isset($pullcase['root'])) {
			// Extract data according to the XML
			// Save the elements as an array to pass to outXML
			$html = $this->getSource();
			//echo $html;
			$xmlout = $this->outputXML($html);
			//var_dump($xmlout);
			// Check for database tags for import
			if(array_key_exists("database",$pullcase)) {
				$db = new Database();
				$db->importXML($xmlout);
			} else{
				return $xmlout;
			}
		} else {
			return "No elements expected for output. Finished\n";
		}
	}
	
	/**
 	* Outputs structured XML from $html input
 	* Uses Tidy to clean up $html
 	* Uses QueryPath to get DOM elements
 	* Uses SimpleXML to output to XML
 	*/
	function outputXML($html) {
		$tidy = tidy_parse_string($html)->html()->value;
		$searchqp = htmlqp($tidy,'body');

		// Set XML based on elements in config XML
		$root = $this->configarray['root'];
		$element_head = $this->configarray['element_head'];
		$xml_output = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $root . "></" . $root . ">");
		//var_dump($xml_output->asXML());
		
		$elements = $this->elements;
		$input = array();
		$output = array();
		$type = array();

		foreach($elements as $element) {
			$input[] = $element['input'];
			$output[] = $element['output'];
			$type[] = isset($element['type']) ? $element['type'] : "HTML";
		}	
		
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
		
		
		return $xml_output->asXML();
	}
	
	function runCommands($command,$parameter,$value="") {
		// Skip URL Typing as this has already been done
		if($parameter!='url') {
			if($command=='type'||$command=='captcha'||$command=='getAllImgScrape'||$command=='getAllImgSave')  { 
				$this->$command($parameter,$value); 
			} else {
				$this->$command($parameter);
			}
		}
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
	function captcha($imglocation,$type) {
		$html = $this->getSource();
		$tidy = tidy_parse_string($html)->html()->value;
		$searchqp = htmlqp($tidy,'body');
		$captchaurl = $searchqp->branch($imglocation)->attr('src');
		$saveimg = '/tmp/mycaptcha.png';
		file_put_contents($saveimg, file_get_contents($captchaurl));
		$tesseract = new TesseractOCR($saveimg);
		$crackedvalue = $tesseract->recognize();
		$this->driver->findElement(WebDriverBy::CssSelector($type))->sendKeys($crackedvalue);
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
