<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		Esen Sagynov
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 2.0.2
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * CUBRID Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		Esen Sagynov
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_dynamo_result extends CI_DB_result {
    
    
        function num_rows()
	{
		//return @mysql_num_rows($this->result_id);
	}
        
        /**
	 * Result - associative array
	 *
	 * Returns the result set as an array
	 *
	 * @access	private
	 * @return	array
	 */
	function _fetch_assoc()
	{
		return mysql_fetch_assoc($this->result_id);
	}
        
        function _data_seek($n = 0)
	{
		//return mysql_data_seek($this->result_id, $n);        
        }
        
        function _fetch_assoc()
	{
		//return mysql_fetch_assoc($this->result_id);
	}
        
        function _fetch_object()
	{
		//return mysql_fetch_object($this->result_id);
	}
	
}
