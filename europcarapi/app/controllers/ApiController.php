<?php
use App\Libraries\Utils;
class ApiController extends ControllerBase
{
    public function indexAction()
    {

    }
	
	/* Method : Getting Error Response */
	public static function geterror($key='',$str){
		$attr = 'attributes';
		$element = new stdClass;	
		$element->serviceResponse =  new stdClass;
		$key = ($key!='') ? '.'.$key : '';
		$flag = 'xrs.missing.'.$str.$key;
		$element->serviceResponse->$attr = array('errorCode'=>$flag,'returnCode' => 'KO');
		return $element;
	 }
	 
	 /* Method : Conversion from xml to array */
	 public function xml2array ( $xmlObject, $out = array () )
	 {
		foreach ( (array) $xmlObject as $index => $node )
			$out[$index] = ( is_object ( $node ) ) ? $this->xml2array ( $node ) : $node;
	
		return $out;
	 }
	 
	 /* Method : Replace Array Keys */
	 public function replaceKey($subject, $newKey, $oldKey) {
		 
		if (!is_array($subject)) return $subject;
	
		$newArray = array(); // empty array to hold copy of subject
		foreach ($subject as $key => $value) {
			// replace the key with the new key only if it is the old key
			$key = ($key === $oldKey) ? $newKey : $key;
			if(is_array($subject[$key])){
				if(!empty($subject[$key])){
					if(count($subject[$key]) > 1){
						foreach($subject[$key] as $k => $v){
							$k = ($k === $oldKey) ? $newKey : $k;
							$v = (is_array($v)) ? $v : $this->xml2array($v);
							$newArray[$key][$k] = $this->replaceKey($v, $newKey, $oldKey);
						}
					}else{
						$value = (is_array($value)) ? $value : $this->xml2array($value);
						$newArray[$key] = $this->replaceKey($value, $newKey, $oldKey); 
					}
				}
				
			}else{
				$newArray[$key] = $this->replaceKey($value, $newKey, $oldKey);
			}
			// add the value with the recursive call
		}
		return $newArray;
	}
	 
	/* Method : Get Countries list with country code */
    public function getCountriesAction()
    {
        $input_xml = ' ';   
		$url = Utils::countryservice();
		$data = Utils::curl($url, $input_xml);
		$xmlArr = $this->xml2array($data);
		$arr = $this->replaceKey($xmlArr,'attributes','@attributes');
		
		header('Content-Type: application/json');
		echo json_encode($arr);
    }
	
	/* Method : Get Cities list wrt country code */
	public function getCitiesAction()
    {
		/* Parameters : { countryCode : AE } */
		$input_xml = ' ';   
		$params = $this->request->getPost();
		if(isset($params) && !empty($params)){	
			if(isset($params['countryCode']) && $params['countryCode']!=''){
				$url = Utils::cityservice($params['countryCode']);
				$xml = Utils::curl($url, $input_xml);
			}else{
				$xml = $this->geterror('key','country');
			}
		}else{
			$xml = $this->geterror('','parameters');
		}
		$xmlArr = $this->xml2array($xml);
		$arr = $this->replaceKey($xmlArr,'attributes','@attributes');

		header('Content-Type: application/json');
		echo json_encode($arr);
    }
	
	/* Method : Get Stations list wrt country code & city name */
	public function getStationsAction()
    {
		/* Parameters : { countryCode : AE , cityName : ABU DHABI } */
		$input_xml = ' ';   
		$params = $this->request->getPost();
		if(isset($params) && !empty($params)){	
			if((isset($params['countryCode']) && $params['countryCode']!='') && (isset($params['cityName']) && $params['cityName']!='')){
				$url = Utils::stationservice($params);
				$xml = Utils::curl($url, $input_xml);
			}else{
				if(!isset($params['countryCode']) || $params['countryCode'] == ''){
					$xml = $this->geterror('key','country');
				}
				if(!isset($params['cityName']) || $params['cityName'] == ''){
					$xml = $this->geterror('key','city');
				}
			}
		}else{
			$xml = $this->geterror('','parameters');
		}
		$xmlArr = $this->xml2array($xml);
		$arr = $this->replaceKey($xmlArr,'attributes','@attributes');

		header('Content-Type: application/json');
		echo json_encode($arr);
    }
	
	/* Method : Get Car categories list wrt station code and date */
	public function getCarCategoriesAction()
    {
		/* Parameters : { stationID : MSQR02 , date : 20180829 } */
		$input_xml = ' ';   
		$params = $this->request->getPost();
		if(isset($params) && !empty($params)){
			if((isset($params['stationID']) && $params['stationID']!='') && (isset($params['date']) && $params['date']!='')){
				//Date in Ymd format
				$url = Utils::carcatservice($params);
				$xml = Utils::curl($url, $input_xml);
			}else{
				if(!isset($params['stationID']) || $params['stationID'] == ''){
					$xml = $this->geterror('key','stationcode');
				}
				if(!isset($params['date']) || $params['date'] == ''){
					$xml = $this->geterror('key','date');
				}
			}
		}else{
			$xml = $this->geterror('','parameters');		
		}
		
		$xmlArr = $this->xml2array($xml);
		$arr = $this->replaceKey($xmlArr,'attributes','@attributes');

		header('Content-Type: application/json');
		echo json_encode($arr);
    }
	
	/* Method : Get quotation list  */
	public function getQuoteAction()
    {
		/* Parameters : { carCategory : IDAR, checkout_stationID: MSQR02, checkin_stationID : MSQR02, checkout_time : 30/08/2018 10:00, checkin_time : 29/08/2018 23:00, countryCode : AE } */
		$input_xml = ' ';  
		$flag = ''; 
		$params = $this->request->getPost();
		if(isset($params) && !empty($params)){
			$carCategory =  isset($params['carCategory']) ? $params['carCategory'] : '';
			$checkin_stationID = 	isset($params['checkin_stationID']) ? $params['checkin_stationID'] : '';
			$checkout_stationID = 	isset($params['checkout_stationID']) ? $params['checkout_stationID'] : '';
			$checkin_time =  isset($params['checkin_time']) ? $params['checkin_time'] : '';
			$checkout_time =  isset($params['checkout_time']) ? $params['checkout_time'] : '';
			$countryCode =  isset($params['countryCode']) ? $params['countryCode'] : '';
			
			$checkParamArr = array(
				'carCategory' => $carCategory,
				'checkin.stationID' => $checkin_stationID,
				'checkout.stationID' => $checkout_stationID,
				'checkin.time' => $checkin_time,
				'checkout.time' => $checkout_time,
				'countryCode' => $countryCode
			);
			
			if(isset($checkParamArr) && !empty($checkParamArr)){
				foreach ($checkParamArr as $key => $value) {
					$value = trim($value);
					if ($value == ''){
						if($flag == ''){
							$flag .= 'xrs.missing.'.$key.'.key';
						}else{
							$flag .= ' , xrs.missing.'.$key.'.key';
						}
					}
				}
			}
			
			if($flag!=''){
				$attr = '@attributes';
				$element = new stdClass;
				$element->serviceResponse =  new stdClass;
				$element->serviceResponse->$attr = array('errorCode'=>$flag,'returnCode' => 'KO');
				$xml = $element;
			}else{
				if($checkin_time!=''){
					$newDate = DateTime::createFromFormat("d/m/Y H:i",$checkin_time);
					$check_in_date = $newDate->format('Ymd'); 
					$check_in_time = $newDate->format('Hi'); 
				}else{
					$check_in_date = $check_in_time =  '';
				}
				
				if($checkout_time!=''){
					$newDate = DateTime::createFromFormat("d/m/Y H:i",$checkout_time);
					$check_out_date = $newDate->format('Ymd'); 
					$check_out_time = $newDate->format('Hi'); 
				}else{
					$check_out_date = $check_out_time =  '';
				}
				
				$paramArr['carCategory'] = $carCategory;
				$paramArr['checkout_stationID'] = $checkout_stationID;
				$paramArr['check_in_date'] = $check_in_date;
				$paramArr['check_in_time'] = $check_in_time;
				$paramArr['checkin_stationID'] = $checkin_stationID;
				
				$paramArr['check_out_date'] = $check_out_date;
				$paramArr['check_out_time'] = $check_out_time;
				$paramArr['countryCode'] = $countryCode;
				
				$url = Utils::quoteservice($paramArr);
				$xml = Utils::curl($url, $input_xml);
			}
		}else{
			$xml = $this->geterror('','parameters');
		}
		
		$xmlArr = $this->xml2array($xml);
		$arr = $this->replaceKey($xmlArr,'attributes','@attributes');

		header('Content-Type: application/json');
		echo json_encode($arr);
    }
	
}