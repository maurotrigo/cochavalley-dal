<?php
class MessagesController extends AppController {
	
	public $components = array('RequestHandler');
	
	public function get(){
        $messages = $this->Message->find('all');
		$this->set('messages', $messages);
	}
	
    public function add($message) {
		$this->Message->save(array("message"=>$message));
		$this->autoRender = false;
	    echo "test";
    }
}