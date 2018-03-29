<?php
defined("ABSPATH") or die("");

class DUPX_DBInstall
{
	private $dbh;
	private $post;
	public $sql_result_data;
	public $sql_result_data_length;
	public $dbvar_maxtime;
	public $dbvar_maxpacks;
	public $dbvar_sqlmode;
	public $dbvar_version;
	public $pos_in_sql;
	public $sql_file_path;
	public $sql_result_file_path;
	public $php_mem;
	public $php_mem_range;
	public $php_used_mem;
	public $table_count;
	public $table_rows;
	public $query_errs;
	public $root_path;
	public $drop_tbl_log;
	public $rename_tbl_log;
	public $dbquery_errs;
	public $dbquery_rows;
	public $dbtable_count;
	public $dbtable_rows;
	public $dbdelete_count;
	public $profile_start;
	public $profile_end;
	public $start_microtime;
	public $dbcollatefb;
	public $dbobj_views;
	public $dbobj_procs;

	public function __construct($post, $start_microtime)
	{
		$this->post					 = $post;
		$this->sql_file_path		 = $GLOBALS['DUPX_ROOT'].'/database.sql';
		$this->php_mem				 = $GLOBALS['PHP_MEMORY_LIMIT'];
		$this->php_used_mem			 = memory_get_usage();
		$this->php_mem_range		 = 1024 * 1024;
		$this->root_path			 = $GLOBALS['DUPX_ROOT'];
		$this->sql_result_file_path	 = "{$this->root_path}/{$GLOBALS['SQL_FILE_NAME']}";

		//ESTABLISH CONNECTION
		$this->dbh = DUPX_DB::connect($post['dbhost'], $post['dbuser'], $post['dbpass']);
		($this->dbh) or DUPX_Log::error(ERR_DBCONNECT.mysqli_connect_error());
		if ($_POST['dbaction'] == 'empty' || $post['dbaction'] == 'rename') {
			mysqli_select_db($this->dbh, $post['dbname'])
				or DUPX_Log::error(sprintf(ERR_DBCREATE, $post['dbname']));
		}

		$this->profile_start	 = isset($post['profile_start']) ? $post['profile_start'] : DUPX_U::getMicrotime();
		$this->start_microtime	 = isset($post['start_microtime']) ? $post['start_microtime'] : $start_microtime;
		$this->dbvar_maxtime	 = DUPX_DB::getVariable($this->dbh, 'wait_timeout');
		$this->dbvar_maxpacks	 = DUPX_DB::getVariable($this->dbh, 'max_allowed_packet');
		$this->dbvar_sqlmode	 = DUPX_DB::getVariable($this->dbh, 'sql_mode');
		$this->dbvar_version	 = DUPX_DB::getVersion($this->dbh);
		$this->dbvar_maxtime	 = is_null($this->dbvar_maxtime) ? 300 : $this->dbvar_maxtime;
		$this->dbvar_maxpacks	 = is_null($this->dbvar_maxpacks) ? 1048576 : $this->dbvar_maxpacks;
		$this->dbvar_sqlmode	 = empty($this->dbvar_sqlmode) ? 'NOT_SET' : $this->dbvar_sqlmode;
		$this->dbquery_errs		 = isset($post['dbquery_errs']) ? $post['dbquery_errs'] : 0;
		$this->drop_tbl_log		 = isset($post['drop_tbl_log']) ? $post['drop_tbl_log'] : 0;
		$this->rename_tbl_log	 = isset($post['rename_tbl_log']) ? $post['rename_tbl_log'] : 0;
		$this->dbquery_rows		 = isset($post['dbquery_rows']) ? $post['dbquery_rows'] : 0;
		$this->dbdelete_count	 = isset($post['dbdelete_count']) ? $post['dbdelete_count'] : 0;
		$this->dbcollatefb		 = isset($post['dbcollatefb']) ? $post['dbcollatefb'] : 0;
		$this->dbobj_views		 = isset($post['dbobj_views']) ? $post['dbobj_views'] : 0;
		$this->dbobj_procs		 = isset($post['dbobj_procs']) ? $post['dbobj_procs'] : 0;
	}

	public function prepareSQL()
	{
		$faq_url		 = $GLOBALS['FAQ_URL'];
		$db_file_size	 = @filesize('database.sql');

		//Fatal Memory errors from file_get_contents is not catchable.
		//Try to warn ahead of time with a buffer in memory difference
		if ($db_file_size >= $this->php_mem_range && $this->php_mem_range != 0) {
			$db_file_size	 = DUPX_U::readableByteSize($db_file_size);
			$msg			 = "\nWARNING: The database script is '{$db_file_size}' in size.  The PHP memory allocation is set\n";
			$msg			 .= "at '{$this->php_mem}'.  There is a high possibility that the installer script will fail with\n";
			$msg			 .= "a memory allocation error when trying to load the database.sql file.  It is\n";
			$msg			 .= "recommended to increase the 'memory_limit' setting in the php.ini config file.\n";
			$msg			 .= "see: {$faq_url}#faq-trouble-056-q \n";
			DUPX_Log::info($msg);
		}

		@chmod("{$this->root_path}/database.sql", 0777);
		$sql_file = file_get_contents($this->sql_file_path, true);

		//ERROR: Reading database.sql file
		if ($sql_file === FALSE || strlen($sql_file) < 10) {
			$spacer	 = str_repeat("&nbsp;", 5);
			$msg	 = <<<EOT
<b>Unable to read/find the database.sql file from the archive.</b><br/>
Please check these items: <br/><br/>
1. Validate permissions and/or group-owner rights on these items: <br/>
{$spacer}- File: database.sql <br/>
{$spacer}- Directory: [{$this->root_path}] <br/>
{$spacer}<small>See: <a href='{$faq_url}#faq-trouble-055-q' target='_blank'>{$faq_url}#faq-trouble-055-q</a></small><br/><br/>
2. Validate the database.sql file exists and is in the root of the archive.zip file <br/>
{$spacer}<small>See: <a href='{$faq_url}#faq-installer-020-q' target='_blank'>{$faq_url}#faq-installer-020-q</a></small><br/><br/>
EOT;
			DUPX_Log::error($msg);
		}

		//Removes invalid space characters
		//Complex Subject See: http://webcollab.sourceforge.net/unicode.html
		$sql_file = $this->nbspFix($sql_file);

		//Write new contents to install-data.sql

		@chmod($this->sql_result_file_path, 0777);
		$sql_file_copy_status			 = file_put_contents($this->sql_result_file_path, $sql_file);
		$this->sql_result_data			 = explode(";\n", $sql_file);
		$this->sql_result_data_length	 = count($this->sql_result_data);
		$sql_file						 = null;


		//WARNING: Create installer-data.sql failed
		if ($sql_file_copy_status === FALSE || filesize($this->sql_result_file_path) == 0 || !is_readable($this->sql_result_file_path)) {
			$sql_file_size	 = DUPX_U::readableByteSize(filesize('database.sql'));
			$msg			 = "\nWARNING: Unable to properly copy database.sql ({$sql_file_size}) to {$GLOBALS['SQL_FILE_NAME']}.  Please check these items:\n";
			$msg			 .= "- Validate permissions and/or group-owner rights on database.sql and directory [{$this->root_path}] \n";
			$msg			 .= "- see: {$faq_url}#faq-trouble-055-q \n";
			DUPX_Log::info($msg);
		}
	}

	public function prepareDB()
	{
		//RUN DATABASE SCRIPT
		@mysqli_query($this->dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
		@mysqli_query($this->dbh, "SET max_allowed_packet = {$GLOBALS['DB_MAX_PACKETS']}");
		DUPX_DB::setCharset($this->dbh, $this->post['dbcharset'], $this->post['dbcollate']);

		//Will set mode to null only for this db handle session
		//sql_mode can cause db create issues on some systems
		switch ($this->post['dbmysqlmode']) {
			case 'DISABLE':
				@mysqli_query($this->dbh, "SET SESSION sql_mode = ''");
				break;
			case 'CUSTOM':
				$dbmysqlmode_opts = $this->post['dbmysqlmode_opts'];

				$qry_session_custom = @mysqli_query($this->dbh, "SET SESSION sql_mode = '{$dbmysqlmode_opts}'");
				if ($qry_session_custom == false) {
					$sql_error	 = mysqli_error($this->dbh);
					$log		 = "WARNING: A custom sql_mode setting issue has been detected:\n{$sql_error}.\n";
					$log		 .= "For more details visit: http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html\n";
				}
				break;
		}


		//Set defaults incase the variable could not be read
		$this->drop_tbl_log		 = 0;
		$this->rename_tbl_log	 = 0;
		$sql_file_size1			 = DUPX_U::readableByteSize(@filesize("{$this->root_path}/database.sql"));
		$sql_file_size2			 = DUPX_U::readableByteSize(@filesize("{$this->root_path}/{$GLOBALS['SQL_FILE_NAME']}"));
		$collate_fb				 = $this->dbcollatefb ? 'On' : 'Off';

		DUPX_Log::info("--------------------------------------");
		DUPX_Log::info('DATABASE-ENVIRONMENT');
		DUPX_Log::info("--------------------------------------");
		DUPX_Log::info("MYSQL VERSION:\tThis Server: {$this->dbvar_version} -- Build Server: {$GLOBALS['DUPX_AC']->version_db}");
		DUPX_Log::info("FILE SIZE:\tdatabase.sql ({$sql_file_size1}) - installer-data.sql ({$sql_file_size2})");
		DUPX_Log::info("TIMEOUT:\t{$this->dbvar_maxtime}");
		DUPX_Log::info("MAXPACK:\t{$this->dbvar_maxpacks}");
		DUPX_Log::info("SQLMODE:\t{$this->dbvar_sqlmode}");
		DUPX_Log::info("NEW SQL FILE:\t[{$this->sql_result_file_path}]");
		DUPX_Log::info("COLLATE FB:\t{$collate_fb}");

		if (version_compare($this->dbvar_version, $GLOBALS['DUPX_AC']->version_db) < 0) {
			DUPX_Log::info("\nNOTICE: This servers version [{$this->dbvar_version}] is less than the build version [{$GLOBALS['DUPX_AC']->version_db}].  \n"
				."If you find issues after testing your site please referr to this FAQ item.\n"
				."https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-260-q");
		}

		//CREATE DB
		switch ($this->post['dbaction']) {
			case "create":
				if ($this->post['view_mode'] == 'basic') {
					mysqli_query($this->dbh, "CREATE DATABASE IF NOT EXISTS `{$this->post['dbname']}`");
				}
				mysqli_select_db($this->dbh, $this->post['dbname'])
					or DUPX_Log::error(sprintf(ERR_DBCONNECT_CREATE, $this->post['dbname']));
				break;

			//DROP DB TABLES:  DROP TABLE statement does not support views
			case "empty":
			    //Drop all tables
                $this->dropTables();

                //Drop all views
                $this->dropViews();

                //Drop all procedures
                $this->dropProcs();
				break;

			//RENAME DB TABLES
			case "rename" :
				$sql = "SHOW TABLES FROM `{$this->post['dbname']}` WHERE  `Tables_in_{$this->post['dbname']}` NOT LIKE '{$GLOBALS['DB_RENAME_PREFIX']}%'";
				$found_tables	= null;
				if ($result	= mysqli_query($this->dbh, $sql)) {
					while ($row = mysqli_fetch_row($result)) {
						$found_tables[] = $row[0];
					}
					if (count($found_tables) > 0) {
						foreach ($found_tables as $table_name) {
							$sql = "RENAME TABLE `{$this->post['dbname']}`.`{$table_name}` TO  `{$this->post['dbname']}`.`{$GLOBALS['DB_RENAME_PREFIX']}{$table_name}`";
							if (!$result = mysqli_query($this->dbh, $sql)) {
								DUPX_Log::error(sprintf(ERR_DBTRYRENAME, "{$this->post['dbname']}.{$table_name}"));
							}
						}
						$this->rename_tbl_log = count($found_tables);
					}
				}
				break;
		}
	}

	public function writeInChunks()
	{
		$buffer					 = '';
		chmod($this->sql_file_path, 0777);
		$handle					 = fopen($this->sql_file_path, 'rb');
		$chunk_size				 = $this->php_mem_range;
		$chunk_left_difference	 = @filesize($this->sql_file_path) - $this->post['pos'];
		$cut_size				 = ($this->php_mem_range > $chunk_left_difference) ? $chunk_left_difference : $this->php_mem_range;
		$last_query				 = ($this->php_mem_range > $chunk_left_difference);
		$next_is_last_query		 = ($this->php_mem_range > ($chunk_left_difference - $this->php_mem_range));
		$new_pos				 = 0;
		if ($handle === false) {
			return false;
		}

		if (fseek($handle, $this->post['pos']) === 0) {
			$buffer = fread($handle, $cut_size);
			if (strrpos($buffer, ";\n")) {
				$sql_data_string		 = substr($buffer, 0, strrpos($buffer, ";\n") + 1);
				$sql_data_string		 = $this->nbspFix($sql_data_string);
				$sql_data_arr			 = explode(";\n", $sql_data_string);
				$sql_data_string_length	 = mb_strlen($sql_data_string, '8bit');
				$new_pos				 = $this->post['pos'] + $sql_data_string_length;
				$this->writeInDB($sql_data_arr);
				$json['profile_start']	 = $this->profile_start;
				$json['start_microtime'] = $this->start_microtime;
				$json['dbquery_errs']	 = $this->dbquery_errs;
				$json['drop_tbl_log']	 = $this->drop_tbl_log;
				$json['dbquery_rows']	 = $this->dbquery_rows;
				$json['rename_tbl_log']	 = $this->rename_tbl_log;
				$json['dbdelete_count']	 = $this->dbdelete_count;
				$json['pos']			 = $new_pos;
				if ($last_query) {
					$json['pass']				 = 1;
					$json['continue_chunking']	 = false;
				} else {
					$json['pass']				 = 0;
					$json['continue_chunking']	 = true;
				}
			} else {
				$json['pass']				 = 1;
				$json['continue_chunking']	 = false;
			}
			ob_flush();
			flush();
		}

		fclose($handle);
		return $json;
	}

	public function writeInDB($sql_data = array())
	{
		//WRITE DATA
		$fcgi_buffer_pool	 = 5000;
		$fcgi_buffer_count	 = 0;
		$counter			 = 0;
		if (!empty($sql_data)) {
			$this->sql_result_data = $sql_data;
		}
		$this->applyCollationFallback();
		$sql_data_length = count($this->sql_result_data);
		@mysqli_autocommit($dbh, false);

		while ($counter < $sql_data_length) {

			$query_strlen = strlen(trim($this->sql_result_data[$counter]));
			if ($this->dbvar_maxpacks < $query_strlen) {
				DUPX_Log::info("**ERROR** Query size limit [length={$this->dbvar_maxpacks}] [sql=".substr($this->sql_result_data[$counter], 0, 75)."...]");
				$this->dbquery_errs++;
			} elseif ($query_strlen > 0) {
                $this->delimiterFix($counter);
				@mysqli_free_result(@mysqli_query($this->dbh, ($this->sql_result_data[$counter])));
				$err = mysqli_error($this->dbh);
				//Check to make sure the connection is alive
				if (!empty($err)) {
					if (!mysqli_ping($this->dbh)) {
						mysqli_close($this->dbh);
						$this->dbh = DUPX_DB::connect($this->post['dbhost'], $this->post['dbuser'], $this->post['dbpass'], $this->post['dbname']);
						// Reset session setup
						@mysqli_query($this->dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
						DUPX_DB::setCharset($this->dbh, $this->post['dbcharset'], $this->post['dbcollate']);
					}
					DUPX_Log::info("**ERROR** database error write '{$err}' - [sql=".substr($this->sql_result_data[$counter], 0, 75)."...]");

					if (DUPX_U::contains($err, 'Unknown collation')) {
						DUPX_Log::info('RECOMMENDATION: Try resolutions found at https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-110-q');
					}

					$this->dbquery_errs++;

					//Buffer data to browser to keep connection open
				} else {
					if ($fcgi_buffer_count++ > $fcgi_buffer_pool) {
						$fcgi_buffer_count = 0;
					}
					$this->dbquery_rows++;
				}
			}
			$counter++;
		}
		@mysqli_commit($this->dbh);
		@mysqli_autocommit($this->dbh, true);

		//DATA CLEANUP: Perform Transient Cache Cleanup
		//Remove all duplicator entries and record this one since this is a new install.
		$dbdelete_count1 = 0;
		$dbdelete_count2 = 0;

		@mysqli_query($this->dbh, "DELETE FROM `{$GLOBALS['DUPX_AC']->wp_tableprefix}duplicator_pro_packages`");
		$dbdelete_count1 = @mysqli_affected_rows($this->dbh);

		@mysqli_query($this->dbh, "DELETE FROM `{$GLOBALS['DUPX_AC']->wp_tableprefix}options` WHERE `option_name` LIKE ('_transient%') OR `option_name` LIKE ('_site_transient%')");
		$dbdelete_count2 = @mysqli_affected_rows($this->dbh);

		$this->dbdelete_count += (abs($dbdelete_count1) + abs($dbdelete_count2));

		//Reset Duplicator Options
		foreach ($GLOBALS['DUPX_AC']->opts_delete as $value) {
			mysqli_query($this->dbh, "DELETE FROM `{$GLOBALS['DUPX_AC']->wp_tableprefix}options` WHERE `option_name` = '{$value}'");
		}
				
		//Remove views from DB
		if(! $this->dbobj_views) {
			$this->dropViews();
            DUPX_Log::info("DB VIEWS:\tdisabled");
		} else {
			DUPX_Log::info("DB VIEWS:\tenabled");
		}
		
		//Remove procedures from DB
		if(! $this->dbobj_procs) {
			$this->dropProcs();
            DUPX_Log::info("DB PROCEDURES:\tdisabled");
		} else {
			DUPX_Log::info("DB PROCEDURES:\tenabled");
		}
	}

	private function dropTables(){
        $sql = "SHOW FULL TABLES WHERE Table_Type != 'VIEW'";
        $found_tables = null;
        if ($result = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found_tables[] = $row[0];
            }
            if (count($found_tables) > 0) {
                foreach ($found_tables as $table_name) {
                    $sql	 = "DROP TABLE `{$this->post['dbname']}`.`{$table_name}`";
                    if (!$result	 = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$table_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
                $this->drop_tbl_log = count($found_tables);
            }
        }
    }

    private function dropProcs(){
        $sql = "SHOW PROCEDURE STATUS";
        $found = null;
        if ($result = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found[] = $row[1];
            }
            if (count($found) > 0) {
                foreach ($found as $proc_name) {
                    $sql = "DROP PROCEDURE IF EXISTS `{$this->post['dbname']}`.`{$proc_name}`";
                    if (!$result = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$proc_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
            }
        }
    }

    private function dropViews(){
        $sql = "SHOW FULL TABLES WHERE Table_Type = 'VIEW'";
        $found_views = null;
        if ($result = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found_views[] = $row[0];
            }
            if (count($found_views) > 0) {
                foreach ($found_views as $view_name) {
                    $sql	 = "DROP VIEW `{$this->post['dbname']}`.`{$view_name}`";
                    if (!$result	 = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$view_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
            }
        }
    }

	public function writeLog()
	{
		DUPX_Log::info("ERRORS FOUND:\t{$this->dbquery_errs}");
		DUPX_Log::info("DROPPED TABLES:\t{$this->drop_tbl_log}");
		DUPX_Log::info("RENAMED TABLES:\t{$this->rename_tbl_log}");
		DUPX_Log::info("QUERIES RAN:\t{$this->dbquery_rows}\n");

		$this->dbtable_rows	 = 1;
		$this->dbtable_count = 0;

		if ($result = mysqli_query($this->dbh, "SHOW TABLES")) {
			while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
				$table_rows			 = DUPX_DB::countTableRows($this->dbh, $row[0]);
				$this->dbtable_rows	 += $table_rows;
				DUPX_Log::info("{$row[0]}: ({$table_rows})");
				$this->dbtable_count++;
			}
			@mysqli_free_result($result);
		}

		DUPX_Log::info("Removed '{$this->dbdelete_count}' cache/transient rows");

		if ($this->dbtable_count == 0) {
			DUPX_Log::info("NOTICE: You may have to manually run the installer-data.sql to validate data input.
             Also check to make sure your installer file is correct and the table prefix 
             '{$GLOBALS['DUPX_AC']->wp_tableprefix}' is correct for this particular version of WordPress. \n");
		}
	}

	public function getJSON($json)
	{
		$json['table_count'] = $this->dbtable_count;
		$json['table_rows']	 = $this->dbtable_rows;
		$json['query_errs']	 = $this->dbquery_errs;

		return $json;
	}

	private function applyCollationFallback()
	{
		if (!empty($this->post['dbcolsearchreplace']) && $this->post['dbcollatefb']) {
			$collation_replace_list = json_decode(stripslashes($this->post['dbcolsearchreplace']), true);

			if ($collation_replace_list === NULL) {
				DUPX_Log::info("WARNING: Cannot decode collation replace list JSON.\n", 1);
				return;
			}

			if (!empty($collation_replace_list)) {

				if ($this->firstOrNotChunking()) {
					DUPX_Log::info("LEGACY COLLATION FALLBACK:\n\tRunning the following replacements:\n\t" . stripslashes($this->post['dbcolsearchreplace']));
				}

				foreach ($collation_replace_list as $val) {
					$replace_charset = false;
					if (strpos($val['search'], 'utf8mb4') !== false && strpos($val['replace'], 'utf8mb4') === false) {
						$replace_charset = true;
					}
					foreach ($this->sql_result_data as $key => $query) {
						if(strpos($query, $val['search'])){
							$this->sql_result_data[$key] = str_replace($val['search'], $val['replace'], $query);
							$sub_query = str_replace("\n", '', substr($query, 0, 80));
							DUPX_Log::info("\tNOTICE: {$val['search']} replaced by {$val['replace']} in query [{$sub_query}...]");
						}
						if ($replace_charset && strpos($query, 'utf8mb4')) {
							$this->sql_result_data[$key] = str_replace('utf8mb4', 'utf8', $this->sql_result_data[$key]);
							$sub_query = str_replace("\n", '', substr($query, 0, 80));
							DUPX_Log::info("\tNOTICE: utf8mb4 replaced by utf8 in query [{$sub_query}...]");
						}
					}
				}
			}
		}
	}

	private function delimiterFix($counter){
	    $firstQuery =  trim(preg_replace('/\s\s+/', ' ',$this->sql_result_data[$counter]));
	    $start = $counter;
	    $end = 0;
	    if(strpos($firstQuery,"DELIMITER") === 0){
	        $this->sql_result_data[$start] = "";
            $continueSearch = true;
            while($continueSearch){
                $counter++;
                if(strpos($this->sql_result_data[$counter],'DELIMITER') === 0){
                    $continueSearch = false;
                    unset($this->sql_result_data[$counter]);
                    $this->sql_result_data = array_values($this->sql_result_data);
                }else{
                    $this->sql_result_data[$start] .= $this->sql_result_data[$counter].";\n";
                    unset($this->sql_result_data[$counter]);
                }
            }
        }
    }

	public function nbspFix($sql)
	{
		if ($this->post['dbnbsp']) {
			if ($this->firstOrNotChunking()) {
				DUPX_Log::info("ran fix non-breaking space characters\n");
			}
			$sql = preg_replace('/\xC2\xA0/', ' ', $sql);
		}
		return $sql;
	}

	public function firstOrNotChunking()
	{
		return (!isset($this->post['continue_chunking']) || $this->post['first_chunk']);
	}

	public function __destruct()
	{
		@mysqli_close($this->dbh);
	}
}