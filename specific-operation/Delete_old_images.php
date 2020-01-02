<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Delete_old_images extends CI_Controller
{
    /**
     * Directory separator
     */
    CONST DS = '/';

    /**
     * @var int
     */
    private $_how_old_days = 6;

    /**
     * @var bool | date
     */
    private $_today = false;

//    private $_photos_dir =

    /**
     * @var bool | string
     */
    private $_folder_name = false;


    function __construct()
    {
        parent::__construct();
        $this->_today = date('Y-m-d');
    }

    public function get_completed_reports($delete_date){
        $this->db->select('*');
        $this->db->from('delete_completed_report_images');
        $this->db->where('date(created_at) <=', $delete_date);
        $this->db->where('deleted', 0);
        return $this->db->get()->result();
    }

    public function get_images($request_id = null, $table_name = null ,$file_type = null){
        $this->db->select('*');
        $this->db->from('photofiles');
        $this->db->where('request_id', $request_id);
        $this->db->where('table_name', $table_name);
        $this->db->where('photofile_type', $file_type);
        return $this->db->get()->result();
    }

    function index(){
        $delete_date = date('Y-m-d', strtotime($this->_today. ' - '.$this->_how_old_days.' days'));

        $deleted_reports = $this->get_completed_reports($delete_date);

        if(count($deleted_reports) > 0){

            foreach ($deleted_reports as $delete_report){

                // Delete complete report images
                $results = $this->get_images($delete_report->request_id, $delete_report->table_name, 'img');
                if(count($results) > 0){
                    foreach ($results as $image_data){
                        $folder_name_arr = explode('_', $image_data->table_name);
                        $folder_name = $folder_name_arr[0];

                        // remove original image
                        if ( !empty($image_data->photofile_name) && file_exists(APPPATH . "../uploads/{$image_data->photofile_name}")) {
                            @unlink(APPPATH . "../uploads/{$image_data->photofile_name}");
                        }

                        // remove large version

                        if ( !empty($image_data->photofile_name) && file_exists(APPPATH . "../photos/{$folder_name}/{$image_data->photofile_name}")) {
                            @unlink(APPPATH . "../photos/{$folder_name}/{$image_data->photofile_name}");
                        }

                        // remove thumbnail version

                        if ( !empty($image_data->photofile_tn_name) && file_exists(APPPATH . "../photos/{$folder_name}_tn/{$image_data->photofile_tn_name}")) {
                            @unlink(APPPATH . "../photos/{$folder_name}_tn/{$image_data->photofile_tn_name}");
                        }
                    } // end foreach

                    $this->db->delete('photofiles',
                        array(
                            'id' => $image_data->id
                        )
                    );

                }
                $update_data = array(
                    'deleted' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                );

                $this->db->update(
                    'delete_completed_report_images',
                    $update_data,
                    array('id' => $delete_report->id)
                );

            } // end completed reports count foreach

        }// end completed reports count if


    }

}