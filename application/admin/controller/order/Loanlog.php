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
class Loanlog extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('LoanLog');

        // 取出所有用户
        $userist = db('user')->field('id,phone')->select();
        $userdata = [];
        foreach ($userist as $k => $v)
        {
            $userdata[0] ="请选择对应用户";
            $userdata[$v['id']] = $v['phone'];
        }

        // 取出代理商分组
        $agentlist = db('agent')->field('id,name,phone')->where('is_deleted',0)->select();
        $agentdata = [];
        foreach ($agentlist as $k => $v)
        {
            $agentdata[0] ="请选择代理商";
            $agentdata[$v['id']] = $v['name'].'/'.$v['phone'];
        }

        // 取出贷款产品分组
        $productlist = db('loan_product')->field('id,name')->where('is_deleted',0)->select();
        $productdata = [];
        foreach ($productlist as $k => $v)
        {
            $productdata[0] ="请选择贷款产品";
            $productdata[$v['id']] = $v['name'];
        }

        $this->view->assign('userdata', $userdata);
        $this->view->assign('agentdata', $agentdata);
        $this->view->assign('productdata', $productdata);
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
                        case 'product_name':
                            $k='c.name';
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

            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';


        //    list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total =db('loan_log')
                ->alias('a')
                ->join('user b','a.user_id=b.id')
                ->join('loan_product c','a.loan_product_id=c.id')
                ->join('agent d','a.agent_id=d.id','left')
                ->join('agent_commission_log e','a.id=e.log_id and type=2','left')
                ->field('a.id,b.phone as user_name,c.name as product_name,d.name as agent_name ,a.status,a.comment,a.apply_time,a.loan_price,e.status as log_status,e.commission,e.confirm_remark')
                ->where($where)
                ->count();
            $list=db('loan_log')
                ->alias('a')
                ->join('user b','a.user_id=b.id')
                ->join('loan_product c','a.loan_product_id=c.id')
                ->join('agent d','a.agent_id=d.id','left')
                ->join('agent_commission_log e','a.id=e.log_id and type=2','left')
                ->field('a.id,b.phone as user_name,c.name as product_name,d.name as agent_name,d.phone as agent_phone,a.status,a.comment,a.apply_time,a.loan_price,e.status as log_status,e.commission,e.confirm_remark')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
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
                       'card_id'=>$params['loan_product_id'],
                       'type'=>1,
                       'money'=>(int)$params['loan_price'],
                   );
                   $a=http_request_post($url,$data);
                   $all= json_decode($a,TRUE);

                   foreach ($all['data'] as $k=>$v){

                       if($v['agentid']==$params['agent_id']){

                           $params['commission']=$v['money'];
                           $params['apply_time']=date('Y-m-d H:i:s',time());
                           $params['status']=1;
                           $params['user_id']=$user_check['id'];
                           $agent_check=db('agent')->where('id',$params['agent_id'])->find();
                           if(!empty($agent_check)){
                               $datas['income_loan']=$agent_check['income_loan']+$v['money'];
                               db('agent')->where('id',$params['agent_id'])->update($datas);
                           }
                       }
                   }
                   $add=db('loan_log')->insert($params);
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
        $bank_card_log_list=db('loan_log')->field('agent_id,loan_product_id')->where('id',$row['id'])->find();
        $agent_name=db('agent')->where('id',$bank_card_log_list['agent_id'])->field('id,name,phone')->find();
        $card_name=db('loan_product')->where('id',$bank_card_log_list['loan_product_id'])->field('name')->find();
        $url=config('get_commission_url');
      //  var_dump($row);exit;
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if($params){

                if(!empty($params['status'])){
                    if($params['status']==2){
                        //修改订单状态为2，且根据当前的代理id，差出是否有上级的代理商，每个代理商在crd_agent_commission_log表插入一条
                      // var_dump($params);exit;
                        $datas=array(
                            'agentid'=>$row['agent_id'],
                            'card_id'=>$row['loan_product_id'],
                            'type'=>1,
                            'money'=>$params['loan_price'],
                        );
                        $a=http_request_post($url,$datas);
                        $all= json_decode($a,TRUE);
                        foreach ($all['data'] as $k=>$v){
                            if($v['agentid']==$row['agent_id']){
                                $current_agent_list=array(
                                    'agent_id'=>$row['agent_id'],//代理商id
                                    'user_id'=>$row['user_id'],//开卡用户id
                                    'type'=>2,//返佣类型
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
                        //首先添加当前代理的crd_agent_commission_log表记录
                        //添加代理
                        $current_agent_add=db('agent_commission_log')->insert($current_agent_list);
                        $bank_card_id= db('agent_commission_log')->getLastInsID();
                        if($current_agent_add){
                            $bank_card_list['status']=2;
                            $check=db('loan_log')->where('id',$row['id'])->update($bank_card_list);
                            //获取是否有上级的id
                            $agent_ids=$this->getAllParentAgentIds($row['agent_id']);
                            $agent_list=db('agent')->where('id','in',$agent_ids)->select();
                            if(count($agent_list) > 1){
                                foreach ($agent_list as $k=>$v){
                                    if($v['id']==$row['agent_id']){
                                        continue;
                                    }

                                    $datas=array(
                                        'agentid'=>$v['id'],
                                        'card_id'=>$row['card_id'],
                                        'type'=>1,
                                        'money'=>$params['loan_price'],
                                    );

                                    $a=http_request_post($url,$datas);
                                    $all= json_decode($a,TRUE);


                                    foreach ($all['data'] as $key=>$val){
                                        if($val['agentid']==$v['id']){
                                            $agent_add_data_list=array(
                                                'agent_id'=>$v['id'],//代理商id
                                                'user_id'=>$row['user_id'],//开卡用户id
                                                'type'=>2,//返佣类型
                                                'log_id'=>$row['id'],//crd_bank_card_log的id
                                                'commission'=>$val['money'],//佣金(差接口)
                                                'status'=>1,//返佣状态
                                                'parent_id'=>$bank_card_id,//对应的上级代理新增加的id
                                                'create_time'=>date('Y-m-d H:i:s',time()),//返佣时间
                                            );
                                            $agent_check=db('agent')->where('id',$v['id'])->find();
                                            if(!empty($agent_check)){
                                                $data['income_loan']=$agent_check['income_loan']+$val['money'];
                                                db('agent')->where('id',$v['id'])->update($data);
                                            }
                                        }
                                    }
                                    $all= db('agent_commission_log')->insert($agent_add_data_list);
                                }

                                if($all) {
                                    $this->code = 1;
                                    $this->msg = "数据添加成功！";
                                    exit;
                                }else{
                                    $this->code=-1;
                                    $this->msg="数据添加失败";
                                    exit;
                                }
                            }
                            $this->code = 1;
                            $this ->msg ="审核成功";
                            exit;

                        }else{
                            $this->code = -1;
                            $this->msg  ="数据添加失败！";
                        }

                    }elseif ($params['status']==3){
                        $check_status['status']=3;
                        $log_update=db('loan_log')->where('id',$row['id'])->update($check_status);
                        if($log_update){
                            $this->code=1;
                            $this->msg="修改成功";
                            exit;
                        }else{
                            $this->code=-1;
                            $this->msg="操作失败";
                            exit;
                        }
                    }
                }
                else{
                    $this->code=-1;
                    $this->msg="审核状态不能为空！";
                    exit;
                }

            }
            else{
                $this->code=-1;
                $this->msg='获取数据失败！';
            }
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }
        $row_list=array(
            'id'=>$row['id'],
            'user_name'=>$row['user_name'],//用户名
            'user_phone'=>$row['user_phone'],//用户电话
            'product_name'=>$card_name['name'],//产品名
            'agent_name'=>$agent_name['name'].'/'.$agent_name['phone'],//代理商名
            'commission'=>$row['commission'],//缺佣金的接口
            'comment'=>$row['comment'],//备注
            'status'=>$row['status'],
            'loan_price'=>$row['loan_price'],
        );
        $this->view->assign("row", $row_list);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }


    /**
     * 详情
     */
    public function details($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        $bank_card_log_list=db('loan_log')->field('agent_id,loan_product_id')->where('id',$row['id'])->find();
        $agent_name=db('agent')->where('id',$bank_card_log_list['agent_id'])->field('id,name,phone')->find();
        $card_name=db('loan_product')->where('id',$bank_card_log_list['loan_product_id'])->field('name')->find();
        if (!$row)
            $this->error(__('No Results were found'));

        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }


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
        $row_list=array(
            'id'=>$row['id'],
            'user_name'=>$row['user_name'],
            'user_phone'=>$row['user_phone'],
            'comment'=>$row['comment'],
            'status'=>$row['status'],
            'product_name'=>$card_name['name'],
            'commission'=>$row['commission'],
            'agent_name'=>$agent_name['name'].'/'.$agent_name['phone'],
        );

        $this->view->assign("row", $row_list);
        $agent_arrs=!empty($agent_arr)?$agent_arr:'';
        $this->view->assign("agent", $agent_arrs);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function priceedit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        $bank_card_log_list=db('loan_log')->field('agent_id,loan_product_id')->where('id',$row['id'])->find();
        $agent_name=db('agent')->where('id',$bank_card_log_list['agent_id'])->field('id,name,phone')->find();
        $card_name=db('loan_product')->where('id',$bank_card_log_list['loan_product_id'])->field('name')->find();
        if (!$row)
            $this->error(__('No Results were found'));

        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }


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
        $row_list=array(
            'id'=>$row['id'],
            'user_name'=>$row['user_name'],
            'user_phone'=>$row['user_phone'],
            'comment'=>$row['comment'],
            'status'=>$row['status'],
            'product_name'=>$card_name['name'],
            'commission'=>$row['commission'],
            'agent_name'=>$agent_name['name'].'/'.$agent_name['phone'],
        );

        $this->view->assign("row", $row_list);
        $agent_arrs=!empty($agent_arr)?$agent_arr:'';
        $this->view->assign("agent", $agent_arrs);
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
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->code = -1;
    }
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
