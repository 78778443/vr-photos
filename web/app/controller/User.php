<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Config;

class User extends BaseController
{
    // 用户注册页面
    public function register()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $email = $this->request->post('email');
            $password = $this->request->post('password');
            $confirmPassword = $this->request->post('confirm_password');
            
            // 简单验证
            if (empty($username) || empty($email) || empty($password)) {
                return json(['code' => 400, 'msg' => '请填写完整信息']);
            }
            
            if ($password !== $confirmPassword) {
                return json(['code' => 400, 'msg' => '两次密码不一致']);
            }
            
            // 检查用户名或邮箱是否已存在
            $existUser = Db::name('users')->where('username', $username)->whereOr('email', $email)->find();
            if ($existUser) {
                return json(['code' => 400, 'msg' => '用户名或邮箱已存在']);
            }
            
            // 创建用户
            $data = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = Db::name('users')->insertGetId($data);
            if ($userId) {
                return json(['code' => 200, 'msg' => '注册成功']);
            } else {
                return json(['code' => 500, 'msg' => '注册失败']);
            }
        }
        
        return View::fetch('user/register');
    }
    
    // 用户登录页面
    public function login()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            
            if (empty($username) || empty($password)) {
                return json(['code' => 400, 'msg' => '请填写完整信息']);
            }
            
            // 查找用户
            $user = Db::name('users')->where('username', $username)->find();
            if (!$user) {
                return json(['code' => 400, 'msg' => '用户不存在']);
            }
            
            // 验证密码
            if (!password_verify($password, $user['password'])) {
                return json(['code' => 400, 'msg' => '密码错误']);
            }
            
            // 登录成功，保存Cookie（加密）
            $userData = [
                'user_id' => $user['id'],
                'username' => $user['username']
            ];
            
            // 加密用户数据并设置Cookie
            Cookie::set('user_auth', json_encode($userData), 3600 * 24 * 7); // 保存7天
            
            return json(['code' => 200, 'msg' => '登录成功', 'redirect' => '/photo']);
        }
        
        return View::fetch('user/login');
    }
    
    // 用户退出
    public function logout()
    {
        Cookie::delete('user_auth');
        return redirect('/user/login')->with('msg', '退出成功');
    }
    
    // 检查用户是否已登录的辅助方法
    protected function isLogin()
    {
        $userAuth = Cookie::get('user_auth');
        return !empty($userAuth);
    }
    
    // 获取当前登录用户信息的辅助方法
    protected function getUserInfo()
    {
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return null;
        }
        
        return json_decode($userAuth, true);
    }
}