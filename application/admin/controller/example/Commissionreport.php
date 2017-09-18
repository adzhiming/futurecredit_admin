<?php
namespace app\admin\controller\example;

use app\common\controller\Backend;
use think\db\Query;


class Commissionreport extends Backend
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
            
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);   //获取查询的字段
         
            $start_date='';
            $end_date='';
            $re_where='';
             if(!empty($filter['createtime']))
            {
                list($start_date,$end_date) = explode(',', $filter['createtime']);
                
            }
           
            if(!empty($filter['phone']))  $re_where = " and phone like '%{$filter['phone']}%'";
            if(!empty($filter['name'])) $re_where .= " and name like '%{$filter['name']}%'";
          
            $start = date("Y-m-d H:i:s",strtotime("-7 day"));
            if($start_date)
            {
                $start = date("Y-m-d H:i:s",$start_date);
            }
           
            $end = date("Y-m-d H:i:s",strtotime("-1 day"));
            if($end_date)
            {
                $end = date("Y-m-d H:i:s",$end_date);
            }
           
            $re_where .= " and a.confirm_time between "."'{$start}'"." and "."'{$end}'";

        
            $sql = "select agent_id,name,phone,sum(card_cnt) card_cnt,sum(card_comm) card_comm,sum(loan_price) loan_price,sum(loan_comm) loan_comm
                 from (
				select a.agent_id,c.name,c.phone,0 card_cnt ,0 card_comm,sum(b.loan_price) loan_price,sum(a.commission) loan_comm
					from crd_agent_commission_log a,
							 crd_loan_log b,
							 crd_agent c
				 where b.id = a.log_id
					 and c.id = a.agent_id
					 and a.type = 2
					 and a.status = 2".
                        $re_where
				 ."group by a.agent_id 
				union all 
				select a.agent_id,c.name,c.phone,count(distinct b.id) card_cnt,sum(a.commission) commission,0,0
					from crd_agent_commission_log a,
							 crd_bank_card_log b,
							 crd_agent c
				 where b.id = a.log_id
					 and c.id = a.agent_id
					 and a.type = 1
					 and a.status = 2".
                        $re_where
				 ."group by a.agent_id )b
                group by agent_id";
            
            $list =  db()->query($sql);
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);       
            return json($result);
       }
        return $this->view->fetch();
    }
    
    
    
    public function details()
    {
  
          
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
            if ($params)
            {
 
                    $result = $this->model->create($params);
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
    
    
    /*
     * 编辑
     */
    public function edit($ids = NULL)
    {   
        $row = model('agent_apply')->get($ids); 
        $status = $this->request->get('status');
        $this->assign('status',$status);
        if (!$row)
            $this->error(__('No Results were found'));
     
        
        if ($this->request->isPost())
        {
        
            $this->code = -1;
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['confirm_time'] = date("Y-m-d H:i:s");
                
                if($params['status'] == 'yes')
                {                  
                    $params['status'] = 2;
                    $result = $row->save($params);
                     
                    if ($result !== false)
                    {
                        $salt='';
                        for ($i = 1; $i <= 6; $i++)
                        {
                            $salt .= chr(rand(97, 122));
                        }
                    
                        $data['phone'] = $row['phone'];
                        $data['salt'] = $salt;
                        $data['password'] = md5($salt.'123456');
                        $data['create_time'] = date("Y-m-d H:i:s");
                        $insert = model('agent')->insert($data);
                    
                        if(false !== $insert) $this->code = 1;
                         
                    }
                    else
                    {
                        $this->msg = $row->getError();
                    }
                }
                else
                {
                    $params['status'] = 3;
                    $result = $row->save($params);
                    
                    if(false !== $result) $this->code = 1;
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
}