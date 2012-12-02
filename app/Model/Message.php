<?php

class Message extends AppModel{
  public $validate = array(
    'type' => array(
        'rule' => 'notEmpty'
    ),
    'place' => array(
        'rule' => 'notEmpty'
    )
  );
  
}