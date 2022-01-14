<?php
	function calculateDistance($alat,$alng,$blat,$blng){
        $lat = ''; //lat 
        $lng = ''; //lng
        $radLat1 = deg2rad($alat); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($blat);
        $radLng1 = deg2rad($alng);
        $radLng2 = deg2rad($blng);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        $distance = ceil($s);
        $distance = $distance / 1000;
        $distance = round($distance, 2);
       return $distance;
	}

	function quick_sort($arr)
	{
		//判断参数是否是一个数组
		if(!is_array($arr)) return false;
		//递归出口:数组长度为1，直接返回数组
		$length = count($arr);
		if($length<=1) return $arr;
		//数组元素有多个,则定义两个空数组
		$left = $right = array();
		//使用for循环进行遍历，把第一个元素当做比较的对象
		for($i=1; $i<$length; $i++)
		{
		//判断当前元素的大小
			if($arr[$i]["distance"]<$arr[0]["distance"]){
				$left[]=$arr[$i];
			}else{
				$right[]=$arr[$i];
			}
		}
		//递归调用
		$left=quick_sort($left);
		$right=quick_sort($right);
		//将所有的结果合并
		return array_merge($left,array($arr[0]),$right);
	}
?>