<?php
App::import('Sanitize');
App::uses('File', 'Utility');
class MobilesController extends AppController
{
	
	var $components = array('RequestHandler');
        
	function readPlate($nr) {
		
		//Primero accedemos a la nube para realizar la decodificacion y lectura de la placa
		$applicationId = 'LicensePlateReader';
		$password = 'q1NBwTQz3KnMRHh/96qKIa2w ';
		
		$fileName = 'p'.$nr.'.jpg';
		$filePath =APP.'webroot'.DS.'files'.DS.$fileName;
		if(!file_exists($filePath))
		{
			die('File '.$filePath.' not found.');
		}
		$url = 'http://cloud.ocrsdk.com/processTextField?letterSet=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&regExp=[0-9]+[A-Z]+&textType=ocrB&oneTextLine=true';
		// Send HTTP POST request and ret xml response
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_USERPWD, "$applicationId:$password");
		curl_setopt($curlHandle, CURLOPT_POST, 1);
		$post_array = array(
			"my_file"=>"@".$filePath,
		);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $post_array);
		$response = curl_exec($curlHandle);
		if($response == FALSE) {
		  $errorText = curl_error($curlHandle);
		  curl_close($curlHandle);
		  die($errorText);
		}
		curl_close($curlHandle);
		
		// Parse xml response
		$xml = simplexml_load_string($response);
		$arr = $xml->task[0]->attributes();
		
		// Task id
		$taskid = $arr["id"];
		
		// 4. Get task information in a loop until task processing finishes
		// 5. If response contains "Completed" staus - extract url with result
		// 6. Download recognition result (text) and display it
		
		$url = 'http://cloud.ocrsdk.com/getTaskStatus';
		$qry_str = "?taskid=$taskid";
		
		// Check task status in a loop until it is finished
		do
		{
		  sleep(5);
		  $curlHandle = curl_init();
		  curl_setopt($curlHandle, CURLOPT_URL, $url.$qry_str);
		  curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($curlHandle, CURLOPT_USERPWD, "$applicationId:$password");
		  $response = curl_exec($curlHandle);
		  curl_close($curlHandle);
		
		  // parse xml
		  $xml = simplexml_load_string($response);
		  $arr = $xml->task[0]->attributes();
		}
		while($arr["status"] != "Completed");
		
		// Result is ready. Download it
		
		$url = $arr["resultUrl"];
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curlHandle);
		curl_close($curlHandle);
		//encontramos el numero de placa en la respuesta
		$valuepos = strpos($response , "utf-16");
		$plate =  substr($response,$valuepos+8,7);
		//llamamos al acceso a la base de la policia con la placa devuelta por el OCR en la nube
		$this->set('plateData', $this->findPlate($plate));
	}
	
	
	function getPlateData($plate) {
		//llamamos al acceso a la base de la policia
		$this->set('plateData', $this->findPlate($plate));
	}
	private function findPlate($platenumber) {
		//Aqui simulamos un acceso a la base de datos de la policia nacional o del RUAT, esta mini base estática es un extracto de la base disponible en la página: http://www.ruat.gob.bo/
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
				'Observaciones'=>'Robado, en captura',	
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
		if(array_key_exists ($platenumber , $data))
		{
			return $data[$platenumber];
		}
		else
		{
			return array();
		}
		
	}
}
?>