<?php
namespace App\Libraries;
class Utils
{
	 /* Method : Get Api response using curl */
     public static function curl($url, $post_string){
		
		$headers = array( 
			"Content-Type: application/xml ;charset=\"utf-8\""
		); 
	
		$soap_do = curl_init(); 
		curl_setopt($soap_do, CURLOPT_URL,            $url );   
		curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $headers); 
		curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec($soap_do);
		
		$xml = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $result);  
		$xml = simplexml_load_string($xml);
		
		return  $xml;
		
	}
	
	/* Method : Get Country Service Response */
	 public static function countryservice(){
		$service = urlencode('<message>\n<serviceRequest serviceCode="getCountries">\n<serviceParameters>\n<brand code ="EP"/>\n</serviceParameters>\n</serviceRequest>\n</message>');
		return self::getUrl($service);
	 }
	 
	 /* Method : Get City Service Response */
	 public static function cityservice($param){
		$service = urlencode('<message>\n<serviceRequest serviceCode="getCities">\n<serviceParameters>\n<country countryCode="'.$param.'"/>\n</serviceParameters>\n</serviceRequest>\n</message>');
		return self::getUrl($service);
	 }
	 
	 /* Method : Get Stations Service Response */
	 public static function stationservice($params){
		$service = urlencode('<message>\n<serviceRequest serviceCode="getStations">\n<serviceParameters>\n<station countryCode="'.$params['countryCode'].'" cityName="'.$params['cityName'].'" language="FR"/>\n</serviceParameters>\n</serviceRequest>\n</message>');;
 		return self::getUrl($service);
	 }
	 
	 /* Method : Get Car Categories Service Response */
	 public static function carcatservice($params){
		$service = urlencode('<message>\n<serviceRequest serviceCode="getCarCategories">\n<serviceContext>\n<localisation active="true">\n<language code="fr_FR"/>\n</localisation>\n</serviceContext>\n<serviceParameters>\n<reservation>\n<checkout stationID="'.$params['stationID'].'" date="'.$params['date'].'"/>\n</reservation>\n</serviceParameters>\n</serviceRequest>\n</message>');
		return self::getUrl($service);
	}
	 
	 /* Method : Get Quote Service Response */
	 public static function quoteservice($params){
		$service = urlencode('<message>\n<serviceRequest serviceCode="getQuote">\n<serviceContext>\n<localisation active="true">\n<language code="fr_FR"/>\n</localisation>\n</serviceContext>\n<caller />\n<serviceParameters>\n<reservation carCategory="'.$params['carCategory'].'"> <checkout stationID="'.$params['checkout_stationID'].'" date="'.$params['check_in_date'].'" time="'.$params['check_in_time'].'" />\n<checkin stationID="'.$params['checkin_stationID'].'" date="'.$params['check_out_date'].'" time="'.$params['check_out_time'].'" /> </reservation>\n<driver countryOfResidence="'.$params['countryCode'].'" />\n</serviceParameters>\n</serviceRequest>\n</message>');
		return self::getUrl($service);
	}
	 
	 /* Method : Get Api Url */
	 public static function getUrl($service){
		 $url = 'https://applications-ptn.europcar.com/xrs/resxml?XML-Request='.$service.'&callerCode=22467&password=12012015';
		 return $url;
	 }
}

?>