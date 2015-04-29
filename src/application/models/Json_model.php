<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Json_model extends CI_Model {

	function __construct()
	{
		parent::__construct();
	}
    public function _get_json($path_to_file)
    {
        $this->load->helper('file');
        $batch = json_decode(read_file($path_to_file),TRUE);
        return $batch;
    }
    public function get_formats()
    {
        return $this->_get_json('./../db/format.json');
    }
    public function get_batch()
    {
        return $this->_get_json('./../db/batch.json');
    }
    public function get_config()
    {
        return $this->_get_json('./../db/config.json');
    }
}
?>
