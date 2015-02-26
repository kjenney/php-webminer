<?php
// Copyright 2015-present kjenney. All Rights Reserved.
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
 * Grab the XML Configuration
 * Used by the trait to make this accessible across classes
 */
class Config {
	
    public static $xmlarray;

    public function setXML($value) {
		//$xmlobj = simplexml_load_file($value);
		//self::$xmlarray = xmlobj2arr($xmlobj);
		$Data = simplexml_load_file($value);
		if (!isset($ret)) { $ret = array(); }
		if (is_object($Data))
			{ foreach (get_object_vars($Data) as $key => $val) { $ret[$key] = $this->xmlobj2arr($val); } self::$xmlarray = $ret; }
		elseif (is_array($Data)) {
			foreach ($Data as $key => $val) { $ret[$key] = $this->xmlobj2arr($val); } self::$xmlarray = $ret;
		} else { 
			self::$xmlarray = $Data; }
    }
    
    public function xmlobj2arr($Data) {
    	if (!isset($ret)) { $ret = array(); }
    	if (is_object($Data))
    	{ foreach (get_object_vars($Data) as $key => $val) { $ret[$key] = $this->xmlobj2arr($val); } return $ret; }
    	elseif (is_array($Data)) {
    		foreach ($Data as $key => $val) { $ret[$key] = $this->xmlobj2arr($val); } return $ret;
    	} else {
    		return $Data; }
    }
}

trait Configurator {
	public function getXML() {
		return Config::$xmlarray;
	}
}
