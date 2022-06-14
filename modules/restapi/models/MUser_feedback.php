<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MUser_feedback extends CI_Model {
    
    private $_table = 'ilapv_user_feedback';
    
    public function __construct()	{
        $this->load->database();
    }
    
    public function getAll()
    {
        $this->db->select();
        return $this->db->get($this->_table)->result();
    }
        
    public function insert($data)
    {
        $this->db->insert($this->_table, $data);
        return $this->db->insert_id();
    }
    
    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update($this->_table, $data);
        if ($this->db->affected_rows() !== -1)
            return true;
        return false;
    }
       

    //$this->db->affected_rows() == 0 // Update query was ran successfully but nothing was updated
    //$this->db->affected_rows() == 1 // Update query was ran successfully and 1 row was updated
    //$this->db->affected_rows() == -1 // Query failed
    
}