<?php


class GeoMath {
	
	public static function LonLatDistance($lon1,$lat1,$lon2,$lat2,$unit="km"){
		$R = 6371; // km
		$dLat = deg2rad($lat2-$lat1);
		$dLon = deg2rad($lon2-$lon1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);
		
		$a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$d = $R * $c;
		if($unit == "mile"){
			$d *= 0.621371192;
		}
		return $d;
	}
	
}

?>