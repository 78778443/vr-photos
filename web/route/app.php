<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

Route::get('photo/upload', 'Photo/upload');
Route::post('photo/upload', 'Photo/batchUpload');
Route::post('photo/batchUpload', 'Photo/batchUpload');
Route::get('photo/my', 'Photo/my');
Route::post('photo/delete/:id', 'Photo/delete');
Route::get('photo/share/:id', 'Photo/share');

Route::get('user/login', 'User/login');
Route::post('user/login', 'User/doLogin');
Route::get('user/register', 'User/register');
Route::post('user/register', 'User/doRegister');
Route::get('user/logout', 'User/logout');

Route::get('VrView/index/id/:id', 'VrView/index');
Route::get('VrView/edit/id/:id', 'VrView/edit');
Route::post('VrView/addHotspot', 'VrView/addHotspot');
Route::post('VrView/deleteHotspot', 'VrView/deleteHotspot');
Route::get('VrView/getUserPhotos', 'VrView/getUserPhotos');

Route::get('vr/:id', 'Vr/index');