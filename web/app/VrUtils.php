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
}