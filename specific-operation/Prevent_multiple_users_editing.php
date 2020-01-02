<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Prevent_multiple_users_editing extends CI_Controller {

    private $_table_ext = '_request';
    private $_time_locked_field = 'time_locked';
    private $_user_id_field = 'user_id';
    private $_forward_minutes = 5;
    private $_one_minute = 60;

    function __construct(){
        parent::__construct();
    }

    public function update_time_locked(){
        if($this->input->post('report_type') == ''){
            $table_first_portion = '';
        }else{
            $table_first_portion = $this->input->post('report_type') == '' || $this->input->post('report_type') == '' ? 'prepurchase' : $this->input->post('report_type');
        }
        $data_array = array(
          $this->_user_id_field => intval($this->session->userdata('logged_user_id')),
          $this->_time_locked_field =>  time() + ($this->_forward_minutes * $this->_one_minute)
        );

        if($this->input->post('report_type') == '' || $this->input->post('report_type') == ''){
            $this->_table_ext = '_report';
        }

        $this->db->update($table_first_portion.$this->_table_ext, $data_array, array('id' => intval($this->input->post('request_id'))));

        return;

    }
}
