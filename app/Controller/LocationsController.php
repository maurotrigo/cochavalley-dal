<?php
class LocationsController extends AppController {
	
	public $components = array('RequestHandler');	

	
	public function get(){
        $locations = $this->Location->find('all');
		$this->set('locations', $locations);
	}
	
    public function add() {
		$this->Location->save($this->request->data);
		$this->autoRender = false;
	    echo "test";
    }
}