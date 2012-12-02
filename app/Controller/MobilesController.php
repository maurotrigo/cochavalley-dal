<?php
App::import('Sanitize');
App::uses('File', 'Utility');
class MobilesController extends AppController
{
	
	var $components = array('RequestHandler');
        
//APPSEARCH2/////////////////////////////////////////////////////////////////


	function readPlate() {
		$this->autoRender=false;
		//if($this->RequestHandler->isPost()) {
			$file = APP.'dumps'.DS.'var_dump.txt';
			$fileW = new File($file, true);
            $fileW->write(var_dump($_POST));
            $fileW->close();
			echo $file;
			var_dump($_POST);
		//}
	}
	
	
	function getPlateData($plate) {
		$data = array(
			'2477HEP' => array(
				'Placa'=>'2477HEP',
				'Poliza'=>'19941976',
				'Clase'=>'JEEP',
				'Tipo'=>'RAV 4',
				'Marca'=>'TOYOTA',
				'Modelo'=>'1999',
				'Pais'=>'JAPON',
				'Servicio'=>'PARTICULAR',
				'Traccion'=>'(DOBLE)',
				'Cilindrada'=>'1998',
				'Color'=>'BLANCO',
				'Puertas'=>'3',
				'Radicatoria'=>'COCHABAMBA',
				'Tipo'=>'REEMPLACADO',
				'Deudas'=>'0',
				'Gravamenes'=>'1',
				'Observaciones'=>'1',	
			),
			'1146DLB' => array(
				'Placa'=>'1146DLB',
				'Poliza'=>'81640617',
				'Clase'=>'VAGONETA',
				'Tipo'=>'GRAND CHEROKEE',
				'Marca'=>'JEEP',
				'Modelo'=>'2008',
				'Pais'=>'ESTADOS UNIDOS',
				'Servicio'=>'PARTICULAR',
				'Traccion'=>'4 X 4 (DOBLE)',
				'Cilindrada'=>'4700',
				'Color'=>'BLANCO',
				'Puertas'=>'5',
				'Radicatoria'=>'COCHABAMBA',
				'Tipo'=>'REEMPLACADO',
				'Deudas'=>'0',
				'Gravamenes'=>'1',
				'Observaciones'=>'0',	
			),
			'1188XNN' => array(
				'Placa'=>'1188XNN',
				'Poliza'=>'30021634',
				'Clase'=>'JEEP',
				'Tipo'=>'GRAND VITARA',
				'Marca'=>'SUZUKI',
				'Modelo'=>'2003',
				'Pais'=>'JAPON',
				'Servicio'=>'PARTICULAR',
				'Traccion'=>'(DOBLE)',
				'Cilindrada'=>'1590',
				'Color'=>'	PLATA',
				'Puertas'=>'3',
				'Radicatoria'=>'COCHABAMBA',
				'Tipo'=>'REEMPLACADO',
				'Deudas'=>'0',
				'Gravamenes'=>'0',
				'Observaciones'=>'Placa en el almacén',	
			),
			'2906ILS' => array(
				'Placa'=>'2906ILS',
				'Poliza'=>'120433440',
				'Clase'=>'VAGONETA',
				'Tipo'=>'PATROL',
				'Marca'=>'NISSAN',
				'Modelo'=>'2012',
				'Pais'=>'JAPON',
				'Servicio'=>'OFICIAL',
				'Traccion'=>'4 X 4 (DOBLE)',
				'Cilindrada'=>'4759',
				'Color'=>'PLATA',
				'Puertas'=>'5',
				'Radicatoria'=>'LA PAZ',
				'Tipo'=>'REEMPLACADO',
				'Deudas'=>'0',
				'Gravamenes'=>'0',
				'Observaciones'=>'',	
			),
		);
		$this->set('plateData', $data[$plate]);
	}
}
?>