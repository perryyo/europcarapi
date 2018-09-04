<?php

	use App\Libraries\Utils;

class ApiController extends ControllerBase
{

	/**
	  * Getting error response for missing parameters.
	  *
	  * @param string $str represents name of the attribute.
	  * @param null or string  $key represents null if all parameters missing or an attribute as key.
	  *
	  * @return error response with code.
	*/

	public function error($str, $key = null)
	{
		$attr = 'attributes';
		$element = new stdClass;	
		$element->serviceResponse =  new stdClass;
		$key = ($key!='') ? '.'.$key : '';
		$flag = 'xrs.missing.'.$str.$key;
		$element->serviceResponse->$attr = array('errorCode'=>$flag,'returnCode' => 'KO');
		return $element;
	}

	/**
	  * Conversion from xml to array.
	  *
	  * @param object $xmlObject represents xml object.
	  * @param array  $out represents converted array from object.
	  *
	  * @return array.
	*/

	public function xml2array($xmlObject, $out = array())
	{
		foreach ((array)$xmlObject as $index => $node){
			$out[$index] = (is_object($node)) ? $this->xml2array($node) : $node;
		}
		return $out;
	}

	/**
	  * Replace array keys.
	  *
	  * @param array $xmlObject represents array whose keys need to be replaced.
	  * @param string  $newKey represents key which is to be replaced with.
	  * @param string  $oldKey represents key which need to be replaced.
	  *
	  * @return array.
	*/

	public function replaceKey($subject, $newKey, $oldKey) 
	{
		if (!is_array($subject)) return $subject;

		$newArray = array(); // empty array to hold copy of subject
		foreach ($subject as $key => $value) {
			// replace the key with the new key only if it is the old key
			$key = ($key === $oldKey) ? $newKey : $key;

			if (is_array($subject[$key])){
				if (!empty($subject[$key])){
					if (count($subject[$key]) > 1){
						foreach ($subject[$key] as $k => $v){
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
	
	
	/**
	  * Check Quote Parameters.
	  *
	  * @param array $params represents post parameters of getQuoteAction.
	  *
	  * @return null or object.
	*/
	
	public function checkQuote($params)
	{
		$flag = '';
			
		$checkParamArr['carCategory'] =  isset($params['carCategory']) ? $params['carCategory'] : '';
		$checkParamArr['checkin.stationID'] = 	isset($params['checkin_stationID']) ? $params['checkin_stationID'] : '';
		$checkParamArr['checkout.stationID'] = 	isset($params['checkout_stationID']) ? $params['checkout_stationID'] : '';
		$checkParamArr['checkin.time'] =  isset($params['checkin_time']) ? $params['checkin_time'] : '';
		$checkParamArr['checkout.time'] =  isset($params['checkout_time']) ? $params['checkout_time'] : '';
		$checkParamArr['countryCode'] =  isset($params['countryCode']) ? $params['countryCode'] : '';

		if (isset($checkParamArr) && !empty($checkParamArr)){
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

		return $flag;
    }
	
	/**
	  * Convert datetime to required format.
	  *
	  * @param string $time represents datetime in "d/m/Y H:i" format.
	  * @param string $format represents in which format $time needs to be converted.
	  *
	  * @return null or string.
	*/
	
	public function datetimeFormat($time, $format)
	{
		if(isset($time) && $time!='')
		{
			$newDate = DateTime::createFromFormat("d/m/Y H:i", $time);
			$result = $newDate->format($format); 
		} else {
			$result = '';	
		}
		
		return $result;
    }

    /**
	  * Conversion from xml to json.
	  *
	  * @param object $xml represents xml object.
	  *
	  * @return json.
	*/

	public function responseJson($xml)
	{
		$xmlArr = $this->xml2array($xml);
		$arr = $this->replaceKey($xmlArr, 'attributes', '@attributes');

		header('Content-Type: application/json');
		return json_encode($arr);
	}

	
	/**
	  * Get list of countries with country code and country description.
	  *
	  *
	  * @return json.
	*/

	public function getCountriesAction()
	{
		$url = Utils::countryservice();
		$data = Utils::curl($url);
		echo $this->responseJson($data);
	}

	/**
	  * Get list of cities with city code and city description.
	  *
	  * @param string countryCode, param need to be passed, as for example { countryCode : AE }.
	  *
	  * @return json.
	*/

	public function getCitiesAction()
	{
		$params = $this->request->getPost();

		if (!isset($params) || empty($params)){
	        $xml = $this->error('parameters');
	        echo $this->responseJson($xml);
	        return false;
		}

		if(!isset($params['countryCode']) || $params['countryCode'] == ''){
			$xml = $this->error('country', 'key');
			echo $this->responseJson($xml);
	        return false;
		}
		
		
		$url = Utils::cityservice($params['countryCode']);
		$xml = Utils::curl($url);
		echo $this->responseJson($xml);
	}

	/**
	  * Get list of stations with station code and station name.
	  *
	  * @param string countryCode, param need to be passed, as for example { countryCode : AE }.
	  * @param string cityName, param need to be passed, as for example { cityName : ABU DHABI }.
	  *
	  * @return json.
	*/

	public function getStationsAction()
	{
		$params = $this->request->getPost();

		if (!isset($params) || empty($params)){
	        $xml = $this->error('parameters');
	        echo $this->responseJson($xml);
	        return false;
		}


			if((isset($params['countryCode']) && $params['countryCode'] != '') && (isset($params['cityName']) && $params['cityName'] != '')){
				$url = Utils::stationservice($params);
				$xml = Utils::curl($url);
			} else {
				if (!isset($params['countryCode']) || $params['countryCode'] == ''){
					$xml = $this->error('country','key');
				}
				if (!isset($params['cityName']) || $params['cityName'] == ''){
					$xml = $this->error('city','key');
				}
			}

		echo $this->responseJson($xml);
	}

	/* Method : Get Car categories list wrt station code and date */
	/**
	  * Get list of car categories with related information.
	  *
	  * @param string stationID, param need to be passed, as for example { stationID : MSQR02 }.
	  * @param date format (Ymd) date, param need to be passed, as for example { date : 20180829 }.
	  *
	  * @return json.
	*/

	public function getCarCategoriesAction()
	{
		$params = $this->request->getPost();
		if (isset($params) && !empty($params)){
			if ((isset($params['stationID']) && $params['stationID']!='') && (isset($params['date']) && $params['date']!='')){
				//Date in Ymd format
				$url = Utils::carcatservice($params);
				$xml = Utils::curl($url);
			} else {
				if (!isset($params['stationID']) || $params['stationID'] == ''){
					$xml = $this->error('stationcode','key');
				}
				if (!isset($params['date']) || $params['date'] == ''){
					$xml = $this->error('date','key');
				}
			}
		} else {
			$xml = $this->error('parameters');		
		}

		echo $this->responseJson($xml);
	}

	/**
	  * Get list of car quotations with related information.
	  *
	  * @param string carCategory, param need to be passed, as for example { carCategory : IDAR }.
	  * @param string checkout_stationID, param need to be passed, as for example { checkout_stationID : MSQR02 }.
	  * @param string checkin_stationID, param need to be passed, as for example { checkin_stationID : MSQR02 }.
	  * @param datetime format (d/m/Y H:i) checkout_time, param need to be passed, as for example { checkout_time : 04/09/2018 10:00 }.
	  * @param datetime format (d/m/Y H:i) checkin_time, param need to be passed, as for example { checkin_time : 03/09/2018 23:00 }.
	  * @param string countryCode, param need to be passed, as for example { countryCode : AE }.
	  *
	  * @return json.
	*/

	public function getQuoteAction()
	{
		$checkRes = ''; 
		$params = $this->request->getPost();

		if (!isset($params) || empty($params)){
	        $xml = $this->error('parameters');

	        echo $this->responseJson($xml);
	        return false;
		}

		
		$checkRes = $this->checkQuote($params);

		if (!empty($checkRes)){

	        $attr = 'attributes';
			$element = new stdClass;
			$element->serviceResponse =  new stdClass;
			$element->serviceResponse->$attr = array('errorCode' => $checkRes,'returnCode' => 'KO');
			$xml = $element;

			echo $this->responseJson($xml);
	        return false;
		}

		$checkin_time 		=   $params['checkin_time'];
		$checkout_time 		=   $params['checkout_time'];
		$check_in_date 		=   $this->datetimeFormat($checkin_time, 'Ymd'); 
		$check_in_time 		=   $this->datetimeFormat($checkin_time, 'Hi');
		$check_out_date 	=   $this->datetimeFormat($checkout_time, 'Ymd');
		$check_out_time 	=   $this->datetimeFormat($checkout_time, 'Hi');

		$paramArr['carCategory'] = $params['carCategory'];
		$paramArr['checkout_stationID'] = $params['checkout_stationID'];
		$paramArr['check_in_date'] = $check_in_date;
		$paramArr['check_in_time'] = $check_in_time;
		$paramArr['checkin_stationID'] = $params['checkin_stationID'];
		$paramArr['check_out_date'] = $check_out_date;
		$paramArr['check_out_time'] = $check_out_time;
		$paramArr['countryCode'] = $params['countryCode'];

		$url = Utils::quoteservice($paramArr);
		$xml = Utils::curl($url);

		echo $this->responseJson($xml);
	}

}
