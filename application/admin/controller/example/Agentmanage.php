<?php
namespace app\admin\controller\example;

use app\common\controller\Backend;

class Agentmanage extends Backend
{
    public $model;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('agent');
    }
    
    
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $re_where= array('is_deleted' => 0);
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);//var_dump($filter);exit;
            
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $re_where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['level'])) $re_where['level'] = $filter['level'];
            if(!empty($filter['name'])) $re_where['name'] = array('like','%'.$filter['name'].'%');
            if(!empty($filter['phone'])) $re_where['phone'] =  array('like','%'.$filter['phone'].'%');;
            
            $total = $this->model
            ->where($re_where)
            ->order($sort, $order)
            ->count();
        
            $list = $this->model
            ->where($re_where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
            
            foreach ($list as $k => &$v)
            {
                $list[$k]['superior'] = '未莱信息';
                if($v['parentid'] != 0)
                {
                    $parentid_id = $v['parentid'];
                   
                    $parend = $this->model
                    ->field('name')
                    ->where('id',$parentid_id)
                    ->find();
                  
             
                    //var_dump($subordinate_quantity);exit;
                    $list[$k]['superior'] = $parend['name'];
                   
                }
               
                 
               switch ($v['level'])
               {
                   case 0: $v['level'] = '特级代理'; break;
                   case 1: $v['level'] = '一级代理'; break;
                   case 2: $v['level'] = '二级代理'; break;
                   case 3: $v['level'] = '三级代理'; break;
               } 
               
               
                $id = $v['id'];
                $subordinate_quantity = $this->model
                ->where('parentid',$id)
                ->field('count(id) as count')
                ->find();
                $list[$k]['subordinate'] = $subordinate_quantity['count'];
                
               
            }
           // var_dump($list);exit;
            $result = array("total" => $total, "rows" => $list);
        
            return json($result);
       }
        return $this->view->fetch();
    }
    //详情
    public function details($ids = NULL)
    {
        $row = model('agent')->get($ids);
        $status = $this->request->get('status');
        $this->assign('status',$status);
    
        if (!$row)
            $this->error(__('No Results were found'));
        
        $row['superior'] = 0;
        $row['superior'] = '未莱信息';
        if($row['parentid'] != 0)
        {
            $parentid_id = $row['parentid'];
             
            $parend = $this->model
            ->where('id',$parentid_id)
            ->find();
        
            $row['superior'] = $parend['name'];
             
        }
        
        
        switch ($row['level'])
        {
            case 0: $row['level'] = '特级代理'; break;
            case 1: $row['level'] = '一级代理'; break;
            case 2: $row['level'] = '二级代理'; break;
            case 3: $row['level'] = '三级代理'; break;
        }
         
        
        $row['subordinate'] = 0;
        $id = $row['id'];
        $subordinate_quantity = $this->model
        ->where('parentid',$id)
        ->field('count(id) as count')
        ->find();
        $row['subordinate'] = $subordinate_quantity['count'];
        
    
        $author = model('admin')->where('id', $row['operator'])->field('username')->find();

    
        $this->assign('author',$author['username']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
    
    /*
     * 新增
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a");
            
            $check_phone =  $params['phone']; 
            
            $agent = $this->model->where("phone",$check_phone)->find();
            $agent_apply = model('agent_apply')->where("phone",$check_phone)->find();
            
            if($agent || $agent_apply){ 
                $this->msg = '手机号码已经存在';    
                return;             ;
            }
           // var_dump($params);exit;
            
            if ($params)
            {
            
                
                    //$salt='';
                    //for ($i = 1; $i <= 6; $i++)
                    //{
                     //   $salt .= chr(rand(97, 122));
                    //}
                   // $params['salt'] = $salt;
                    $params['password'] = base64_encode ($params['password']);
                    if(session('user_name')){
                        $operator = session('user_name');
                        $operator = model('admin')->where('username',$operator)->field('id')->find();
                    }
                    $params['operator'] = $operator['id'];
                    $params['apply_time'] = date("Y-m-d H:i:s",time());
                    //$result = $this->model->create($params);
                    $result = model('agent_apply')->insert($params);
                    if ($result !== false)
                    {
                        $this->code = 1;
                    }
                    else
                    {
                        $this->msg = $this->model->getError();
                    }
              
              
            }
            else
            {
                $this->msg = __('Parameter %s can not be empty', '');
            }
    
            return;
        }
        return $this->view->fetch();
    }
    
    
    //获取短信验证码
    public function getverificationcode()
    {
        $userid = $this->request->post('userid');
    
        if($userid)
        {
            $list = $this->model
            ->where('id',$userid)
            ->find();
            
            $phone = $list['phone'];
            $name = $list['name'];
            
            vendor('alidayun.sendMessageCode');
            
            session($phone,null);
            $code = rand(100000, 999999);//验证码
            
            
            $message = new \sendMessageCode();
          
           // $phone = '15113659981';

            $data = array(
                'code' => "$code",
                'name' => $name
            );
 
            $result = $message->sendSMSMessage(json_encode($data, JSON_UNESCAPED_UNICODE), $phone); //var_dump($result);
            
            if($result->result->success == "true")
            {
            session($phone, $code);
            $this->code = 0;
            $this->msg = '发送成功';
              return ;
            }

            $this->code = 1;
            $this->msg = '发送验证码失败原因：' . $result->sub_msg;
            return ;
        }
    }
    
    /*
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a"); 
            if ($params)
            {   
                   /*  $params['code'];
                    if(empty($params['code']))
                    {
                        $this->code = 2 ;
                        $this->msg = '请输入验证码';
                        return ;
                    } 
                    
                    $phone = $row['phone'];
                    $code = session($phone);
                    
                    if( $params['code'] != $code)
                    {
                        $this->code = 2 ;
                        $this->msg = '验证码不正确，请重新输入';
                        return ;
                    }
                    unset($params['code']);*/
                    if($params['password'] != '******')
                    {
                        $params['password'] = md5($params['password'].$row['salt']);
                    }else{
                        unset($params['password']);
                    }
                    
                    $params['update_time'] = date("Y-m-d H:i:s");
                    $result = $row->save($params);
                    
                    if ($result !== false)
                    {
                        $this->code = 1;
                    }
                    else
                    {
                        $this->msg = $row->getError();
                    }
               
            }
            else
            {
                $this->msg = __('Parameter %s can not be empty', '');
            }
        
            return;
        }
        

             
    
     
        $this->view->assign("row", $row);
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
}