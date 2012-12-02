<?php
App::import('Sanitize');
class MobilesController extends AppController
{
    var $uses = 'Item';
	var $amerpagesGlobal = null;
	var $components = array('RequestHandler', 'Email');
        
	function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->deny('appClaim', 'appReview');           

	}
	    
	function beforeRender() {
	}	
//APPSEARCH2/////////////////////////////////////////////////////////////////


	function readPlate() {
		if($this->RequestHandler->isPost() {
			var_dump($_POST);
		}
	}
	
	function appsearch() {
		$this->layout="xml";
		$latSearch = true;
		if (isset($this->passedArgs['lat']))
        {
            $lat = $this->passedArgs['lat'];
        } 
		
		if (isset($this->passedArgs['lng']))
        {
            $lng = $this->passedArgs['lng'];
        }
		if (isset($this->passedArgs['funct']))
		{
			$query = $this->passedArgs['funct'];
		}
		else
		{
			$query = 'kw';
		}
		if (isset($this->passedArgs['cityid']))
		{
			$cityid = $this->passedArgs['cityid'];
			$this->loadModel('Location');
			$this->Location->recursive = -1;
			$loc = $this->Location->find('first', array('conditions' => 'Location.id ='.$cityid, 'fields' => array('id','location_code','url_name','location_type')));
			$officialCity = $this->Item->City->find('first', array('conditions' => array("City.id =".substr($loc['Location']['location_code'],2)), 'contain' => array('Country' => array('fields'=> array('id', 'eng', 'url_name', 'name')))));
			$country = $officialCity['Country']['url_name'];
			$city = $officialCity['City']['spa'];
			$modelSearchOfficialCity = 'City';
			$latSearch = false;
			
		}
		$useSphinx = true;
		if($query == 'pr'){
			$useSphinx = false;
		}
		//$query == 'xy' 'cy' 'kw' 'pr'
		if($latSearch)
		{
			App::import('Xml');
			$url = "http://maps.googleapis.com/maps/api/geocode/xml?latlng=".$lat.",".$lng."&sensor=false";
			$parsed_xml =& new XML($url);
			$parsed_xml = Set::reverse($parsed_xml);
			$city='';
			$country='';
			//pr($url);
			foreach($parsed_xml['GeocodeResponse']['Result'] as $result)
			{	
				if(isset($result['Type']))
				{
					if($result['Type'][0] == 'locality' && $result['Type'][1] == 'political')
					{
						foreach($result['AddressComponent'] as $component)
						{
							if($component['Type'][0] == 'locality' && $component['Type'][1] == 'political')
							{
								$city = $component['long_name'];
							}
							if($component['Type'][0] == 'country' && $component['Type'][1] == 'political')
							{
								$country = $component['long_name'];
							}
						}		
					}
				}
			}
			//echo $city.','.$country;
			$modelSearchOfficialCity = 'City';
			$this->Item->City->Behaviors->attach('Containable');
			$officialCity = $this->Item->City->find('first', array('conditions' => array("City.eng LIKE '%".$city."%'", "Country.eng LIKE '%".$country."%'"), 'contain' => array('Country' => array('fields'=> array('id', 'eng', 'url_name', 'name')))));
			if(empty($officialCity)){
				$modelSearchOfficialCity = 'Region';
				$this->Item->Region->Behaviors->attach('Containable');
				$officialCity = $this->Item->Region->find('first', array('conditions' => array("Region.eng LIKE '%".$city."%'", "Country.eng LIKE '%".$country."%'"), 'contain' => array('Country' => array('fields'=> array('id', 'eng', 'url_name', 'name')))));
			}
		}
               // pr($officialCity);
		
		$this->Item->Behaviors->attach('Containable');
		$contain = array(
            'Country' => array(
				'fields' => array('id', 'url_name')
				),
			'Region' => array(
				'fields' => array('id', 'name', 'code')
				),
			'Subregion' => array(
				'fields' => array('id', 'name')
				),			
			'Category1' => array(
				'fields' => array('id', 'eng', 'eng_keywords', 'spa', 'spa_keywords')
				),
			'Category2' => array(
				'fields' => array('id', 'eng', 'eng_keywords', 'spa', 'spa_keywords')
				),
			'Promo' => array(
				'fields' => array(
					'id',
					'title',
					'facevalue',
					'description',
					'image',
					'yousave',
					'validated',
					'current_stock',
					'start_date',
					'end_date',
					'conditions',
					'keywords',
					),
				'Currency' => array(
					'fields' => 'symbol',
					),
				)
			);
		$this->Item->setSource(ItemsCore::getCountrySource($officialCity['Country']['url_name']));
		
		$searchConditions = array();
        $searchConditions['Item.country_id'] = $officialCity[$modelSearchOfficialCity]['country_id'];
		if($modelSearchOfficialCity == 'Region'){
			$searchConditions['Item.region_id'] = $officialCity[$modelSearchOfficialCity]['id'];
		} else {
			$searchConditions['Item.city_id'] = $officialCity[$modelSearchOfficialCity]['id'];
		}
        
		
		if ($query == 'pr')
		{
			$searchConditions[] = 'Item.promos_count > 0';
		}
		
//		$this->paginate['Item']['sphinx']['matchMode'] = SPH_MATCH_EXTENDED;
//		$this->paginate['Item']['sphinx']['sortMode'] = array(SPH_SORT_EXTENDED => 'plan_id DESC, reviews_rating ASC, @relevance DESC');
//		$this->paginate['Item']['sphinx']['index'] = array(
//			ItemsCore::getCountrySource($officialCity['Country']['url_name']),
//		);
		
		
		if (isset($this->passedArgs['keyword'])) {
            App::import('Sanitize');
			$searchKeyword = Sanitize::clean($this->passedArgs['keyword']);
			
			if($query == 'cy')
			{
				if($useSphinx){
					
					/**WJ: Search with macrocategory_id**/
					$this->paginate['Item']['sphinx']['matchMode'] = SPH_MATCH_EXTENDED;
					$this->paginate['Item']['sphinx']['sortMode'] = array(SPH_SORT_EXTENDED => 'plan_id DESC, global_rating DESC, @relevance DESC');
					$index = 'items_argentina_macrocategories';
					if($officialCity['Country']['url_name'] != 'usa'){
						$index = 'items_'.$officialCity['Country']['url_name'].'_macrocategories' ;
					} else { 
						$index = 'items_'.$officialCity['Country']['url_name']. (!empty($officialCity['Region']['code']) ? '_'.strtolower($officialCity['Region']['code']) : '_ny').'_macrocategories';
					}
					
					$this->paginate['Item']['sphinx']['index'] = array($index);
					
					$q = 104;
					$this->paginate['Item']['search'] = '@(country_id)( ^'.$q.' | '.$q.' | '.$q.'$)';
					//WJ: Search in region
					if($modelSearchOfficialCity == 'Region'){
						$q = $officialCity[$modelSearchOfficialCity]['id'];
						$this->paginate['Item']['search'] = '@(region)( ^'.$q.' | '.$q.' | '.$q.'$)';
					} else {						
						$q = $officialCity[$modelSearchOfficialCity]['id'];
						$this->paginate['Item']['search'] = '@(city)( ^'.$q.' | '.$q.' | '.$q.'$)';
					}
					$idMacro = $searchKeyword;
					$this->paginate['Item']['sphinx']['filter'][] = array('macro_id' , $idMacro);
					/*End search*/
					if($latSearch)
					{
						$_latitude = $lat;
						$_longitude = $lng;
						$_radius = 4000;
						$circle = (float) $_radius;
	
						$this->Item->getApi()->SetGeoAnchor('lat', 'lng', (float) deg2rad($_latitude), (float) deg2rad($_longitude)); 
				//		$this->Item->getApi()->SetFilterFloatRange('@geodist', 0.0, $circle);
					}
					
				} else {
					
					//
					$locationId = $officialCity[$modelSearchOfficialCity]['id'];
					$locationType = Configure::read('Configuracion.cityLocationType');

					$this->loadModel('Category');
					$this->Category->Behaviors->attach('Containable');				
					$catcontain = array(
						'CategoryCount' => array(
							'fields' => array('item_count'),
							'conditions' => array('location_id' => $locationId, 'location_type' => $locationType,),
							)
					);

					$options = array(
						'fields' => array('id', 'macrocategory_id', 'lft'),
						'conditions' => array('Category.macrocategory_id = '.$searchKeyword),
						'order' => 'Category.lft ASC',
						'contain' => $catcontain,
					);
					$childrenCategories = $this->Category->find('all', $options);

					$ids = '';
					foreach ($childrenCategories as $childrenCategory) {
						$ids .= $childrenCategory['Category']['id'].', ';
					}
					$ids = substr(trim($ids),0, strlen($ids)-2);
					//pr($ids);

					$searchConditions['OR'] = array(
						'Item.category_1_id IN ('.$ids.')', 
						'Item.category_2_id IN ('.$ids.')', 
						'Item.category_3_id IN ('.$ids.')', 
						//'Item.category_4_id IN ('.$ids.')', 
						//'Item.category_5_id IN ('.$ids.')'
					);
					//end
					
					$locationId = $officialCity[$modelSearchOfficialCity]['id'];
					$locationType = Configure::read('Configuracion.cityLocationType');

					$this->loadModel('Category');
					$this->Category->Behaviors->attach('Containable');				
					$catcontain = array(
						'CategoryCount' => array(
							'fields' => array('item_count'),
							'conditions' => array('location_id' => $locationId, 'location_type' => $locationType,),
							)
					);
					$keywordQuery = "(Category.spa_keywords LIKE '%".$searchKeyword."%' OR Category.eng_keywords LIKE '%".$searchKeyword."%')";
					$options = array(
						'fields' => array('id', 'macrocategory_id', 'lft'),
						'conditions' => array('Category.spa = '.$searchKeyword),
						'conditions' => array($keywordQuery),
						'order' => 'Category.lft ASC',
						'contain' => $catcontain,
					);
				//	pr($options);
					$childrenCategories = $this->Category->find('all', $options);

					$ids = '';
					foreach ($childrenCategories as $childrenCategory) {
						$ids .= $childrenCategory['Category']['id'].', ';
					}
					$ids = substr(trim($ids),0, strlen($ids)-2);
					//pr($ids);

					$searchConditions['OR'] = array(
						'Item.category_1_id IN ('.$ids.')', 
						'Item.category_2_id IN ('.$ids.')', 
						'Item.category_3_id IN ('.$ids.')', 
						'Item.category_4_id IN ('.$ids.')', 
						'Item.category_5_id IN ('.$ids.')'
					);
				}
			}
			else  
			{ 				
				if($useSphinx){
				$this->paginate['Item']['sphinx']['matchMode'] = SPH_MATCH_EXTENDED;
				$this->paginate['Item']['sphinx']['sortMode'] = array(SPH_SORT_EXTENDED => 'plan_id DESC, reviews_rating ASC, @relevance DESC');
				$this->paginate['Item']['sphinx']['index'] = array(
					ItemsCore::getCountrySource($officialCity['Country']['url_name']),
				);

				$this->paginate['Item']['search'] = '@(name) ( ^'.$searchKeyword.' | '.$searchKeyword.' | '.$searchKeyword.'$ )';
				} else {
					$searchConditions['OR'] = array("Match(Item.name) AGAINST('\"".$searchKeyword."\"' IN BOOLEAN MODE)", "Match(Category1.spa) AGAINST('\"".$searchKeyword."\"' IN BOOLEAN MODE)");
				}						
					
			}
		}
		
		
		//pr($searchConditions);
		if($query == 'xy')
		{
			if($useSphinx){
				$this->paginate['Item']['sphinx']['matchMode'] = SPH_MATCH_EXTENDED;
				$this->paginate['Item']['sphinx']['sortMode'] = array(SPH_SORT_EXTENDED => 'plan_id DESC, reviews_rating ASC, @relevance DESC');
				$this->paginate['Item']['sphinx']['index'] = array(
					ItemsCore::getCountrySource($officialCity['Country']['url_name']),
				);
				$searchKeyword = 'a';
				if (isset($this->passedArgs['keyword'])) {
					$searchKeyword = $this->passedArgs['keyword'];
				}
				$this->paginate['Item']['search'] = '@(name,category_1_spa,category_1_spa_keywords,category_2_spa,category_2_spa_keywords,category_1_eng,category_1_eng_keywords,category_2_eng,category_2_eng_keywords) ( ^'.$searchKeyword.' | '.$searchKeyword.' | '.$searchKeyword.'$ )';
				if($latSearch)
				{
					$_latitude = $lat;
					$_longitude = $lng;
					$_radius = 4000;
					$circle = (float) $_radius;
					$this->Item->getApi()->SetGeoAnchor('lat', 'lng', (float) deg2rad($_latitude), (float) deg2rad($_longitude)); 
					$this->Item->getApi()->SetFilterFloatRange('@geodist', 0.0, $circle);
				}
			} else {
				
				$fields = $this->Item->getFieldsApp();
				$x = '110 * ('.$lat.' - Item.lat)';
				$y = '110 * ('.$lng.' - Item.lng) * cos(Item.lat/57.3)';
				array_push($fields, 'sqrt('.$x.' * '.$x.' + '.$y.' * '.$y.') AS `distance`');
				$this->paginate['Item']['fields'] = $fields;
				$this->paginate['Item']['order'] = 'distance ASC';		
			}

		}
		
		
		$this->paginate['Item']['limit'] = 15;
		if(!$useSphinx){
			$this->paginate['Item']['conditions'] = $searchConditions;
		}
		
		$this->paginate['Item']['contain'] = $contain;
		$this->paginate['Item']['limit'] = 15;
				
		$items = $this->paginate('Item');
		
	//	pr($items);
		$AmerpagesList = array('AmerpagesList' => array());
		$PromoList = array('PromoList' => array());
		$pi=0;
		$i=0;
		foreach($items as $item)
		{
			$logo='';
			if($item['Item']['logo']==1)
			{
				$logo = 'http://img0.amerpages.com/items/'.$item['Item']['country_id'].'/'.$item['Item']['country_id'].'_'.$item['Item']['id'].'.jpg';
			}
			
			//BusinessID, PlanID, BusinessName, Rating, BusinessDescription, BusinessLogoURL, Country, Region, Subregion, City, Zone, AddressLine, ZipCode, Latitude, Longitude, MapStatus, TotalReviews, TotalDeals, TotalCatalogs, TotalImages
			if (in_array($officialCity['Country']['url_name'], $this->amerpagesGlobal->getSubregionCountries()))
			{
				$SRegion = $item['Subregion']['name'];
			}
			else
			{
				$SRegion = '';
			}
			/*
			if ($query == 'pr')
			{
				$business = array('BusinessL' => array ('BusinessID' => $item['Item']['id'], 'PlanID' => $item['Item']['plan_id'], 'BusinessName' => htmlspecialchars($item['Item']['name']), 'Rating'=>$item['Item']['reviews_rating'], 'BusinessDescription' => htmlspecialchars($item['Item']['description']), 'BusinessLogoURL' => $logo, 'Country' => $officialCity['Country']['name'], 'Region' => $item['Region']['name'], 'Subregion' => $SRegion, 'City' => $officialCity['City']['name'], 'Zone' => $item['Item']['zone'], 'AddressLine' => htmlspecialchars($item['Item']['address']), 'ZipCode' => $item['Item']['zip_code'], 'Latitude' => $item['Item']['lat'], 'Longitude' => $item['Item']['lng'], 'MapStatus' => $item['Item']['map_status'], 'TotalReviews' => $item['Item']['reviews_count'],'TotalDeals' => $item['Item']['promos_count'],'Deal' => array(),'TotalCatalogs' => $item['Item']['product_services_count'],'TotalImages' => $item['Item']['images_count'],));
				$j=0;
				foreach($item['Promo'] as $promo) {
					//DealID, Title, FaceValue, Description, DealImageURL, YouSave, Currency, Validated, CurrentStock, StartDate, EndDate, Condition*, KeyWords
		
					if($promo['start_date']<=date('Y-m-d') && $promo['end_date']>date('Y-m-d')&&(($promo['current_stock']>0 && $promo['validated']==1)||($promo['validated']==0)))
					{
						if($promo['image']==1)
						{
		
							$imageurl = 'http://amerpages.com/img/items/'.$item['Item']['country_id'].'/'.$item['Item']['id'].'/promos/'.$promo['id'].'.jpg';
						}
						else
						{
							$imageurl = '';
						}
						$business['Deal'][$j] = array('DealID'=>$promo['id'],'Title'=>$promo['title'],'FaceValue'=>$promo['facevalue'],'Description'=>$promo['description'],'DealImageURL'=>$imageurl,'YouSave'=>$promo['yousave'],'Currency'=>$promo['Currency']['symbol'],'Validated'=>$promo['validated'],'CurrentStock'=>$promo['current_stock'],'StartDate'=>$promo['start_date'],'EndDate'=>$promo['end_date'],'Condition'=>array(),'KeyWords'=>$promo['keywords'],);
					
					$k=0;
					foreach(json_decode($promo['conditions']) as $cond)
					{
						$business['Deal'][$j]['Condition'][$k] = $cond;
						$k++;
					}
					$j++;
					}
		
				}

			}
			*/
            $business = array();
			if ($query == 'pr')
			{
				//pr($item);
				foreach($item['Promo'] as $promo) {
                                    
					//DealID, Title, FaceValue, Description, DealImageURL, YouSave, Currency, Validated, CurrentStock, StartDate, EndDate, Condition*, KeyWords
					if($promo['start_date']<=date('Y-m-d') && $promo['end_date']>date('Y-m-d')&&(($promo['current_stock']>0 && $promo['validated']==1)||($promo['validated']==0)))
					{
						if($promo['image']==1)
						{
							$imageurl = 'http://amerpages.com/img/items/'.$item['Item']['country_id'].'/'.$item['Item']['id'].'/promos/'.$promo['id'].'.jpg';
						}
						else
						{
							$imageurl = '';
						}
						if(!empty($this->params['language'])){
							$locale = $this->params['language'].'/';
						}
						else {
							$locale = '';
						}
						$promoClaimUrl = 'http://amerpages.com/mobiles/appclaim';
						$itemCode = ItemsCore::encodeItem($item['Item']['id'], $item['Country']['url_name'], $item['Region']['code']);
						
						$promoL = array('Promo' => array('ID'=>$promo['id'],'ItemID'=>$item['Item']['id'],'ItemCode'=>$itemCode,'Country' => $officialCity['Country']['name'],'Region' => $item['Region']['name'], 'City' => $officialCity[$modelSearchOfficialCity]['name'],'Latitude' => $item['Item']['lat'], 'Longitude' => $item['Item']['lng'], 'MapStatus' => $item['Item']['map_status'], 'Stock'=>$promo['current_stock'],'Validated'=>$promo['validated'],'StartDate'=>$promo['start_date'],'EndDate'=>$promo['end_date'],'Title'=> $this->xmlentities($promo['title']),'ImageURL'=>$imageurl, 'ClaimURL' => $promoClaimUrl ,'Description'=>$this->xmlentities($promo['description']),'FaceValue'=>$this->xmlentities($promo['facevalue']),'YouSave'=>$promo['yousave'],'Currency'=>$promo['Currency']['symbol'],'Condition'=>array(),'KeyWords'=>$promo['keywords'],));
					
						$k=0;
						if(!empty($promo['conditions'])){
							foreach(json_decode($promo['conditions']) as $cond)
							{
								$promoL['Promo']['Condition'][$k] = $cond;
								$k++;
							}
						}
						
						$PromoList['PromoList'][$pi] = $promoL;
						$pi++;
						}
				}
				
				
			}
			else
			{
				$business = array('BusinessL' => array ('BusinessID' => $item['Item']['id'], 'PlanID' => $item['Item']['plan_id'], 'BusinessName' => $this->xmlentities($item['Item']['name']), 'Rating'=>$item['Item']['reviews_rating'], 'BusinessDescription' => $this->xmlentities($item['Item']['description']), 'BusinessLogoURL' => $logo, 'Country' => $officialCity['Country']['name'], 'CountryURL' => $item['Country']['url_name'], 'Region' => $item['Region']['name'], 'RegionCode' => $item['Region']['code'], 'Subregion' => $SRegion, 'City' => $officialCity[$modelSearchOfficialCity]['name'], 'Zone' => $item['Item']['zone'], 'AddressLine' => $this->xmlentities($item['Item']['address']), 'ZipCode' => $item['Item']['zip_code'], 'Latitude' => $item['Item']['lat'], 'Longitude' => $item['Item']['lng'], 'MapStatus' => $item['Item']['map_status'], 'TotalReviews' => $item['Item']['reviews_count'],'TotalDeals' => $item['Item']['promos_count'],'TotalCatalogs' => $item['Item']['product_services_count'],'TotalImages' => $item['Item']['images_count'],));
			}
			$AmerpagesList['AmerpagesList'][$i] = $business;
			$i++;

		}
		if ($query == 'pr')
		{
			$this->set('amerpagesList',$PromoList);
		}
		else
		{
			$this->set('amerpagesList',$AmerpagesList);
		}
		//$this->render('/elements/noreturn');
	}

	function appxml($id = null, $alias = null) {
		$this->layout="xml";
		$idKey = 'id';
		$searchOnRegion = null;
		$itemIsPending = null;			
		if(!$id) {
			$this->Session->setFlash(__('c_ite_invalidBusiness', true));
			$this->redirect($this->referer());
		} else {
			if(!is_numeric($id)) {
				$idKey = 'alias';
			}
		}
		
		$itemSource = $this->_getItemsSource();
		
		if($this->currentConfig['isBigCountry']) {
			$regionId = $this->_getRegion();
			$searchOnRegion = true;
		}
		
		if($itemSource == ItemsCore::getPendingSource()) {
			$itemIsPending = true;
			$this->indexPage = false;
		}
			
		$this->Item->setSource($itemSource);
		
		$this->Item->Behaviors->attach('Containable');
		$contain = $this->Item->getContainApp($this->currentConfig['locale']);
		$fields = $this->Item->getFieldsApp($this->currentConfig['locale']);
		
		if ($itemIsPending) {
			unset($contain['Item']['reviews_count']);	
			unset($contain['Item']['product_services_count']);	
			unset($contain['Item']['images_count']);
			unset($contain['Branch']);	
		} else {
			unset($contain['Branch']['fields']);
			$contain['Branch']['conditions'] = 'Branch.country_id='.$this->currentLocation['Country']['id'];
		}
		//$contain['Review'] = array('order' => 'Review.created DESC, Review.parent_id', 'limit' => 4, 'conditions' => 'Review.country_id='.$this->currentLocation['Country']['id'], 'User' => array('fields' => array('id', 'name', 'last_name')));
		
		if($this->currentConfig['isSubregionCountry']) {
			array_push($contain, 'Subregion');
		}
		
		$item = $this->Item->find('first', array('conditions' => array('Item.'.$idKey => $id), 'contain' => $contain, 'fields' => $fields));
		
		$itemCode = ItemsCore::encodeItem($item['Item']['id'], $item['Country']['url_name'], $item['Region']['code']);
		$this->set('itemCode', $itemCode);
		
		$logo='';
		if($item['Item']['logo']==1)
		{
			$logo = 'http://img0.amerpages.com/items/'.$item['Item']['country_id'].'/'.$item['Item']['country_id'].'_'.$item['Item']['id'].'.jpg';
		}

		$business = array('Business' => array ('BusinessID' => $item['Item']['id'], 'PlanID' => $item['Item']['plan_id'], 'BusinessName' => $this->xmlentities($item['Item']['name']), 'BusinessDescription' => $this->xmlentities($item['Item']['description']), 'BusinessLogoURL' => $logo, 'Website' => $this->xmlentities($item['Item']['website']), 'Email' => $item['Item']['email'], 'Country' => $item['Country']['name'], 'CountryURL' => $item['Country']['url_name'], 'Region' => $item['Region']['name'], 'RegionCode' => $item['Region']['code'], 'Subregion' => $item['Subregion']['name'], 'City' => $item['City']['name'], 'Zone' => $item['Item']['zone'], 'AddressLine' => $this->xmlentities($item['Item']['address']), 'ZipCode' => $item['Item']['zip_code'], 'Latitude' => $item['Item']['lat'], 'Longitude' => $item['Item']['lng'], 'MapStatus' => $item['Item']['map_status'], 'Phone' => $item['Item']['phone_1'], 'Cellphone' => $item['Item']['cell_phone_1'], 'Ratings' => $item['Item']['reviews_rating'], 'TotalBranches'=>0, 'Branch'=>array(),),'Rating'=>$item['Item']['reviews_rating']);
		$branchesCounter=0;
		foreach($item['Branch'] as $branch) {
			$branchesCounter++;
			if (in_array($item['Country']['url_name'], $this->amerpagesGlobal->getSubregionCountries()))
			{
				$business['Business']['Branch'][$branchesCounter] = array( 'CountryURL' => $item['Country']['url_name'], 'Region'=>$branch['Region']['name'], 'RegionCode' => $item['Region']['code'],'Subregion'=>$branch['Subregion']['name'],'City'=>$branch['City']['name'],'Zone'=>$branch['zone'],'AddressLine'=>$this->xmlentities($branch['address']),'ZipCode'=>$branch['zip_code'],'Latitude'=>$branch['lat'],'Longitude'=>$branch['lng'],'MapStatus'=>$branch['map_status'],'Phone'=>$branch['phone_1'],'Cellphone'=>$branch['cell_phone_1'],'Email'=>$branch['email']);
			}
			else
			{
				$business['Business']['Branch'][$branchesCounter] = array('CountryURL' => $item['Country']['url_name'], 'Region'=>$branch['Region']['name'], 'RegionCode' => $item['Region']['code'],'Subregion'=>'','City'=>$branch['City']['name'],'Zone'=>$branch['zone'],'AddressLine'=>$this->xmlentities($branch['address']),'ZipCode'=>$branch['zip_code'],'Latitude'=>$branch['lat'],'Longitude'=>$branch['lng'],'MapStatus'=>$branch['map_status'],'Phone'=>$branch['phone_1'],'Cellphone'=>$branch['cell_phone_1'],'Email'=>$branch['email'] );
			}
		}
		$business['Business']['TotalBranches'] = $branchesCounter;
		//TotalReviews, Review*, TotalDeals, Deal*, TotalCatalogs, Catalog*, TotalImages, Image*
		$totalReviews = array('TotalReviews'=>$item['Item']['reviews_count']);
		$reviews = array('Review'=>array());
		$i=0;
		foreach($item['Review'] as $review) {
			//User, Created, Rating, Text
			$name='';
			if($review['author_name'])
			{
				$name = $this->xmlentities($review['author_name']);
			}
			else
			{
				$name = $this->xmlentities($review['User']['name'].' '.$review['User']['last_name']);
			}
			$string = str_replace('\n', ' ', $review['text']);
			$coment = stripslashes(preg_replace('/\v+|\\\[rn]/', '<br/>', $string));
			
			$reviews['Review'][$i] = array('User'=>$name,'Created'=>$review['created'],'Rating'=>$review['rating'],'Text'=> ($this->xmlentities($coment))) ;
			$i++;

		}
		
		$totalDeals = array('TotalDeals'=>$item['Item']['promos_count']);
		$deals = array('Deal'=>array());
		$i=0;
		foreach($item['Promo'] as $promo) {
			//DealID, Title, FaceValue, Description, DealImageURL, YouSave, Currency, Validated, CurrentStock, StartDate, EndDate, Condition*, KeyWords

			if($promo['start_date']<=date('Y-m-d') && $promo['end_date']>date('Y-m-d')&&(($promo['current_stock']>0 && $promo['validated']==1)||($promo['validated']==0)))
			{
				if($promo['image']==1)
				{

					$imageurl = 'http://amerpages.com/img/items/'.$item['Item']['country_id'].'/'.$item['Item']['id'].'/promos/'.$promo['id'].'.jpg';
				}
				else
				{
					$imageurl = '';
				}
				$deals['Deal'][$i] = array('DealID'=>$promo['id'],'Title'=>$this->xmlentities($promo['title']),'FaceValue'=>$this->xmlentities($promo['facevalue']),'Description'=>$this->xmlentities($promo['description']),'DealImageURL'=>$imageurl,'YouSave'=>$promo['yousave'],'Currency'=>$promo['Currency']['symbol'],'Validated'=>$promo['validated'],'CurrentStock'=>$promo['current_stock'],'StartDate'=>$promo['start_date'],'EndDate'=>$promo['end_date'],'Condition'=>array(),'KeyWords'=>$this->xmlentities($promo['keywords']),);
			
			$j=0;
			foreach(json_decode($promo['conditions']) as $cond)
			{
				$deals['Deal'][$i]['Condition'][$j] = $this->xmlentities($cond);
				$j++;
			}
			$i++;
			}

		}
		$totalCatalogs = array('TotalCatalogs'=>$item['Item']['product_services_count']);
		$catalogs = array('Catalog'=>array());
		$i=0;
		foreach($item['ProductService'] as $catalog) {
			//CatalogImageURL, Name, Price, Currency, Description
			$imageurl = '';
			if($catalog['image']==1)
			{
				$imageurl = 'http://amerpages.com/img/items/'.$item['Item']['country_id'].'/'.$item['Item']['id'].'/pds/'.$catalog['id'].'_s.jpg';
			}
			$catalogs['Catalog'][$i] = array('CatalogImageURL'=>$imageurl,'Name'=>$this->xmlentities($catalog['name']),'Price'=>$catalog['price'],'Currency'=>$catalog['Currency']['symbol'],'Description'=>$this->xmlentities($catalog['description']) );
			$i++;

		}
		
		$totalImages = array('TotalImages'=>$item['Item']['images_count']);
		$images = array('Image'=>array());
		$i=0;
		foreach($item['Image'] as $image) {
			//ImageURL, Name, Description
			$imageurl = 'http://amerpages.com/img/items/'.$item['Item']['country_id'].'/'.$item['Item']['id'].'/img/'.$image['id'].'_s.jpg';
			$images['Image'][$i] = array('ImageURL'=>$imageurl,'Name'=>$this->xmlentities($image['name']),'Description'=>$this->xmlentities($image['description']) );
			$i++;

		}
	
		$amerpagesBusiness = Set::merge($business, $totalReviews, $reviews, $totalDeals, $deals, $totalCatalogs, $catalogs, $totalImages, $images);
		$this->set('appBusiness', array('AmerpagesBusiness' => $amerpagesBusiness));
	}

	function qappxml($itemCode = null, $alias = null) {
		if ($itemCode) {
			$decodedItem = ItemsCore::decodeItemCode($itemCode);
			$this->redirect(array('controller' => 'mobiles', 'action' => 'appxml', 'language' => $this->currentConfig['locale'], $decodedItem['item_id'], $alias, 'country' => $decodedItem['country_url_name'], 'regionCode' => $decodedItem['region_code']));
			$this->render('/elements/noreturn');
		}
	}
	
	function loginApp($username = null, $password = null){
        if($username && $password){
            $user = array();
			Security::setHash('md5');
			$user['User']['username'] = $username;
            $position = strpos($this->params['form']['username'], '@');
            if ($position !== false) {
                $user['User']['email'] = $username;
                $this->Auth->fields = array(
					'username' => 'email',
					'password' => 'password'
				);
            }
			$user['User']['password'] = $this->Auth->password($password);
            if($this->Auth->login($user) == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
	
    function login() {
		$this->layout = "json";
		$data = array('authentication' => false);
		if($this->RequestHandler->isPost()) {
			if($this->loginApp($this->params['form']['username'], $this->params['form']['password'] )) {
				$data['authentication'] = true;		
			}              
		}
		$this->set('data', $data);
		$this->render('appjson');
	}
	
	function fbLogin() {
		$this->layout = "json";
		$data = array('authentication' => false);
		
		//MT: the app should send four values extracted from the facebook user via POST: id, email, first_name, last_name
		if($this->RequestHandler->isPost() && !empty($_POST['id']) && !empty($_POST['email']) && !empty($_POST['first_name']) && !empty($_POST['last_name'])) {
			
			$facebookUser = array();
			
			//MT: sanitize facebook parameters sent by the app
			App::import('Sanitize');
			$facebookUser['id'] = Sanitize::paranoid($_POST['id']);
			$facebookUser['email'] = Sanitize::paranoid($_POST['email'], array('.', '@'));
			$facebookUser['first_name'] = Sanitize::paranoid($_POST['first_name']);
			$facebookUser['last_name'] = Sanitize::paranoid($_POST['last_name']);
			$this->loadModel('User');
			//MT: if facebook user has already allowed amerpages, check if he already has an account on amerpages, otherwise, create one
			$this->User->recursive = -1;
			$existingUser = $this->User->findByOauthUid($facebookUser['id']);
			if(empty($existingUser)){
				//MT: check if user already has an account on amerpages based on his email
				$existingUser = $this->User->findByEmail($facebookUser['email']);
				if(empty($existingUser)){
					//MT: the user is absolutely new to amerpages and will create an account based on his facebook info
					$newFacebookUser = array();
					$newFacebookUser['User']['oauth_provider'] = 'facebook';
					$newFacebookUser['User']['oauth_uid'] = $facebookUser['id'];
					$newFacebookUser['User']['email'] = $facebookUser['email'];
					$newFacebookUser['User']['name'] = $facebookUser['first_name'];
					$newFacebookUser['User']['last_name'] = $facebookUser['last_name'];
					//MT: a password will be created for the new user
					$newFacebookUser['User']['oauth_password'] = $this->User->generatePassword();
					$newFacebookUser['User']['password'] = $this->Auth->password($newFacebookUser['User']['oauth_password']);
					
					$this->User->create();
					if($this->User->save($newFacebookUser)) {
						$this->Auth->fields = array('username' => 'oauth_uid', 'password' => 'password');
						if ($this->Auth->login($newFacebookUser)) {
							$data['authentication'] = true;
							$data['email'] = $facebookUser['email'];
							$data['password'] = $newFacebookUser['User']['oauth_password'];
						} 
					}
				} else {
					//MT: associate the facebook account with the already existing user
					$this->User->id = $existingUser['User']['id'];
					$this->User->data = $existingUser;
					$this->User->set(array('oauth_provider' => 'facebook', 'oauth_uid' => $facebookUser['id']));
					if ($this->User->save()) {
						$this->Session->setFlash(__('c_use_facebookSuccess', true));						
						$this->Auth->fields = array('username' => 'oauth_uid', 'password' => 'password');
						$existingUser['User']['oauth_uid'] = $facebookUser['id'];
						if ($this->Auth->login($existingUser)) {
							$data['authentication'] = true;
						} 
					}
				}
			} else {
				//MT: user's account is already associated with his facebook account
				$this->Auth->fields = array('username' => 'oauth_uid', 'password' => 'password');
				if($this->Auth->login($existingUser)) {
					$data['authentication'] = true;
				}
			}      
		}
		$this->set('data', $data);
		$this->render('appjson');
	}
	
	function test213() {
		$this->layout = 'test';
	}
	
	
	function appLocations($countryID = null, $keyword = null){
		$this->layout="xml";
		$this->loadModel('Location');
		if(empty($countryId) && !empty($_GET['country'])) {
			$countryId = $_GET['country'];
		}
		
		$autocompleteLocations = null;
		$locale = 'spa';
		$fields = array('id', $locale);
		$countryCondition = '';
		$limit = 20;
		$fulltext = true;
		$locations = array();
		
		//$q = Sanitize::clean($_GET['term']);
		$q = Sanitize::clean($keyword);
		$q = html_entity_decode($q, ENT_NOQUOTES, "UTF-8");
		if(!empty($countryID) && !empty($locale) && !empty($q)) {
			//MT: check if Sphinx service is running
			$handle = fsockopen ('127.0.0.1', 9312, $errnum, $errmsg, 1);
			if (false) {
			//echo ('Sphinx no corre: '.$errnum . ' ' . $errmsg);
				if(is_numeric($countryId)) {
					$countryCondition = 'Location.country_id = '.$countryId;
				} else {
					$countryCondition = 'Location.url_name = \''.$countryId.'\'';
				}		
					
				if(strlen($q) == 2 && ($countryId == 110 || $countryId == 'usa')) {
					$locationCondition = 'region_code=\''.$q.'\'';
					$locations = $this->Location->find('list', array('conditions' => array('AND' => array($countryCondition, $locationCondition)), 'fields' => $fields, 'order' => ' location_type ASC', 'recursive' => -1, 'limit' => $limit));	
				}
						
				if(count($locations) == 0) {
					$locationCondition = "MATCH ".$locale." AGAINST ('".$q."')";
					$locations = $this->Location->find('list', array('conditions' => array('AND' => array($countryCondition, $locationCondition)), 'fields' => $fields, "order" => $locationCondition." DESC, location_type ASC", 'recursive' => -1, 'limit' => $limit));	
				}
						
				if(count($locations) == 0) {
					$locationCondition = $this->_getFulltextQuery($q, array($locale.'_city', $locale.'_subregion', $locale.'_region'));
					$locations = $this->Location->find('list', array('conditions' => array('AND' => array($countryCondition, $locationCondition)), 'fields' => $fields, "order" => $locationCondition.", location_type ASC", 'recursive' => -1, 'limit' => $limit));	
				}
						
				if(count($locations) == 0) {
					$locationCondition = $this->_getLikeQuery($q, array($locale.'_city', $locale.'_subregion', $locale.'_region'));
					$locations = $this->Location->find('list', array('conditions' => array('AND' => array($countryCondition, $locationCondition)), 'fields' => $fields, "order" => "location_type ASC", 'recursive' => -1, 'limit' => $limit));	
				}
			} 
			else 
			{
				fclose ($handle);
				if(is_numeric($countryID)) {
					$countryCondition = array('country_id', $countryID);
					} 
				else {						
					$countryIdChecksum = crc32($countryID);
					if($countryIdChecksum < 0) {
						$countryIdChecksum += 4294967296;
					}
					$countryCondition = array('url_name', $countryIdChecksum);
				}
				$sphinx = array(
					'matchMode' => SPH_MATCH_EXTENDED, 
					'sortMode' => array(SPH_SORT_EXTENDED => '@relevance DESC'), 
					'index' => array('locations'),
				);
				$sphinx['filter'][] = $countryCondition;
				
				//pr($sphinx['filter']);
				
				$explodedQ = explode(',', $q);
				
				if (count($explodedQ) == 1) {
					$searchQuery = '@('.$locale.'_city,'.$locale.'_region) '.$q.'* | "'.$q.'"~4';
				} else {
					$searchQuery = '@'.$locale.'_city '.$explodedQ[0].' @'.$locale.'_region ^'.$explodedQ[1];
				}
				//echo $searchQuery;
				
				$this->loadModel('Location');	
				$locations = $this->Location->find('all', array(
					'search' => $searchQuery, 
					'fields' => $fields, 
					'recursive' => -1, 
					'limit' => $limit, 
					'sphinx' => $sphinx
				));	
			}
			//$this->set('locale', $locale);
			//$this->set('q', $q);
			//$this->set('locations', $locations);
			//pr($locations);
			$AmerpagesList = array('AmerpagesList' => array());
			//$PromoList = array('PromoList' => array());
			//$pi=0;
			$i=0;
			foreach($locations as $location)
			{
				//pr($location);
				$city = array('City' => array('Name' => $location['Location']['spa'], 'ID' => $location['Location']['id']));
				$AmerpagesList['AmerpagesList'][$i] = $city;
				$i++;		
			}
			//pr($AmerpagesList);
			$this->set('amerpagesList',$AmerpagesList);
		} 
		else {
			$this->layout = 'hidden';
		}
		
	}	
	function appClaim() {
		//$this->params['form']['promo_id'] = $this->params['pass'][0];
		$this->layout = "json";
		if (!empty($this->params['form']['username']) && !empty($this->params['form']['password']) && !empty($this->params['form']['promo_id'])) {
                
				if($this->loginApp($this->params['form']['username'], $this->params['form']['password'])) {
                $this->loadModel('Promo');
                $this->Promo->Behaviors->attach('Containable');

                if (!empty($this->currentUser['User']['id'])) {
                    $promoContain = array(
                        'PromoClaim' => array(
                            'conditions' => 'PromoClaim.user_id = '.$this->currentUser['User']['id'],
                            ),
                    );
                } else {
                    $promoContain = null;
                }
                $this->Promo->recursive = -1;
                $conditions = array(
                    "Promo.start_date <= '".date('Y-m-d')."'", 
                    "Promo.end_date >= '".date('Y-m-d')."'", 
                    'Promo.id' => $this->params['form']['promo_id']
                );
                $promo = $this->Promo->find('first', array('conditions' => $conditions, 'contain' => $promoContain));
                if(!empty($promo)){
                    $decodedItem = ItemsCore::decodeItemCode($promo['Promo']['item_code']);
                    $this->Promo->Item->Behaviors->attach('Containable');
                    $this->Promo->Item->setSource($decodedItem['item_source']);
                    $itemContain = array(
                        'Country' => array(
                            'fields' => array('id', $this->currentConfig['locale'], 'url_name'),
                            'Currency'
                            ),				
                        'Region' => array(
                            'fields' => array('id', $this->currentConfig['locale'], 'code'),
                            ),
                        'City' => array(
                            'fields' => array('id', $this->currentConfig['locale']),
                            ),
                    );
                    $item = $this->Promo->Item->find('first', array('conditions' => 'Item.id = '.$promo['Promo']['item_id'], 'contain' => $itemContain));
                    if ($promo['Promo']['validated'] == 1 && $promo['Promo']['current_stock'] > 0) {
                        if (!empty($promo['PromoClaim'])) {
                            $data = array('PromoClaim' => array('Status' => false, 'Error' => 'La promo ya fué reclamada'));
                        } else {
                            $newCode = $promo['Promo']['code'].'-'.substr(String::uuid(), 9, 4);
                            $this->data['PromoClaim']['code'] = $newCode;
                            $this->data['PromoClaim']['user_id'] = $this->currentUser['User']['id'];
                            $this->data['PromoClaim']['promo_id'] = $promo['Promo']['id'];

                            $this->Promo->PromoClaim->create();
                        	
                            if ($this->Promo->PromoClaim->save($this->data)) {
                                $this->Promo->id=$promo['Promo']['id'];
                                $newStock = $promo['Promo']['current_stock'] - 1;

                                $decodedItem = ItemsCore::decodeItemCode($promo['Promo']['item_code']);

                                $this->Promo->saveField('current_stock', $newStock);
                                $this->Promo->Item->updatePromoCount($promo['Promo']['item_code']);


                                $this->Email->to = $this->currentUser['User']['email'];
                                $this->Email->subject = __('Amerpages promo', true).': '.$promo['Promo']['facevalue'].' '.$promo['Promo']['title'].' '.__('gen_on',true).' '.$item['Item']['name'];
                                $this->Email->from = __('gen_siteName', true).' <noreply@amerpages.com>';

                                //MT: files located at views/templates/email/html - text
                                $this->Email->layout = 'default_'.$this->currentConfig['locale'];
                                $this->Email->template = 'promo_claim_approved_'.$this->currentConfig['locale'];
                                $this->Email->sendAs = 'both';
                                //Set view variables as normal
                                $this->set('emailAddress', $this->currentUser['User']['email']);
                                $this->set('language', $this->currentConfig['locale']);
                                $this->set('promo', $promo);
                                $this->set('item', $item);
                                $this->set('newCode', $newCode);
                                
                                if($this->Email->send()) {
                                    $data = array('PromoClaim' => array('Status' => true, 'NewCode' => $newCode));
                                } else {
                                    $data  = array('PromoClaim' => array('Status' => false));
                                }
                                $this->Email->to = $item['Item']['email'];
                                $this->Email->subject = $this->currentUser['User']['name'].' '.$this->currentUser['User']['last_name'].' '.__('Has claimed your Amerpages promo', true);
                                $this->Email->from = __('gen_siteName', true).' <noreply@amerpages.com>';
                                $this->Email->bcc = array('contact@amerpages.com', 'maurot21@yahoo.com');
                                //MT: files located at views/templates/email/html - text
                                $this->Email->layout = 'default_'.$this->currentConfig['locale'];
                                $this->Email->template = 'promo_claimed_owner_'.$this->currentConfig['locale'];
                                $this->Email->sendAs = 'both';
                                //Set view variables as normal
                                $this->set('emailAddress', $this->currentUser['User']['email']);
                                $this->set('language', $this->currentConfig['locale']);
                                $this->set('promo', $promo);
                                $this->set('item', $item);
                                $this->set('newCode', $newCode);
                                    //
                                if($this->Email->send()) {
                                    //$this->Session->setFlash(__('c_prm_claim_client_sent', true).$item['Item']['name']);
                                    $messageSent = true;
                                } else {
                                    //VER QUE HACER!!!!!! ENVIAR MAIL MANUAL!
                                    //$this->Session->setFlash(__('c_prm_claim_client_notsent', true).$item['Item']['email']);
                                }
                            }
                        }
                    } else {
                        //ST: no validado					
                        $newCode = $promo['Promo']['code'];
                        $this->data['PromoClaim']['code'] = $newCode;
                        $this->data['PromoClaim']['user_id'] = $this->currentUser['User']['id'];
                        $this->data['PromoClaim']['promo_id'] = $promo['Promo']['id'];
                        //$this->Promo->PromoClaim->create();
                        //$this->Promo->PromoClaim->save($this->data);


                        $this->Email->to = $this->currentUser['User']['email'];
                        $this->Email->subject = __('Your Amerpages promo',true).': '.$promo['Promo']['facevalue'].__('gen_on',true).' '.$item['Item']['name'];
                        $this->Email->from = __('gen_siteName', true).' <noreply@amerpages.com>';
                        $this->Email->bcc = array('contact@amerpages.com', 'maurot21@yahoo.com');
                        //MT: files located at views/templates/email/html - text
                        $this->Email->layout = 'default_'.$this->currentConfig['locale'];
                        $this->Email->template = 'promo_claim_approved_'.$this->currentConfig['locale'];
                        $this->Email->sendAs = 'both';
                        //Set view variables as normal
                        $this->set('emailAddress', $this->currentUser['User']['email']);
                        $this->set('language', $this->currentConfig['locale']);
                        $this->set('promo', $promo);
                        $this->set('item', $item);
                        $this->set('newCode', $newCode);

                        //Do not pass any args to send()
                        //$this->Email->send()
                        if(true) {
                            $data = array('PromoClaim' => array('Status' => true, 'NewCodde' => $newCode));
                        } else {
                            $data  = array('PromoClaim' => array('Status' => false));
                        }					

                    }
                }//ELSE promo don't exists
                else{
                    $data  = array('PromoClaim' => array('Status' => false));
                }
            } else {
               $data = array('authentication' => false);
            }
            
						
		} else {
			$data = array('authentication' => false);
		}
		$this->set('data', $data);
		$this->render('appjson');
	}
	
	function appReview(){
		if($this->RequestHandler->isPost()){
			$this->layout = "json";
            if (!empty($this->params['form']['username']) && !empty($this->params['form']['password']) ) {
                
                if($this->loginApp($this->params['form']['username'], $this->params['form']['password'])) {
                    $this->loadModel('Review');
                    App::import('Sanitize');
                    $this->data['Review']['rating'] = $this->params['form']['rating'];
                    $this->data['Review']['text'] = Sanitize::clean($this->params['form']['review'], array('encode' => false));
                    $this->data['Review']['author_name'] = $this->params['form']['author_name'];
                    $this->data['Review']['user_id'] = $this->currentUser['User']['id'];
                    $this->data['Review']['created'] = date('Y-m-d H:i:s');

                    $itemCode = ItemsCore::encodeItem($this->params['form']['business_id'], $this->params['form']['country_url'], $this->params['form']['region_code']);

                    $decodedItem = ItemsCore::decodeItemCode($itemCode);

                    $this->Review->Item->Behaviors->attach('Containable');
                    $this->Review->Item->setSource($decodedItem['item_source']);	
                    $itemContain = array(
                        'Country' => array(
                            'fields' => array('id'),
                            ),				
                        'Region' => array(
                            'fields' => array('id'),
                            ),
                        'Subregion' => array(
                            'fields' => array('id'),
                            ),
                        'City' => array(
                            'fields' => array('id'),
                            ),
                    );
                    $item = $this->Review->Item->find('first', array('conditions' => 'Item.id = '.$decodedItem['item_id'], 'contain' => $itemContain));

                    $this->data['Review']['country_id'] = $item['Country']['id'];
                    $this->data['Review']['region_id'] = $item['Region']['id'];
                    $this->data['Review']['subregion_id'] = $item['Subregion']['id'];
                    $this->data['Review']['city_id'] = $item['City']['id'];
                    $this->data['Review']['item_id'] = $this->params['form']['business_id'];
                    $this->data['Review']['item_code'] = $itemCode;

                    if (!empty($this->currentUser) && !empty($this->data)) {

                        $this->Review->create();
                        if ($this->Review->save($this->data)) {

                            $newReview = array();
                            $newReview['Review'] = $this->data['Review'];
                            $newReview['Review']['id'] = $this->Review->getLastInsertID();
                            $newReview['User']['id'] = $this->currentUser['User']['id'];
                            $newReview['User']['name'] = $this->currentUser['User']['name'];
                            $newReview['User']['last_name'] = $this->currentUser['User']['last_name'];
                            $itemCode = $newReview['Review']['item_code'];
                            $this->Review->Item->updateReviewInfo($itemCode);

                            $decodedItem = ItemsCore::decodeItemCode($itemCode);

                            $item = $this->Review->Item->basic($itemCode);
                            if (!empty($item['Item']['email'])) {
                                $this->Email->to = $item['Item']['email'];
                                $this->Email->subject = __('c_rev_newReview', true).': '.$item['Item']['name'].' '.__('gen_seoOnSite', true);
                                $this->Email->from = __('gen_siteName', true).' <noreply@amerpages.com>';
                                $this->Email->bcc = array('contact@amerpages.com');

                                if ($this->currentConfig['locale'] != 'deu' || $this->currentConfig['locale'] != 'chi') {
                                    $selectedLocale = $this->currentConfig['locale'];
                                } else {
                                    $selectedLocale = 'spa';
                                }

                                //MT: files located at views/templates/email/html - text
                                $this->Email->layout = 'default_'.$selectedLocale;
                                $this->Email->template = 'review_add_'.$selectedLocale;
                                $this->Email->sendAs = 'html';

                                //Set view variables as normal
                                $this->set('emailAddress', $item['Item']['email']);
                                $this->set('language', $selectedLocale);
                                $this->set('item', $item);
                                $this->set('review', $newReview);
                                $this->set('itemLinkUrl', array('controller' => 'items', 'action' => 'view', 'country' => $decodedItem['country_url_name'], 'regionCode' => $decodedItem['region_code'], $item['Item']['id'], (!empty($item['Item']['alias']) ? $item['Item']['alias'] : null), 'language' => $selectedLocale));

                                //Do not pass any args to send()
                                $this->Email->send();
                            }

                            if (empty($this->data['Review']['author_name']) && !empty($this->data['Review']['share_facebook']) && $this->data['Review']['share_facebook'] == 1) {
                                App::import('Vendor', 'facebook');
                                $facebook = new Facebook(array('appId' => Configure::read('Configuracion.fbAppId'), 'secret' => Configure::read('Configuracion.fbSecret'), 'cookie' => true));
                                //MT: retrieve facebook user
                                $facebookUserId = $facebook->getUser();
                                //MT: check if facebook user is logged in and has allowed amerpages
                                if(!empty($facebookUserId)) {
                                    try {
                                        // Proceed knowing you have a logged in user who's authenticated.
                                        //$facebookUser = $facebook->api('/me');

                                        $decodedItem = ItemsCore::decodeItemCode($itemCode);
                                        if (!empty($decodedItem['region_code'])) {
                                            $regionCodeUrl = $decodedItem['region_code'].'/';
                                        } else {
                                            $regionCodeUrl = '';
                                        }
                                        if ($newReview['Review']['rating'] >= 3) {
                                            $fbRating = '';
                                            for($i = 1; $i <= $newReview['Review']['rating']; $i++) {
                                                $fbRating .= '*';
                                            }
                                            $fbRating .= ' - '.$newReview['Review']['rating'].' '.__('estrellas', true);									
                                        } else {
                                            $fbRating = null;
                                        }
                                        $item = $this->Review->Item->basic($itemCode);
                                        $attachment = array(
                                            'message' => $item['Item']['name'].': '.stripslashes(preg_replace('/\v+|\\\[rn]/', ' ', $newReview['Review']['text'])),
                                            'name' => __('Dinos quÃ© piensas de', true).' '.$item['Item']['name'],
                                            'link' => Router::url(array('controller' => 'items', 'action' => 'view', 'language' => $this->currentConfig['locale'], 'country' => $decodedItem['country_url_name'], 'regionCode' => $decodedItem['region_code'], $item['Item']['id'], '#' => 'reviews'), true),
                                            'caption' => $fbRating,
                                            'type' => 'link',
                                        );

                                        if ($item['Item']['logo'] == 1) {

                                            $logoName = $item['Item']['country_id'].(!empty($decodedItem['region_code']) ? '_'.strtoupper($decodedItem['region_code']) : '').'_'.$item['Item']['id'];

                                            $attachment['picture'] = Router::url('/img/items/'.$item['Item']['country_id'].'/'.$regionCodeUrl.$item['Item']['country_id'].(!empty($decodedItem['region_code']) ? '_'.strtoupper($decodedItem['region_code']) : '').'_'.$item['Item']['id'].Configure::read('Configuracion.mediumLogoSufix').Configure::read('Configuracion.imageExtension'), true);
                                        } else {
                                            $attachment['picture'] = Router::url('/img/amerpages_default_s.jpg', true);
                                        }
        //								if ($this->currentUser['UserType']['code'] == 'x') {
        //									pr($attachment);
        //								} 
                                        $facebook->api('/me/feed/', 'post', $attachment);
                                    } catch (FacebookApiException $e) {
                                        error_log($e);
                                    }
                                }
                            }
                            $data  = array('Review' => array('Status' => true));
                        } 				
                    }
                    else {
                        $data  = array('Review' => array('Status' => false));			
                    }
                    $data = $this->data['Review'];
                } else {
                   $data = array('authentication' => false);
                }
            } else {
                $data = array('authentication' => false);
            }
            $this->set('data', $data);
            $this->render('appjson');
		}
		else {            
			$this->redirect('/');
		}		
	}
	function xmlentities($string) {
		return str_replace(array("<", ">", "\"", "'", "&"),
			array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), $string);
	}
}
?>