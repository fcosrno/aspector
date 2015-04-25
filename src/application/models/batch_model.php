<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Batch_model extends CI_Model {

	function __construct()
	{
		parent::__construct();
	}
    public function mark($key,$status='complete')
    {
        $this->db->update('batch',array('status'=>$status),array('key'=>$key));
    }
    public function key_exists($key)
    {
        return (bool) $this->db->get_where('batch',array('key'=>$key))->num_rows();
    }
    public function get_pending($limit=null)
    {
        if(!is_null($limit))$this->db->limit($limit);
        $this->db->select('key');
        $query = $this->db->get_where('batch',array('status'=>'pending'));
        return $query->result_array();
    }
    public function count_pending()
    {
        $this->db->where('status','pending');
        $this->db->from('batch');
        return $this->db->count_all_results();
    }
    public function store_pending_images($array)
    {
        foreach($array as $key){
            if(!$this->key_exists($key)){
                $this->db->insert('batch',array('key'=>$key,'status'=>'pending'));
            }
        }
    }

}
?>
