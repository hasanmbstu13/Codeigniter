<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Magazine extends CI_Controller {
	/**
	* Index page for Magazine controller.
	*/
	public function index() {
		/*// echo '<h2>My magazines</h2>';
		$this->load->model('Publication');
		$this->Publication->publication_name = 'Sandy Shore'; // create the record
		$this->Publication->save();
		// print_r($this->Publication);
		echo '<tt><pre>' . var_export($this->Publication,TRUE) . '<pre><tt>';

		$this->load->model('Issue');
		$issue = new Issue();
		$issue->publication_id = $this->Publication->publication_id;
		$issue->issue_number = 2;
		$issue->issue_date_publication = date('2015-02-01');
		$issue->save();
		echo '<tt><pre>' . var_export($issue,TRUE) . '<pre><tt>';

		$this->load->view('magazines');
		// $this->load->view('magazines');*/

		$data = array();

		$this->load->model('Publication');
		$publication = new Publication();
		$publication->load(2);
		$data['publication'] = $publication;

		$this->load->model('Issue');
		$issue = new Issue();
		$issue->load(2);
		$data['issue'] = $issue;

		$this->load->view('magazines');
		$this->load->view('magazine',$data);

		// echo "<pre>";
		// echo print_r($data);
		// echo "</pre>";

	}
}

?>