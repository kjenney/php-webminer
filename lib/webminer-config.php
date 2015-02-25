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
class Config
{
    /**
     * Full XML configuration
     *
     * @var SimpleXMLElement
     */
    private static $xml;

    /**
     * Parse XML configuration file into XML object (once)
     */
    public function __construct($filename)
    {
        if (!self::$xml) {
            $xmlSource = file_get_contents($filename);
            self::$xml = new SimpleXMLElement($xmlSource);
        }
    }

    /**
     * Returns the first node matching the specified xpath
     *
     * @param $xpath
     *
     * @return SimpleXMLElement
     */
    public function getFirstByXpath($xpath)
    {
        // Return the first matching configuration
        return self::$xml->xpath($xpath)[0];
    }
}

trait Configurable
{
    /**
     * Class configuration XML element
     *
     * @var SimpleXMLElement
     */
    public static $xml_file;
    private $configuration;

    /**
     * Get the class configuration XML element
     *
     * @return SimpleXMLElement
     */
    function getConfiguration()
    {
        if (!$this->configuration) {
            $tag = strtolower(get_class($this));
            $xpath = '/config/' . $tag;

            $this->configuration = (new Config($xml_file))->getFirstByXpath($xpath);
        }

        return $this->configuration;
    }
}
