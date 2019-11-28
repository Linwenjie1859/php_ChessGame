<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\game\controller;

use cmf\controller\RestBaseController;
use think\Db;
use think\facade\Validate;
use app\user\model\UserModel;

class UserController extends RestBaseController
{

    // http://www.gs.com/game/user/doRegister?password=123162&&username=1232ddd@qq.com
    /**
     * 前台用户注册提交
     */
    public function doRegister()
    {
        $rules = [
            'password' => 'require|min:6|max:32',
        ];

        $validate = new \think\Validate($rules);
        $validate->message([
            'password.require' => '密码不能为空',
            'password.max'     => '密码不能超过32个字符',
            'password.min'     => '密码不能小于6个字符',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }


        $register          = new UserModel();
        $user['user_pass'] = $data['password'];
        if (Validate::is($data['username'], 'email')) {
            $user['user_email'] = $data['username'];
            $log                = $register->register($user, 3);
        } else if (cmf_check_mobile($data['username'])) {
            $user['mobile'] = $data['username'];
            $log            = $register->register($user, 2);
        } else {
            $log = 2;
        }
        switch ($log) {
            case 0:
                $this->success('注册成功');
                break;
            case 1:
                $this->error("您的账户已注册过");
                break;
            case 2:
                $this->error("您输入的账号格式错误");
                break;
            default:
                $this->error('未受理的请求');
        }
    }

    public function getUserId()
    {
       return parent::getUserId();
    }

    /**
     * 登录验证提交
     */
    public function doLogin()
    {
        $validate = new \think\Validate([
            'username' => 'require',
            'password' => 'require|min:6|max:32',
        ]);
        $validate->message([
            'username.require' => '用户名不能为空',
            'password.require' => '密码不能为空',
            'password.max'     => '密码不能超过32个字符',
            'password.min'     => '密码不能小于6个字符',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
//      以上是验证数据部分


        $userModel         = new UserModel();
        $user['user_pass'] = $data['password'];
        if (Validate::is($data['username'], 'email')) {
            $user['user_email'] = $data['username'];
            $log                = $userModel->doEmail($user);
        } else if (cmf_check_mobile($data['username'])) {
            $user['mobile'] = $data['username'];
            $log            = $userModel->doMobile($user);
        } else {
            $user['user_login'] = $data['username'];
            $log                = $userModel->doName($user);
        }
        $value= Db::table('cmf_user')->where('user_email','=',$data['username'])->field('id')->find();
        $value['token']=session('token');
        switch ($log) {
            case 0:
                cmf_user_action('login');
                $this->success(lang('LOGIN_SUCCESS'),$value);
                break;
            case 1:
                $this->error(lang('PASSWORD_NOT_RIGHT'));
                break;
            case 2:
                $this->error('账户不存在');
                break;
            case 3:
                $this->error('账号被禁止访问系统');
                break;
            default:
                $this->error('未受理的请求');
        }
    }
}
