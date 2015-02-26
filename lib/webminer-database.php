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

// Meekrodb
//require_once 'meekrodb/db.class.php';

/**
 * Class to manage database interactions and logging
 */
class Database {
	use Configurator;
	
	private $db_user, $db_passwd, $db_db, $process_time;
	
	function __construct() {
		$this->getConfig();
	}
	
	/**
     * Grab database values from config file
     */
	public function getConfig() {
		$config = $this->getXML();
		$this->process_time = $config['process']['time'];
		$this->db_user = $config['database']['user'];
		$this->db_passwd = $config['database']['password'];
		$this->db_db = $config['database']['database'];
		DB::$dbName = $this->db_db;
		DB::$password = $this->db_passwd;
		DB::$user = $this->db_user;
	}
	
	/**
     * Installs a base structure to get started
     */
    public function install() {
		echo "installing";
	}
	

	/**
     * List entries
     */
	public function list_entry() {
		$sites= DB::query("SELECT * FROM sites");
		foreach ($sites as $site) {
			echo $site['name'] . "\n";
		}
	}

	/**
     * Add site
     */
	public function add($site) {
		DB::insert('sites', array(
			'username' => 'John', 
			'password' => 'whatever')
		);
	}
	
	/**
     * Get site ID for request association
     */
	public function getID($site) {
		$result = DB::query("SELECT * FROM sites WHERE name = %s",$site);
		return $result[0]['id'];
	}

	
	public function getServerofContainer($container) {
		$result = DB::query("SELECT docker_server FROM requests WHERE container = %s",$container);
		return $result[0]['docker_server'];
	}
	
	/**
     * Add request
     */
	public function addRequest($url,$site) {
		$site_id = $this->getSiteID($site);
		DB::insert('requests', array(
			'site_id' => $site_id, 
			'url' => $url,
			'added' => date('Y-m-d H:i:s', strtotime("now")),
			)
		);
		return DB::insertId();
	}

	/**
     * Set the machine for the container
     * Treat docker containers as machines
     */
	public function setRequestMachine($request,$server,$container) {
		DB::update('requests', array(
			'docker_server' => $server,
			'container' => $container
			), "id=%i", $request);
	}	
	
	
	/**
     * Get the status of a request
     * Based on URL and Process Time
     * Process Time set in the config - number of days from process time
     */
	public function checkStatus($url) {
		$stale = date('Y-m-d H:i:s', strtotime("-$this->process_time days"));
		$requests = DB::query("SELECT * FROM requests WHERE url = %s AND (processed > %t OR processed IS NULL)", $url, $stale);
		if (empty($requests)) { 
			return 0; 
		} else {
			return 1;
		}
	}

}
