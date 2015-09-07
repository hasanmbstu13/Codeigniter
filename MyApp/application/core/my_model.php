<?php

class MY_Model extends CI_Model {
	const DB_TABLE = 'abstract';
	const DB_TABLE_PK = 'abstract';

	// Create record.
	// insert method takes two arguments the name of the table as string, and second is values
	private function insert() {
		$this->db->insert($this::DB_TABLE,$this);
		$this->{$this::DB_TABLE_PK} = $this->db->insert_id();
	}

	// Update record.
	// Takes three arguments table name, array or object to the function, the third paramete is the where key or primary key of the table.
	private function update() {
		$this->db->update($this::DB_TABLE,$this,$this::DB_TABLE_PK);
	}

	// Populate from an array or standard class.
	// param mixed $row
	public function populate($row) {
		foreach ($row as $key => $value) {
			$this->key = $value;
		}
	}

	// loading a single from the database into model that takes one argument
	// Load from the database.
	// param int $id
	// get_where permits us to add where clause in the second parameter.
	public function load($id) {
		$query = $this->db->get_where($this::DB_TABLE,array($this::DB_TABLE_PK => $id));
		$this->populate($query->row());
	}

	//Delete the current record.
	public function delete() {
		$this->db->delete($this::DB_TABLE,array($this::DB_TABLE_PK => $this->{$this::DB_TABLE_PK},));
		unset($this->{$this::DB_TABLE_PK});
	} 

	// Save the record.
	public function save() {
		if(isset($this->{$this::DB_TABLE_PK})) {
			$this->update();
		}
		else {
			$this->insert();
		}
	}

	// Get an array of Models with an optional limit, offset.
	// @param int $limit optional.
	// @param int offset optional; if set, requires $limit
	// @return array models populated by database, keyed by pk.
	public function get($limit = 0, $offset = 0) {
		if($limit) {
			$query = $this->db->get($this::DB_TABLE,$limit, $offset);
		}
		else {
			$query = $this->db->get($this::DB_TABLE); // which wil returns all rows
		}
		$ret_val = array();
		$class = get_class($this); // get class of the current model
		// we iterative the query using method result which will return database rows
		foreach ($query->result() as $row) {
			$model = new $class;
			$model->populate($row); //populate the helper function with row
			// added to the return value using the primary key
			$ret_val[$row->{$this::DB_TABLE_PK}] = $model;
		}
		return $ret_val;
	}
}
