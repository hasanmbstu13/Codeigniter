<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';
use mikehaertl\wkhtmlto\Pdf;

class Dropbox_upload extends CI_Controller {

    const DS = '/';

    /**
     * @var date
     */
    private $_year_month = false;

    /**
     * @var string
     */
    private $_token = false;

    /**
     * @var array
     */
    private $_params = array();
    /**
     * @var bool|string
     */
    private $_completed_reports_master_folder = false;
    /**
     * @var bool
     */
    private $_table_name = false;
    /**
     * @var bool|string
     */
    private $_job_queue_table = false;
    /**
     * @var bool
     */
    private $_folder_name = false;
    /**
     * @var bool
     */
    private $_destination_folder_path = false;
    /**
     * @var int
     */
    private $_main_request_id = false;
    /**
     * @var string
     */
    private $_doc_relative_path = APPPATH . '../phpdocx/classes/CreateDocx.inc';
    /**
     * @var string
     */
    private $_claim_number = false;
    /**
     * @var string
     */
    private $_model = false;

    /**
     * @var string
     */
    private $_report_type = '';

    /**
     * @var string
     */
    private $_data_type = '';

    /**
     * @var bool
     */
    private $_inspector_type = false;

    /**
     * @var bool
     * Instance of PDF
     */
    private $_pdf = false;

    /**
     * @var bool | string
     */
    private $_pdf_file_path = false;

    /**
     * @var bool | string
     */
    private $_docx_file_path = false;

    /**
     * @var int
     */
    private $_http_success_code = 200;

    /**
     * @var bool | string
     */
    private $_invoice_file_path = false;

    /**
     * @var bool | int
     */
    private $_user_id = false;

    /**
     * @var bool | string
     */
    private $_pdf_shared_link = false;

    /**
     * @var bool | string
     */
    private $_docx_shared_link = false;

    /**
     * @var bool
     */
    private $_send_pdf_file = false;

    /**
     * @var bool
     */
    private $_send_docx_file = false;

    /**
     * @var int
     */
    private $_file_size_limit = 9;

    /**
     * @var int
     */
    private $_divider_value = 1024;

    /**
     * @var bool
     */
    private $_dropbox_pdf_file_path = false;

    /**
     * @var bool
     */
    private $_dropbox_docx_file_path = false;

    /**
     * @var bool|Pdf
     */
    private $_invoice_pdf = false;

    /**
     * @var bool | string
     */
    private $_report_status = false;

    /**
     * @var bool|string
     */
    private $_new_reports_folder = false;

    /**
     * @var bool | string
     */
    private $_file_upload_dir = false;

    /**
     * @var bool | string
     */
    private $_upload_images = false;

    /**
     * @var bool | string
     */
    private $_images_master_folder = false;

    /**
     * @var bool
     */
    private $_image_successfully_uploaded = false;

    /**
     * @var bool
     */
    private $_have_images = false;

    function __construct(){
        parent::__construct();
        // create pdf instance
        $this->_pdf = new Pdf();
        $this->_invoice_pdf = new Pdf();

        if(strpos(gethostname(), '.local') !== false){
            // Local Test
            $this->_token = '';
        }else{
            // Server Test
            $this->_token = '';
        }

        $this->_params['token'] = $this->_token;
        $this->_completed_reports_master_folder = 'project_completed_reports';
        $this->_new_reports_folder = 'project_open_reports';
        $this->_images_master_folder = 'project_images';
        $this->_job_queue_table = 'project_dropbox_job_queue';
        $this->_year_month = date('Y_m');

        $this->load->model(array('project_dropbox_job_queue_model'));
        $this->load->model(array('user_model'));

        // Load dropbox library
        $this->load->library('dropbox', $this->_params);
    }

    public function extract_file_info(){
        $upload_file_info_query = $this->project_dropbox_job_queue_model->get();
        $upload_file_info = $upload_file_info_query->row();
        return $upload_file_info;
    }

    public function create_invoice_pdf(){
        $this->_invoice_pdf->addPage(site_url() . "/report/{$this->_report_type}/invoice/{$this->_main_request_id}/print");
        $this->_invoice_file_path = APPPATH . '..'.self::DS.'exports'.self::DS.$this->_report_type.'_invoice_'.$this->_claim_number.'.pdf';
        if (!$this->_invoice_pdf->saveAs($this->_invoice_file_path)) {
            log_message('error', $this->_invoice_pdf->getError());
        }
        return true;
    }

    public function create_doc_pdf_file($is_special = ''){


        if ($this->_main_request_id) {

            $this->load->model(array($this->_model));

            // check for valid report id
            $request_query = $this->{$this->_model}->get_by_id($this->_main_request_id);

            if (!$request_query->num_rows()) {
                // fake report id
                log_message('error', 'Sorry, Requested report is not found.');
            } else {
                // get report images
                $this->db->order_by('id');
                $this->data['report_images'] = $this->db->get_where(
                    'photofiles',
                    array(
                        'table_name' => $this->_table_name,
                        'request_id' => $this->_main_request_id,
                        'photofile_type' => 'img'
                    )
                );

                // Create and save doc file
                require_once $this->_doc_relative_path;

                $html = file_get_contents(site_url() . "/report/".$this->_report_type."/print-report/{$this->_main_request_id}/img");

                $docx = new CreateDocx();
                $docx->embedHTML($html, array('downloadImages' => true));

                if(!is_dir($this->_file_upload_dir)){
                    mkdir($this->_file_upload_dir, '0755', true);
                }


                if(is_dir($this->_file_upload_dir)){
                    $docx->createDocx($this->_docx_file_path);
                }else{
                    log_message('error', 'File is not created, directory maybe is not exists');
                    return false;
                }

//                $pdf = new Pdf();

                $this->_pdf->addPage(site_url() . "/report/{$this->_report_type}/print-report/{$this->_main_request_id}/img");
                if (!$this->_pdf->saveAs($this->_pdf_file_path)) {
                    log_message('error', $this->_pdf->getError());
                    return false;
                }

                // take all request + report data of corresponding model
//                $report_data = $request_query->row();
//
//                if($this->_report_type == 'bbb'){
//                    $this->data['bbb_questions'] = $this->bbb_model->get_questions_by_id($this->_main_request_id);
//                    $this->data['bbb_problems'] = $this->bbb_model->get_problems_by_id($this->_main_request_id);
//                }
//                if($this->_inspector_type){
//                    if ($report_data->{$this->_inspector_type}) {
//                        $this->data['tech_info'] = $this->user_model->get_tech_info($report_data->{$this->_inspector_type});
//                    } else {
//                        $this->data['tech_info'] = false;
//                    }
//                }
//
//                $this->data['is_special'] = $is_special;
//                $this->data[$this->_data_type] = $report_data;
//                $this->load->view('report'.self::DS.$this->_report_type.self::DS.'print', $this->data);
                return true;
            }
        } else {
            log_message('error', 'Sorry, Requested report is not found.');
            return false;
        }
    }

    public function get_file_size($file_path){
        return round((filesize($file_path) / $this->_divider_value) / $this->_divider_value);
    }

    public function extract_new_report_info(){
        $upload_file_info_query = $this->project_dropbox_job_queue_model->get_new_report();
        $upload_file_info = $upload_file_info_query->row();
        return $upload_file_info;
    }

    public function send_email_customer(){
        $this->load->model(array('email_model'));

        // get client email
        $client_query = $this->user_model->get_by_id( $this->_user_id );
        $client_data = $client_query->row();

        $_report_files = array(
            'to' => $client_data->email,
            'pdf_shared_link' => $this->_pdf_shared_link,
            'docx_shared_link' => $this->_docx_shared_link,
            'claim_no' => $this->_claim_number,
            'send_pdf_file' => $this->_send_pdf_file,
            'send_docx_file' => $this->_send_docx_file,
            'pdf_file' => $this->_pdf_file_path,
            'docx_file' => $this->_docx_file_path.'.docx',
            'invoice_file' => $this->_invoice_file_path,
            'event' => 'Completed report documents',
            'request_type' => strtoupper($this->_report_type)
        );
        if($this->email_model->send_report_files($_report_files)){
            return true;
        }else{
            log_message('error', "Something goes wrong with sending email to the customer");
            return false;
        }
    }

    public function send_email_process(){
        // Send email to the user - PDF, DOCX, INVOICE, SHARED LINK PATH (PDF,DOCX)
        if($this->get_file_size($this->_pdf_file_path) < $this->_file_size_limit){
            $this->_send_pdf_file = true;
        }
        if($this->get_file_size($this->_docx_file_path.".docx") < $this->_file_size_limit){
            $this->_send_docx_file = true;
        }

        // Create invoice
        $this->create_invoice_pdf();

        // Create Shared Link
        // PDF shared link
        $_pdf_shared_link_res = $this->dropbox->create_shared_link($this->_dropbox_pdf_file_path);
//        var_dump($_pdf_shared_link_res);
//
//        exit;
        if($_pdf_shared_link_res['http_code'] == $this->_http_success_code){
            $_pdf_server_response = json_decode($_pdf_shared_link_res['response']);
            $this->_pdf_shared_link = $_pdf_server_response->url;
//            log_message('info', $this->_pdf_shared_link);
        }

        // DPCX shared link
        $_docx_shared_link_res = $this->dropbox->create_shared_link($this->_dropbox_docx_file_path);
        if($_docx_shared_link_res['http_code'] == $this->_http_success_code){
            $_docx_server_response = json_decode($_docx_shared_link_res['response']);
            $this->_docx_shared_link = $_docx_server_response->url;
//            log_message('info', $_docx_server_response->url);
        }

        // Send email to the customer
        if($this->send_email_customer()){
            unlink($this->_pdf_file_path);
            unlink($this->_docx_file_path.".docx");
            unlink($this->_invoice_file_path);

            return true;
        }

        return false;
    }

    public function populate_file_info($_extract_file_info = null){
        $this->_folder_name = $this->_report_type = $_extract_file_info->report_type;
        $this->_main_request_id = $_extract_file_info->request_id;
        $this->_table_name = $_extract_file_info->table_name;
        $this->_claim_number = $_extract_file_info->report_claim_number;
        $this->_model = $_extract_file_info->model_name;
        $this->_data_type = $_extract_file_info->data_type;
        $this->_user_id = $_extract_file_info->user_id;
        if(!is_null($_extract_file_info->inspector_type)){
            $this->_inspector_type = $_extract_file_info->inspector_type;
        }
        if(!is_null($_extract_file_info->upload_images)){
            $this->_upload_images = $_extract_file_info->upload_images;
        }
    }

    public function log_dropbox_folder_error_msg($folder_name, $server_res){
        log_message('error', 'Http Code: '.$server_res['http_code'].' Server Response: '.$server_res['response']. ' Custom Message : '.$folder_name.' folder is not exists or program is not able to create new folder');
        return false;
    }

    public function log_dropbox_file_upl_error_msg($server_res, $file_type = null){
        log_message('error', 'Http Code : '.$server_res['http_code'].' Server Response : '.$server_res['response']. '  Custom Message : '.$file_type.' file is not uploaded on dropbox successfully');
    }

    public function destination_folder($destination_folder_path = false){
        // Create destination folder
        $destination_folder_res = $this->dropbox->create_folder($destination_folder_path);
        if($destination_folder_res['http_code'] != $this->_http_success_code){
            $this->log_dropbox_folder_error_msg($this->_folder_name, $destination_folder_res);
            return false;
        }
        return true;
    }

    public function check_dropbox_upload_folder($folder_arr = array()){
        if($this->dropbox->folder_exists($folder_arr['destination_folder'])['http_code'] == $this->_http_success_code){
            return true;
        }else{

            // Check destination upload folder is exists
            if($this->dropbox->folder_exists($folder_arr['destination_folder'])['http_code'] != $this->_http_success_code){
                // Create destination folder
                if($this->destination_folder($folder_arr['destination_folder'])){
                    return true;
                }
            }

            // Create folder if year month folder not exists
            if($this->dropbox->folder_exists($folder_arr['year_month'])['http_code'] != $this->_http_success_code){
                // Create year month folder
                $year_month_folder_res = $this->dropbox->create_folder($folder_arr['year_month']);
                if($year_month_folder_res['http_code'] != $this->_http_success_code){
                    $this->log_dropbox_folder_error_msg($this->_year_month, $year_month_folder_res);
                }


                if($year_month_folder_res['http_code'] == $this->_http_success_code) {
                    // Create destination folder
                    if($this->destination_folder($folder_arr['destination_folder'])){
                        return true;
                    }
                }
            }

            // Check root folder is exists otherwise create new one
            if($this->dropbox->folder_exists( $folder_arr['master_folder'])['http_code'] != $this->_http_success_code){

                $master_folder_res = $this->dropbox->create_folder($folder_arr['master_folder']);
                if($master_folder_res['http_code'] != $this->_http_success_code){
                    $this->log_dropbox_folder_error_msg($this->_completed_reports_master_folder, $master_folder_res);
                }

                if ($master_folder_res['http_code'] == $this->_http_success_code) {

                    // Create year month folder
                    $year_month_folder_res = $this->dropbox->create_folder($folder_arr['year_month']);
                    if($year_month_folder_res['http_code'] != $this->_http_success_code){
                        $this->log_dropbox_folder_error_msg($this->_year_month, $year_month_folder_res);
                    }
                }

                if($year_month_folder_res['http_code'] == $this->_http_success_code) {

                    if($this->destination_folder($folder_arr['destination_folder'])){
                        return true;
                    }
                }
                return false;
            }
            return false;
        }// end else
    }

    public function delete_dropbox_record($job_queue_id = null){
        // Delete the record, if file upload is successful
        $update_job_queue_table_data = array(
            'deleted' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->update($this->_job_queue_table, $update_job_queue_table_data, array('id' => $job_queue_id));
    }

    public function upload_pdf_docx_dropbox($master_folder = null){
        if($this->create_doc_pdf_file()){
            // Check both docx and pdf file exists
            $destination_folder_path = self::DS.$master_folder.self::DS.$this->_year_month.self::DS.$this->_folder_name;
            $folder_lists = array(
                'master_folder' => self::DS.$master_folder,
                'year_month' => self::DS.$master_folder.self::DS.$this->_year_month,
                'destination_folder' => $destination_folder_path
            );
            // Check dropbox destination folder exists
            if($this->check_dropbox_upload_folder($folder_lists)){
                // Upload pdf file
                $upload_file_info = array(
                    'file' => $this->_pdf_file_path,
                    'file_name' => $this->_claim_number.".pdf",
                    'folder_name' => $destination_folder_path.self::DS
                );
                $pdf_upload_res = $this->dropbox->upload_file($upload_file_info);
//                    var_dump($pdf_upload_res);
//                    exit;
                if($pdf_upload_res['http_code'] == $this->_http_success_code){
                    $this->_dropbox_pdf_file_path = $destination_folder_path.self::DS.$this->_claim_number.".pdf";
                }else{
                    $this->log_dropbox_file_upl_error_msg($pdf_upload_res, 'PDF');
                    return false;
                }

                // upload DOCX file
                $upload_docx_file_info = array(
                    'file' =>$this->_docx_file_path.".docx",
                    'file_name' => $this->_claim_number.".docx",
                    'folder_name' => $destination_folder_path.self::DS
                );
                $upload_docx_file_res = $this->dropbox->upload_file($upload_docx_file_info);
                if($upload_docx_file_res['http_code'] == $this->_http_success_code){
                    $this->_dropbox_docx_file_path = $destination_folder_path.self::DS.$this->_claim_number.".docx";
                }else{
                    $this->log_dropbox_file_upl_error_msg($upload_docx_file_res, 'DOCX');
                    return false;
                }

                return true;

            }
        }// endif create doc pdf file

        return false;
    }

    public function index(){
        $_extract_file_info = $this->extract_file_info();
        // Check if relevant information find for a single file then start uploading process of a file
        if(is_object($_extract_file_info)){
            // Update is_processing status to avoid conflicts between other cron job
            $update_job_queue_table_data = array(
                'is_processing' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->update($this->_job_queue_table, $update_job_queue_table_data, array('id' => $_extract_file_info->job_queue_id));

            // Populate file info
            $this->populate_file_info($_extract_file_info);
            $this->_file_upload_dir = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox';
            $this->_docx_file_path = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox'.self::DS.$this->_claim_number;
            $this->_pdf_file_path = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox'.self::DS."{$this->_claim_number}.pdf";
//            $this->_destination_folder_path = self::DS.$this->_completed_reports_master_folder.self::DS.$this->_year_month.self::DS.$this->_folder_name;

            if($this->upload_pdf_docx_dropbox($this->_completed_reports_master_folder)){
                // Upload images if have
                if($this->_upload_images){
                    // Check both docx and pdf file exists
                    $destination_folder_path = self::DS.$this->_images_master_folder.self::DS.$this->_year_month.self::DS.$this->_folder_name;
                    $folder_lists = array(
                        'master_folder' => self::DS.$this->_images_master_folder,
                        'year_month' => self::DS.$this->_images_master_folder.self::DS.$this->_year_month,
                        'destination_folder' => $destination_folder_path
                    );

                    // load all related images
                    $images = $this->db->get_where(
                        'photofiles',
                        array(
                            'table_name' => $this->_table_name,
                            'request_id' => $this->_main_request_id,
                            'photofile_type' => 'img'
                        )
                    )->result();
                    if(count($images) > 0){
                        $this->_have_images = true;
                        // Check dropbox destination folder exists
                        if($this->check_dropbox_upload_folder($folder_lists)){
                            foreach($images as $image_data){
                                $folder_name_arr = explode('_', $image_data->table_name);
                                $folder_name = $folder_name_arr[0];

                                // upload image
                                if ( !empty($image_data->photofile_name) && file_exists(APPPATH . "../uploads/{$image_data->photofile_name}")) {
                                    // upload image file
                                    $upload_image_file_info = array(
                                        'file' =>APPPATH . "../uploads/{$image_data->photofile_name}",
                                        'file_name' => $image_data->photofile_name,
                                        'folder_name' => $destination_folder_path.self::DS
                                    );
                                    $upload_image_file_res = $this->dropbox->upload_file($upload_image_file_info);
                                    if($upload_image_file_res['http_code'] != $this->_http_success_code){
                                        log_message('error', 'Http Code : '.$upload_image_file_res['http_code'].' Server Response : '.$upload_image_file_res['response']. '  Custom Message : '.$image_data->photofile_name.' file is not uploaded on dropbox successfully');
                                        $this->_image_successfully_uploaded = false;
                                        break;
                                    }
                                }
                                if(!$this->_image_successfully_uploaded){
                                    $this->_image_successfully_uploaded = true;
                                }

                            }// end foreach

                        }
                    }

                }// end if root upload images
            }

            if($this->_dropbox_docx_file_path && $this->_dropbox_pdf_file_path){
                if($this->send_email_process()){
                    if($this->_upload_images && $this->_have_images){
                        if($this->_image_successfully_uploaded){
                            $this->delete_dropbox_record($_extract_file_info->job_queue_id);
                        }else{
                            log_message('error', 'Images are not uploaded successfully');
                            exit;
                        }
                    }// upload image condition
                    $this->delete_dropbox_record($_extract_file_info->job_queue_id);
                } // end email process
            }// check docx and pdf file exists

        }else{
            log_message('error', 'No record is found for file uploading');
        }
    }

    public function upload_new_report(){
        $_extract_file_info = $this->extract_new_report_info();
        // Check if relevant information find for a single file then start uploading process of a file
        if(is_object($_extract_file_info)){
            // Update is_processing status to avoid conflicts between other cron job
            $update_job_queue_table_data = array(
                'is_processing' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->update($this->_job_queue_table, $update_job_queue_table_data, array('id' => $_extract_file_info->job_queue_id));

            // Populate file info
            $this->populate_file_info($_extract_file_info);
            $this->_file_upload_dir = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox'.self::DS.'new_reports';
            $this->_docx_file_path = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox'.self::DS.'new_reports'.self::DS.$this->_claim_number;
            $this->_pdf_file_path = APPPATH . '..'.self::DS.'exports'.self::DS.'dropbox'.self::DS.'new_reports'.self::DS."{$this->_claim_number}.pdf";
//            $this->_destination_folder_path = self::DS.$this->_new_reports_folder.self::DS.$this->_year_month.self::DS.$this->_folder_name;

           if($this->upload_pdf_docx_dropbox($this->_new_reports_folder)){
               if(file_exists($this->_pdf_file_path)){
                   @unlink($this->_pdf_file_path);
               }
               if(file_exists($this->_docx_file_path.".docx")){
                   @unlink($this->_docx_file_path.".docx");
               }
               $this->delete_dropbox_record($_extract_file_info->job_queue_id);
           }

        }else{
            log_message('error', 'No record is found for file uploading');
        }
    }
}
