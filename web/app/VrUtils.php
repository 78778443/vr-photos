<?php

namespace app;

class VrUtils
{
    // 将经纬度转换为3D向量坐标（用于A-Frame定位）
    public static function convertLatLonToVector3($lat, $lon, $radius = 10) {
        $latRad = deg2rad($lat);
        $lonRad = deg2rad($lon);
        
        $x = $radius * cos($latRad) * cos($lonRad);
        $y = $radius * sin($latRad);
        $z = $radius * cos($latRad) * sin($lonRad);
        
        // 调整坐标系以适应A-Frame
        return $x . ' ' . $y . ' ' . (-$z);
    }
    
    // 将3D向量坐标转换为经纬度
    public static function convertVector3ToLatLon($x, $y, $z, $radius = 10) {
        // 调整坐标系
        $z = -$z;
        
        // 归一化坐标
        $length = sqrt($x*$x + $y*$y + $z*$z);
        if ($length > 0) {
            $x /= $length;
            $y /= $length;
            $z /= $length;
        }
        
        $lat = asin($y) * 180 / pi();
        $lon = atan2($z, $x) * 180 / pi();
        
        return ['lat' => $lat, 'lon' => $lon];
    }
}