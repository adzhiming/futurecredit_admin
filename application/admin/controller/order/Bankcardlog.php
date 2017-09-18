<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Bankcardlog extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BankCardLog');
        // 取出所有用户
        $userist = db('user')->field('id,phone')->select();
        $userdata = [];
        foreach ($userist as $k => $v)
        {
            $userdata[0] ="请选择对应用户";
            $userdata[$v['id']] = $v['phone'];
        }

        // 取出信用卡分组
        $cardlist = db('bank_card')->field('id,card_name')->where('is_deleted',0)->select();
        $carddata = [];
        foreach ($cardlist as $k => $v)
        {
            $carddata[0] ="请选择信用卡";
            $carddata[$v['id']] = $v['card_name'];
        }

        // 取出代理商分组
        $agentlist = db('agent')->field('id,name,phone')->where('is_deleted',0)->select();
      //  var_dump($agentlist);
        $agentdata = [];
        foreach ($agentlist as $k => $v)
        {
            $agentdata[0] ="请选择代理商";
            $agentdata[$v['id']] = $v['name'].'/'.$v['phone'];
        }

        $this->view->assign('userdata', $userdata);
        $this->view->assign('carddata', $carddata);
        $this->view->assign('agentdata', $agentdata);
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax())
        {

            $filter = json_decode($_GET['filter'],true);
            $where = array('a.is_deleted'=>0);
            if (!empty($filter))
            {
                foreach( $filter as $k=>$v)
                {
                    switch ($k) {
                        case 'id':
                            $k='a.id';
                            $where[$k] = $v;
                            break;
                        case 'user_name':
                            $k='b.phone';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'card_name':
                            $k='c.card_name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'agent_name':
                            $k='d.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'status':
                            $k='a.status';
                            $where[$k] = $v;
                            break;
                        case 'apply_time':
                            $k='a.apply_time';
                            $where[$k] = $v;
                            break;
                    }

                }
            }
            

            if(!empty($filter['apply_time']))
            {
                list($start,$end) = explode(',', $filter['apply_time']);
                $where['a.apply_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            
            if(!empty($filter['user_name'])) $where['b.phone'] = array('like','%'.$filter['user_name'].'%');
            if(!empty($filter['card_name'])) $where['c.card_name'] = array('like','%'.$filter['card_name'].'%');
            
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';


         //  list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total =db('bank_card_log')
                ->alias('a')
                ->join('user b','a.user_id=b.id')
                ->join('bank_card c','a.card_id=c.id')
                ->join('agent d','a.agent_id=d.id','left')
                ->field('a.id,a.agent_id,b.phone as user_name,c.card_name,d.name as agent_name,d.phone as agent_phone,a.status,a.comment,a.apply_time,a.user_name,a.user_phone,a.commission')
                ->where($where)
                ->count();
            $list=db('bank_card_log')
                ->alias('a')
                ->join('user b','a.user_id=b.id')
                ->join('bank_card c','a.card_id=c.id')
                ->join('agent d','a.agent_id=d.id','left')
                ->field('a.id,a.agent_id,b.phone as user_name,c.card_name,d.name as agent_name,d.phone as agent_phone,a.status,a.comment,a.apply_time,a.user_name,a.user_phone,a.commission,a.card_id')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

//            $url=config('get_commission_url');
//            $lists=array();
//            foreach ($list as $k=>$v){
//
//                $data=array(
//                    'agentid'=>$v['agent_id'],
//                    'card_id'=>$v['card_id'],
//                    'type'=>1,
//                    'money'=>(int)0,
//                );
//                $a=http_request_post($url,$data);
//                $all= json_decode($a,TRUE);
//
//                foreach ($all['data'] as $key=>$value){
//                    if($v['agent_id']==$value['agentid']){
//                        $lists=array(
//                            'id'=>$v['id'],
//                            'agent_id'=>$v['agent_id'],
//                            'user_name'=>$v['user_name'],
//                            'card_name'=>$v['card_name'],
//                            'agent_name'=>$v['agent_name'],
//                            'agent_phone'=>$v['agent_phone'],
//                            'status'=>$v['status'],
//                            'comment'=>$v['comment'],
//                            'apply_time'=>$v['apply_time'],
//                            'user_phone'=>$v['user_phone'],
//                            'card_id'=>$v['card_id'],
//                            'commission'=>$value['money'],
//                        );
//                    }
//                }
//
//
//            }






            $result = array("total" => $total, "rows" => $list);
            return json($result);

        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params)
            {
                $user_check=db('user')->where('phone',$params['user_phone'])->find();
                if(!empty($user_check)){
                    $url=config('get_commission_url');
                    $data=array(
                        'agentid'=>$params['agent_id'],
                        'card_id'=>$params['card_id'],
                        'type'=>1,
                        'money'=>(int)0,
                    );
                    $a=http_request_post($url,$data);
                    $all= json_decode($a,TRUE);
                    foreach ($all['data'] as $k=>$v){
                        if($v['agentid']==$params['agent_id']){
                            $params['commission']=$v['money'];
                            $params['status']=1;
                            $params['user_id']=$user_check['id'];
                            $agent_check=db('agent')->where('id',$params['agent_id'])->find();
                            if(!empty($agent_check)){
                                $datas['income_loan']=$agent_check['income_loan']+$v['money'];
                                db('agent')->where('id',$params['agent_id'])->update($datas);
                            }
                        }
                    }
                    $params['apply_time']=date('Y-m-d H:i:s',time());
                    $add=db('bank_card_log')->insert($params);
                    if($add){
                        $this->code = 1;
                        $this->msg ="数据添成功";
                        exit;
                    }else{
                        $this->code = -1;
                        $this->msg ="数据添加失败";
                        exit;
                    }
                }else{
                    $this->code = -1;
                    $this->msg ="用户不存在";
                    exit;
                }

            }
            return;
        }
        return $this->view->fetch();
    }


    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        $bank_card_log_list=db('bank_card_log')->field('agent_id,card_id')->where('id',$row['id'])->find();
        $agent_name=db('agent')->where('id',$bank_card_log_list['agent_id'])->field('id,name,phone')->find();
        $card_name=db('bank_card')->where('id',$bank_card_log_list['card_id'])->field('card_name')->find();
        $url=config('get_commission_url');
        $datas=array(
            'agentid'=>$row['agent_id'],
            'card_id'=>$row['card_id'],
            'type'=>1,
            'money'=>(int)0,
        );
        $a=http_request_post($url,$datas);
        $all= json_decode($a,TRUE);
        foreach ($all['data'] as $k=>$v){
            if($v['agentid']==$row['agent_id']){
                $row_list['commission']=$v['money'];
            }
        }
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if($params){
                if(!empty($params['status'])){
                    if($params['status']==2){
                        //修改订单状态为2，且根据当前的代理id，差出是否有上级的代理商，每个代理商在crd_agent_commission_log表插入一条新数
                     //首先添加当前代理的crd_agent_commission_log表记录
                        $a=http_request_post($url,$datas);
                        $all= json_decode($a,TRUE);
                        foreach ($all['data'] as $k=>$v){
                            if($v['agentid']==$row['agent_id']){
                            //    $current_agent_list['commission']=;
                                    $current_agent_list=array(
                                    'agent_id'=>$row['agent_id'],//代理商id
                                    'user_id'=>$row['user_id'],//开卡用户id
                                    'type'=>1,//返佣类型
                                    'log_id'=>$row['id'],//crd_bank_card_log的id
                                    'commission'=>$v['money'],//佣金
                                    'status'=>1,//返佣状态
                                    'create_time'=>date('Y-m-d H:i:s',time()),//返佣时间
                                    );
                                $agent_check=db('agent')->where('id',$row['agent_id'])->find();
                                if(!empty($agent_check)){
                                    $data['income_loan']=$agent_check['income_loan']+$v['money'];
                                    db('agent')->where('id',$row['agent_id'])->update($data);
                                }
                            }
                        }

                      //  var_dump($current_agent_list);exit;
                        //添加代理
                        $current_agent_add=db('agent_commission_log')->insert($current_agent_list);
                        $bank_card_id= db('agent_commission_log')->getLastInsID();
                        if($current_agent_add){
                            $bank_card_list['status']=2;
                            db('bank_card_log')->where('id',$row['id'])->update($bank_card_list);
                            //获取是否有上级的id
                            $agent_ids=$this->getAllParentAgentIds($row['agent_id']);
                            $agent_list=db('agent')->where('id','in',$agent_ids)->select();

                            if(count($agent_list) > 1) {
                                foreach ($agent_list as $k => $v) {
                                    if ($v['id'] == $row['agent_id']) {
                                        continue;
                                    }
                                    $data1=array(
                                        'agentid'=>$v['id'],
                                        'card_id'=>$row['card_id'],
                                        'type'=>1,
                                        'money'=>(int)0,
                                    );

                                    $a=http_request_post($url,$data1);
                                    $all= json_decode($a,TRUE);

                                    foreach ($all['data'] as $key=>$val){
                                        if($val['agentid']==$v['id']){
                                            //$agent_add_data['commission']=$val['money'];
                                            $agent_add_data = array(
                                                'agent_id' => $v['id'],//代理商id
                                                'user_id' => $row['user_id'],//开卡用户id
                                                'type' => 1,//返佣类型
                                                'log_id' => $row['id'],//crd_bank_card_log的id
                                                'commission' => $val['money'],//佣金(差接口)
                                                'status' => 1,//返佣状态
                                                'parent_id' => $bank_card_id,//对应的上级代理新增加的id
                                                'create_time' => date('Y-m-d H:i:s', time()),//返佣时间
                                            );
                                            $agent_check=db('agent')->where('id',$v['id'])->find();
                                            if(!empty($agent_check)){
                                                $data['income_loan']=$agent_check['income_loan']+$val['money'];
                                                db('agent')->where('id',$v['id'])->update($data);
                                            }
                                        }
                                    }
                                    $agent_add = db('agent_commission_log')->insert($agent_add_data);
                                }
                                if ($agent_add) {
                                    $this->code = 1;
                                } else {
                                    $this->code = -1;
                                    $this->msg = "数据添加失败！";
                                }
                            }
                            $this->code = 1;
                            $this ->msg ="审核成功";
                            exit;
                        }

                    }elseif ($params['status']==3){
                        //只修改订单状态为3
                        $check_status['status']=3;


                        $url=config('get_commission_url');
                        $data=array(
                            'agentid'=>$row['agent_id'],
                            'card_id'=>$row['card_id'],
                            'type'=>1,
                            'money'=>(int)0,
                        );
                        $a=http_request_post($url,$data);
                        $all= json_decode($a,TRUE);
                        foreach ($all['data'] as $k=>$v){
                            if($v['agentid']==$row['agent_id']){
                                $check_status['commission']=$v['money'];
                            }
                        }
                        $log_update=db('bank_card_log')->where('id',$row['id'])->update($check_status);
                        if($log_update){
                            $this->code=1;
                        }else{
                            $this->code=-1;
                            $this->msg="操作失败";
                        }
                    }
                }else{
                    $this->code=-1;
                    $this->msg="审核状态不能为空！";
                }

            }else{
                $this->code=-1;
                $this->msg='获取数据失败！';
            }


            return;
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }

        $row_list['id']=$row['id'];
        $row_list['user_name']=$row['user_name'];
        $row_list['user_phone']=$row['user_phone'];
        $row_list['comment']=$row['comment'];
        $row_list['status']=$row['status'];
        $row_list['card_name']=$card_name['card_name'];
        $row_list['agent_name']=$agent_name['name'].'/'.$agent_name['phone'];

        $this->view->assign("row", $row_list);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }


    /**
     * 删除
     */
    public function del($ids = "")
    {
        $this->code = -1;
        if (!empty($ids))
        {
           
               $row = $this->model->get(['id' => $ids]);
            $params['is_deleted'] = 1;
            $count = $row->save($params);
            if ($count)
            {
                $this->code = 1;
            }
        }else{
            echo 1;
        }

        return;
    }

    /**
     * 批量更新
     * @internal
     */


    public function details($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        $bank_card_log_list=db('bank_card_log')->field('agent_id,card_id')->where('id',$row['id'])->find();
        $agent_name=db('agent')->where('id',$bank_card_log_list['agent_id'])->field('id,name,phone')->find();
        $card_name=db('bank_card')->where('id',$bank_card_log_list['card_id'])->field('card_name')->find();
        if (!$row)
            $this->error(__('No Results were found'));
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }

        //获取当前订单所有代理商的crd_agent_commission_log表信息
        $agent_commission_list=db('agent_commission_log')->field('agent_id,commission')->where('log_id',$row['id'])->select();
        foreach ($agent_commission_list as $key=>$value){
            $arr[]= $value['agent_id'];
        }
        //获取$agent_commission_list的commission(佣金)，获取$agent_data里面的level,name,phone，和成新的多维数组
        foreach ($agent_commission_list as $keys=>$values){
            $agent_data=db('agent')->where('id',$values['agent_id'])->field('level,name,phone')->order('level desc')->find();
                $agent_arr[]=array(
                    'commission'=>$values['commission'],//佣金
                    'agent_name'=>$agent_data['name'].'/'.$agent_data['phone'],//名称
                    'level'=>(int)$agent_data['level'],//等级
                );

        }
       // var_dump($agent_arr);exit;
        $row_list=array(
            'id'=>$row['id'],
            'user_name'=>$row['user_name'],
            'user_phone'=>$row['user_phone'],
            'comment'=>$row['comment'],
            'status'=>$row['status'],
            'card_name'=>$card_name['card_name'],
        );

        $this->view->assign("row", $row_list);
        $agent_arrs=!empty($agent_arr)?$agent_arr:'';
        $this->view->assign("agent", $agent_arrs);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }



    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->code = -1;
    }



//获取所有上级代理商
  public  function getAllParentAgentIds($aid){
        $ParentAgentIds = db('agent')->field("id,parentid,level")->select();
        return $this->getParents($ParentAgentIds,$aid);
    }

//获取某个分类的所有父分类
  public  function getParents($categorys,$aid){
        $subs="";
        foreach($categorys as $item){
            if($item['id']==$aid){
                $subs = $item['id'];
                if($this->getParents($categorys,$item['parentid'])){
                    $subs=$subs.",".$this->getParents($categorys,$item['parentid']);
                }
            }
        }
        return $subs;
    }


}
