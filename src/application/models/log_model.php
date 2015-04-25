<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log_model extends CI_Model {

	function __construct()
	{
		parent::__construct();
	}

    public function is_duplicate($params)
    {
        return $this->db->get_where('log',$params);
    }
    public function insert_unique($row)
    {
        if(!$this->is_duplicate($row))$this->db->insert('log',$row);
    }
    public function count_all()
    {
        return $this->db->count_all_results('log');
    }
    public function schema()
    {
        return array(
            'id'=>array('type'=>'int', 'auto_increment'=>TRUE ),
            'message'=>array('type'=>'text','constraint'=>75,'fillable'=>1),
            'details'=>array('type'=>'textarea','fillable'=>1),
            );
    }
}
?>
