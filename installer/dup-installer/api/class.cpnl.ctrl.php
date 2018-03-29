<?php
defined("ABSPATH") or die("");

//This class is called through both the router.php and main.installer.php
//Full Path: {$GLOBALS['DUPX_INIT']}/api/
require_once("class.cpnl.base.php");


/**
 * Class used to store cPanel host information 
 * */
class DUPX_cPanelHost
{
	public $url;
	public $host;
	public $scheme;
	public $port;
	public $user;
	public $pass;
}

/**
 * Wrapper Class for cPanel API  
 *
 * <routable> */
class DUPX_cPanel_Controller
{
	private $api;

	/**
	 * 	Creates a security token to access the cPanel calls
	 *  @param  string	$host	The cPanel host name can be full url or just domain name
	 * 							https://mysite.com:2083, https://mysite.com, mysite.com
	 *  @param  string	$user	A valid cPanel user name
	 *  @param  string	$pass	A valid cPanel user password
	 *  @return string		    A base64 encoded string of the input params
	 *
	 *  <route template="/cpnl/create_token/{host}/{user}/{pass}/">
	 */
	public function create_token($host, $user, $pass)
	{
		if (substr($host, 0, 4) !== 'http')
		{
			$host = 'https://' . $host;
		}

		$url = parse_url($host);
		$host = isset($url['host']) ? $url['host'] : null;

		if (!$host || is_null($host))
		{
			throw new Exception('The create_token operation requires a valid host parameter');
		}
		if (!$user)
		{
			throw new Exception('The create_token operation requires a valid user parameter');
		}
		if (!$pass)
		{
			throw new Exception('The create_token operation requires a valid password parameter');
		}

		$scheme = isset($url['scheme']) ? $url['scheme'] : 'https';
		$port = isset($url['port']) ? $url['port'] : '2083';
		$token = base64_encode("{$scheme},{$host},{$port},{$user},{$pass}");
		return $token;
	}

	/**
	 * 	Get the host information about this cpanel server
	 *  @param  string	$token		The authtoken used to access cpanel
	 * 	@return DUPX_cPanelHost		A DUPX_cPanelHost object
	 *
	 *  <route template="/cpnl/get_host/{token}/">
	 */
	public function get_host($token)
	{
		$host = new DUPX_cPanelHost();
		$creds = explode(",", base64_decode($token));

		if (!isset($creds[1]))
		{
			throw new Exception("Invalid hostname detected for get_host with token: $token");
		}

		$host->scheme = $creds[0];
		$host->host = $creds[1];
		$host->port = $creds[2];
		$host->user = $creds[3];
		$host->pass = $creds[4];
		$host->url = "{$host->scheme}://{$host->host}:{$host->port}";

		return $host;
	}

	/**
	 * 	Get the setup data needed for validating and show DB information
	 *  @param  string	$token					The authtoken used to access cpanel
	 * 	@return array	$data['valid_host']		Is the url a valid cpanel URL
	 * 					$data['valid_user']		Is the user a valid cpanel user
	 * 					$data['is_prefix_on']	Does the cpanel use a DB prefix
	 * 					$data['dbinfo']			A list of databases and info
	 * 					$data['dbusers']		A list of database users
	 *
	 *  <route template="/cpnl/get_setup_data/{token}/">
	 */
	public function get_setup_data($token)
	{
		$data = array();
		$host = $this->connect($token);
		$data['valid_host'] = false;
		$data['valid_user'] = false;
		$data['is_prefix_on'] = false;
		$data['dbinfo'] = null;
		$data['dbusers'] = null;

		try
		{
			$data = array();
			$host = $this->connect($token);
			$data['valid_host'] = $this->is_host_active($host->url);
			$data['is_prefix_on'] = $this->is_prefix_on($token);
			//Try two calls just in case
			$obj = json_decode($this->api->api2_query($host->user, "Contactus", "isenabled"));
			if (isset($obj->cpanelresult->func))
			{
				$data['valid_user'] = true;
			}
			else
			{
				$obj = json_decode($this->api->api2_query($host->user, "Email", "accountname"));
				if (isset($obj->cpanelresult->func))
					$data['valid_user'] = true;
			}

			//DB NAMES/USRERS
			$obj = json_decode($this->api->api2_query($host->user, "MysqlFE", "getalldbsinfo"));
			$obj_dbs = isset($obj->cpanelresult->data) ? $obj->cpanelresult->data : null;
			$data['dbinfo'] = ($obj_dbs != null && count($obj_dbs) >= 1) ? $obj_dbs : null;

			$obj = json_decode($this->api->api2_query($host->user, "MysqlFE", "listusers"));
			$obj_dbusers = isset($obj->cpanelresult->data) ? $obj->cpanelresult->data : null;
			$data['dbusers'] = ($obj_dbusers != null && count($obj_dbusers) >= 1) ? $obj_dbusers : null;

			return $data;
		}
		catch (Exception $ex)
		{
			return $data;
		}
	}

	/**
	 * 	Lists the databases for the specified cpanel account
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/list_dbs/{token}/">
	 */
	public function list_dbs($token)
	{
		// Returns true/false or message on error
		$data['status'] = "Error listing databases.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$json = $this->api->api2_query($host->user, "MysqlFE", "listdbs");
		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;
			$data['status'] = isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1 ? true : false;
			if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}

	/**
	 * 	Creates a database via the cPanel API
	 *
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbname		The name of database to create
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/create_db/{token}/{dbname}">
	 */
	public function create_db($token, $dbname)
	{
		// Returns true/false or message on error
		$data['status'] = "Error creating database '{$dbname}'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['db'] = $dbname;
		$json = $this->api->api2_query($host->user, "MysqlFE", "createdb", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;
			$data['status'] = isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1 ? true : false;
			if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}

	/**
	 * 	Deletes a database via the cPanel API
	 *
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbname		The name of database to delete
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/delete_db/{token}/{dbname}">
	 */
	public function delete_db($token, $dbname)
	{
		// Returns true/false or message on error
		$data['status'] = "Error deleting database '{$dbname}'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['db'] = $dbname;
		$json = $this->api->api2_query($host->user, "MysqlFE", "deletedb", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;
			$data['status'] = isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1 ? true : false;
			if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}



	/**
	 * 	Creates a database user via the cPanel API
	 *
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbuser		The name of database user to create
	 * 	@param  string	$dbpass		The database password to create for the user
	 *  @return array	$data['status']	    True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/create_db_user/{token}/{dbuser}/{dbpass}">
	 */
	public function create_db_user($token, $dbuser, $dbpass)
	{
		// Returns true/false or message on error
		$data['status'] = "Error creating database user '{$dbuser}'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['dbuser'] = $dbuser;
		$args['password'] = $dbpass;
		$json = $this->api->api2_query($host->user, "MysqlFE", "createdbuser", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;

			if (isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1)
			{
				$data['status'] = true;
			}
			if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}


	/**
	 * 	Deletes a database user via the cPanel API
	 *
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbuser		The name of database user to delete
	 *  @return array	$data['status']	    True/False or an error message.
	 * 					$data['cpnl_api']	The cpanel API result
	 *
	 *  <route template="/cpnl/delete_db_user/{token}/{dbuser}">
	 */
	public function delete_db_user($token, $dbuser)
	{
		// Returns true/false or message on error
		$data['status'] = "Error deleting database user '{$dbuser}'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['dbuser'] = $dbuser;
		$json = $this->api->api2_query($host->user, "MysqlFE", "deletedbuser", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;

			if (isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1)
			{
				$data['status'] = true;
			}
			if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}


	/**
	 * 	Assigns a user to a database  via the cPanel API with ALL privileges
	 *
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbname		The name of a valid database
	 * 	@param  string	$dbuser		The user to add to the database
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/assign_db_user/{token}/{dbname}/{dbuser}">
	 */
	public function assign_db_user($token, $dbname, $dbuser)
	{
		// Returns true/false or message on error
		$data['status'] = "Unable to retrieve error status from cPanel API'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['privileges'] = 'ALL PRIVILEGES';
		$args['db'] = $dbname;
		$args['dbuser'] = $dbuser;

		$json = $this->api->api2_query($host->user, "MysqlFE", "setdbuserprivileges", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;

			//On some APIs this method returns error = 1 even when result = 1.  It seems that the result = 1 is more
			//accurate for success detection so that will be the primary driver for success
			if (isset($obj->cpanelresult->event->result) && $obj->cpanelresult->event->result == 1)
			{
				$data['status'] = true;
			}
			else if (property_exists($obj->cpanelresult, 'error'))
			{
				$data['status'] = $obj->cpanelresult->error;
			}
		}
		return $data;
	}

	/**
	 * 	Is the user in the database specified
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @param  string	$dbname		The name of a valid database
	 * 	@param  string	$dbuser		A database user
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/is_user_in_db/{token}/{dbname}/{dbuser}">
	 */
	public function is_user_in_db($token, $dbname, $dbuser)
	{
		// Returns true/false or message on error
		$data['status'] = "Error determining if '{$dbuser}' can access database '{$dbname}'.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$args = array();
		$args['db'] = $dbname;
		$json = $this->api->api2_query($host->user, "MysqlFE", "listusersindb", $args);

		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;
			$data['status'] = false;
			foreach ($obj->cpanelresult->data as $database_pair)
			{
				if ($database_pair->user == $dbuser)
				{
					$data['status'] = true;
					break;
				}
			}
		}
		else
		{
			$data['status'] = 'Could not retrieve list of users in database.';
		}

		return $data;
	}

	/**
	 * 	Does cpanel require the database to have a prefix
	 *  @param  string	$token		The authtoken used to access cpanel
	 *  @return array	$data['status']	  True/False or an error message.
	 * 					$data['cpnl_api'] The cpanel API result
	 *
	 *  <route template="/cpnl/is_prefix_on/{token}">
	 */
	public function is_prefix_on($token)
	{
		// Returns true/false or message on error
		$data['status'] = "Error determining if database prefix name is enabled.  See the log for more details.";
		$data['cpnl_api'] = null;
		$host = $this->connect($token);

		$json = $this->api->api2_query($host->user, "DBmap", "status");
		if ($json !== false)
		{
			$obj = json_decode($json);
			$data['cpnl_api'] = $obj;

			if (isset($obj->cpanelresult->data))
			{
				$data['status'] = "Error calling DBmap";
			}
			else
			{
				$data['status'] = ($obj->cpanelresult->data[0]->prefix == 1);
			}
		}

		return $data;
	}

	/**
	 *  Connect to the cPanel API
	 *
	 *  @param  string	$token			A valid token
	 * 	@return DUPX_cPanelHost		    A DUPX_cPanelHost object
	 */
	public function connect($token)
	{
		$host = $this->get_host($token);
		if (!$host->host || !$host->user || !$host->pass)
		{
			throw new Exception('DUPX_cPanel->connect invalid token provided.');
		}

		//Call to cPanel XMLAPI Client Class see /classes/_libs.php
		$this->api = new CPNL_API($host->host);
		$this->api->password_auth($host->user, $host->pass);
		$this->api->set_protocol($host->scheme);
		$this->api->set_port($host->port);
		$this->api->set_output("json");
		$this->api->set_debug(0);

		return $host;
	}

	/**
	 *  Check to see if the cPanel API is availble for use
	 *  @param string	$host
	 *  @return bool	True if this host supports the json api
	 *  //https://mysite.com:2083/json-api/
	 *
	 */
	private function is_host_active($url)
	{
		$route = '/json-api/';
		$url = (!strpos($url, $route)) ? "{$url}/json-api/" : $url;

		$response = DUPX_HTTP::get($url);
		$json = json_decode($response);

		if (isset($json->cpanelresult))
		{
			return true;
		}
		return false;
	}

}
?>