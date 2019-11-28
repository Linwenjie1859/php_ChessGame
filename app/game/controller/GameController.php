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

use cmf\controller\RestUserBaseController;
use think\Db;
use function MongoDB\BSON\toJSON;


class GameController extends RestUserBaseController
{
    public function match(){
        $games=cache('gamesLists');

        if(empty($games)){
            $games=array();
        }
        $userGame=null;
        $userGameKey=0;
        foreach($games as $key=>$game){
            if($game['uid1'] == $this->userId || $game['uid2'] == $this->userId){
                $userGame=$game;
                $userGameKey=$key;
                break;
            }
        }
        //有点过play的用户
        if($userGame){
            if($userGame['id']!=0){ //可以开始游戏
                array_splice($games,$userGameKey,1);
            }else{  //还需要等待一个玩家，更新一下时间
                $userGame['update_time']=time();
                $games[$userGameKey]=$userGame;
            }
        }else{
            //不存在用户
            if(!empty($games)){
                $userGame=$games[0];    //获取队列中的第一个游戏
            }
            //如果第一个游戏存在并且没有匹配的话
            if($userGame && $userGame['id']==0){
                //设置连接时间为15s
                if(time()-$userGame['update_time']<15){
                    $userGame['uid2']=$this->userId;    //当前用户是第二个玩家
                    $userGame['update_time']=time();    //刷新时间
                    $userGame['start_time']=$userGame['update_time']+20;   //游戏开始时间
                    $userGame['round_limit_time']=$userGame['start_time']+20;   //回合时间
                    $chessboard = [1, 1, 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 11, 11, 11, 11, 11, 12, 12, 13, 13, 14, 14, 15, 15, 16, 16, 17];
                    for ($i = 0; $i < 32; $i++) {
                        $r =rand(0,31);
                        $temp = $chessboard[$i];
                        $chessboard[$i] = $chessboard[$r];
                        $chessboard[$r] = $temp;
                    }
                    $userGame['chessboard']=json_encode($chessboard);  //将$chessboard转化成json字符串
                    $userGame['current_player']=1;  //当前方
                    $userGame['round']=1;   //回合数
                    $userGame['id']=Db::name('game')->insertGetId($userGame);
                    $games[0]=$userGame;
                }else{
                    //有用户超时连接，那么就将第二个用户替换第一个用户
                    $userGame['uid1']=$this->userId;
                    $userGame['update_time']=time();
                    $games[0]=$userGame;
                }
            }else{
                $userGame=array('id'=>0,'uid1'=>$this->userId,'uid2'=>0,'update_time'=>time());
                array_splice($games,0,0,array($userGame));
            }
        }
        cache('gamesLists',$games);
        $this->success('match',$userGame);
    }

    public function get_game_info(){
        $data = $this->request->post();
        $gameInfo=Db::name("game")->where('id',$data['id'])->find();
        $this->success('gameInfo',$gameInfo);
    }

    public function updata_info(){
        $updataInfo='';
        $data = $this->request->post();
        $data['update_time']=time();
        $gameInfo=Db::name("game")->where('id',$data['id'])->update($data);
        if($gameInfo==1){
            $updataInfo=Db::name("game")->where('id',$data['id'])->find();
        }
        $this->success('updataInfo',$updataInfo);
    }

}
