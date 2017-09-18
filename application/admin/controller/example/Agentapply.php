<?php
namespace app\admin\controller\example;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;

class Agentapply extends Backend
{
    public $model;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('agent_apply');
        
        
        $groups = $this->auth->getGroups();
 
        // 取出所有分组
        $grouplist = model('AuthGroup')->all(['status' => 'normal']);
        $objlist = [];
        foreach ($groups as $K => $v)
        {
            // 取出包含自己的所有子节点
            $childrenlist = Tree::instance()->init($grouplist)->getChildren($v['id'], TRUE);
            $obj = Tree::instance()->init($childrenlist)->getTreeArray($v['pid']);
            $objlist = array_merge($objlist, Tree::instance()->getTreeList($obj));
        }
        $groupdata = [];
        foreach ($objlist as $k => $v)
        {
            $groupdata[$v['id']] = $v['name'];
        }
        $this->childrenIds = array_keys($groupdata);
        $this->view->assign('groupdata', $groupdata);
    }
    
    
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //$re_where['status'] = array('in',array(1,3));
            $re_where = '';
            
            $filter = $this->request->get("filter", ''); 
            $filter = json_decode($filter, TRUE);   //获取查询的字段
      
            if(!empty($filter['confirm_time']))
            {                
              list($start,$end) = explode(',', $filter['confirm_time']);                
              $re_where['confirm_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }// var_dump($re_where['confirm_time']);exit;
            if(!empty($filter['phone'])) $re_where['phone'] = array('like','%'.$filter['phone'].'%');
            if(!empty($filter['status'])) $re_where['status'] = $filter['status'];
            

           
            
            $total = $this->model
            ->where($re_where)
            ->order($sort, $order)
            ->count();
        
            $list = $this->model
            ->where($re_where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
           // var_dump($list);exit;
            
            foreach ($list as $k => &$v)
            {
                if($v['status'] == '1')
                {
                    $v['status'] = '待审核';
                }
                else if ($v['status'] == '2')
                {
                    $v['status'] = '审核通过';
                }
                else 
                {
                    $v['status'] = '审核失败';
                }
                $list[$k]['superior'] = '未莱信息';
            }
            
         
            $result = array("total" => $total, "rows" => $list);
        
            return json($result);
       }
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
    
    //详情
    public function details($ids = NULL)
    {
        if($this->request->isPost())
        {
        $card_id = $this->request->post('card');
        $loan_id = $this->request->post('loan');
        $level1_num = $this->request->post('level1_num');
        $level2_num = $this->request->post('level2_num');
        $level3_num = $this->request->post('level3_num');
        $id = $this->request->post('id');
      //var_dump($card_id); var_dump($loan_id);exit;
        $agent_phone = $this->model->where('id',$id)->field('phone')->find();   
        $agent_id = model('agent')->where('phone',$agent_phone['phone'])->field('id,level')->find();
        
        $num = db("agent_num")->where("agent_id = '{$agent_id['id']}'")->find();
        $has_num = true;
        if(!$num){
        	$has_num = false;
        	$num = db("agent_num")->where("agent_id = '0'")->find();
        }
        $flag =false;
        if($level1_num != $num['level1_num'] || $level2_num != $num['level2_num'] || $level3_num != $num['level3_num']){
        	   $flag =true;
        }
        if($flag){
        	 $up_num= array();
        	 $up_num['level1_num'] = $level1_num;
        	 $up_num['level2_num'] = $level2_num;
        	 $up_num['level3_num'] = $level3_num;
        	 if($has_num){
        	 	$rs = db("agent_num")->where("agent_id = '{$agent_id['id']}'")->update($up_num);
        	 }
        	else{
        		$up_num['agent_id'] = $agent_id['id'];
        		$up_num['level'] = $agent_id['level'];
        		$rs = db("agent_num")->insert($up_num);
        	}
        }
        //modify
        if(empty($card_id)){
         	    //删除关系表
         	   $rs = db('agent_bank_loan')->where("agent_id ='{$agent_id['id']}' and type = 1")->delete();
        }
        else
        {
        	$old = array();
        	$diff = array();
        	$rs = db('agent_bank_loan')->field("bank_loan_id")->where("agent_id ='{$agent_id['id']}' and type = 1")->select();
        	if($rs){
        		foreach ($rs as $ov){
        			$old[] = $ov['bank_loan_id'];
        		}
        	}
        	
        	$arr = explode(",",$card_id);
        	$diff = array_diff($old,$arr);
        	foreach ($arr as $v){
        		  //处理关系表
        		  $has = model('agent_bank_loan')->where(array('agent_id'=>$agent_id['id'],'type'=>1,'bank_loan_id'=>$v))->find();
        		  if(!$has){
        		  	    $data = array();
	        		  	$data['agent_id'] = $agent_id['id'];
	        		  	$data['create_time'] = date("Y-m-d H:i:s",time());
	        		  	$data['bank_loan_id'] =  $v;
	        		  	$data['type'] =  1;
	        		  	$list = model('agent_bank_loan')->insert($data);
        		  }
        		  
        		  //处理代理商报价单
        		  $has = model('agent_sys_price')->where(array('agent_id'=>$agent_id['id'],'type'=>1,'bank_id'=>$v,'fy_type'=>1,'level'=>$agent_id['level']))->find();
        		  if(!$has){
        		  	 //找出银行卡的报价
        		  	$data = array();
        		  	$bank_price = db("bank")->field("level_price,level1_price,level2_price,level3_price")->where("id ='{$v}' and is_deleted = 0")->find();
        		  	if($bank_price){
        		  		$data['level_price'] = $bank_price['level_price'];
        		  		$data['level1_price'] = $bank_price['level1_price'];
        		  		$data['level2_price'] = $bank_price['level2_price'];
        		  		$data['level3_price'] = $bank_price['level3_price'];
        		  		$data['type'] =  1;
        		  		$data['bank_id'] =  $v;
        		  		$data['card_id'] =  $v;
        		  		$data['fy_type'] =  1;
        		  		$data['level'] =  $agent_id['level'];
        		  		$data['agent_id'] = $agent_id['id'];
        		  		$data['create_time'] = date("Y-m-d H:i:s",time());
        		  		$list = model('agent_sys_price')->insert($data);
        		  	}	
        		  }
        	}
        	if($diff){
        		 foreach ($diff as $dv){
        		 	 $rs = db('agent_bank_loan')->where("agent_id ='{$agent_id['id']}' and type = 1 and bank_loan_id ='{$dv}'")->delete();
        		 }
        	}
        }
        
        //贷款部分
        if(empty($loan_id)){
        	//删除关系表
        	 $rs = db('agent_bank_loan')->where("agent_id ='{$agent_id['id']}' and type = 2")->delete();
        }
        else
        {
        	$old = array();
        	$diff = array();
        	$rs = db('agent_bank_loan')->field("bank_loan_id")->where("agent_id ='{$agent_id['id']}' and type = 2")->select();
        	if($rs){
        		foreach ($rs as $ov){
        			$old[] = $ov['bank_loan_id'];
        		}
        	}
        	 
        	$arr = explode(",",$loan_id);
        	$diff = array_diff($old,$arr);
        	
        	foreach ($arr as $v){
        		//处理关系表
        		$has = model('agent_bank_loan')->where(array('agent_id'=>$agent_id['id'],'type'=>2,'bank_loan_id'=>$v))->find();
        		if(!$has){
        			$data = array();
        			$data['agent_id'] = $agent_id['id'];
        			$data['create_time'] = date("Y-m-d H:i:s",time());
        			$data['bank_loan_id'] =  $v;
        			$data['type'] =  2;
        			$list = model('agent_bank_loan')->insert($data);
        		}
        
        		//处理代理商报价单
        		$loan_price = db("loan_product_price")->alias("a")->field("a.rule_type,a.loan_product_id,a.level_price,a.level1_price,a.level2_price,a.level3_price,b.loan_id")
        		->join("loan_product b","a.loan_product_id = b.id")
        		->where("a.loan_product_id ='{$v}' and a.is_deleted = 0")
        		->select();
        		if($loan_price){
        			  $data = array();
        			  foreach ($loan_price as $val){
        			  	   $has = model('agent_sys_price')->where(array('agent_id'=>$agent_id['id'],'type'=>2,'fy_type'=>$val['rule_type'],'card_id'=>$v,'level'=>$agent_id['level']))->find();
        			  	   if(!$has){
	        			  	   	$data['level_price'] = $val['level_price'];
	        			  	   	$data['level1_price'] = $val['level1_price'];
	        			  	   	$data['level2_price'] = $val['level2_price'];
	        			  	   	$data['level3_price'] = $val['level3_price'];
	        			  	   	$data['type'] =  2;
	        			  	   	$data['bank_id'] =  $val['loan_id'];
	        			  	   	$data['card_id'] =  $val['loan_product_id'];
	        			  	   	$data['fy_type'] =  $val['rule_type'];
	        			  	   	$data['level'] =  $agent_id['level'];
	        			  	   	$data['agent_id'] = $agent_id['id'];
	        			  	   	$data['create_time'] = date("Y-m-d H:i:s",time());
	        			  	   	$list = model('agent_sys_price')->insert($data);
        			  	   }
        			  }
        		}
        		
        		//删除差异
        		if($diff){
        			foreach ($diff as $dv){
        				$rs = db('agent_bank_loan')->where("agent_id ='{$agent_id['id']}' and type = 2 and bank_loan_id ='{$dv}'")->delete();
        			}
        		}
        	}
        }
        
        
   
     
        
      
        
       // if(false !== $list || $del !== false || $diff_bankprice_update !== false || $diff_loanprice_update !== false)
      //  {
            $this->code = 1;
      //  }

        return ;
        }
        $row = model('agent_apply')->get($ids);
        $agent_id = model('agent')->where('phone',$row['phone'])->field('id')->find();
       
        //找出代理商可发展下家
        $num = db("agent_num")->where("agent_id = '{$agent_id['id']}'")->find();
        if(!$num){
        	$num = db("agent_num")->where("agent_id = '0'")->find();
        }
        $status = $this->request->get('status');
        $this->assign('status',$status);

        $where = array('is_deleted'=>0,'level_price'=>array('gt',0));
        $bank = db('bank')->where($where)->field('id,bank_logo,bank_url,bank_name')->select();
        
        $where2 = array('a.is_deleted'=>0,'b.level_price'=>array('gt',0));
        $loan = model('loan_product')->alias("a")
                     ->join("loan_product_price b","a.id = b.loan_product_id","INNER")
                     ->where($where2)->field('a.id,a.name,a.loan_id,a.product_logo,a.product_url,a.product_type')
                     ->group("a.id")->select();
       
        $agent_bank = db('agent_bank_loan')->where(array('agent_id' => $agent_id['id'],'type'=>1))->select();
        $agent_loan = model('agent_bank_loan')->where(array('agent_id' => $agent_id['id'],'type'=>2))->select();
        /* echo "<pre>";
        print_r($bank);
        echo "</pre>";
        exit; */
         foreach ($bank as $k=>$v)
         {
         	    $bank[$k]['selected'] = 0;
                 foreach ($agent_bank as $kk =>$vv)
                 {
                    if($v['id'] == $vv['bank_loan_id'])
                    {
                         $bank[$k]['selected'] = 1;
                    }
                }
         } 
      
        foreach ($loan as $a=>$b)
        {
            $loan[$a]['selected']=0;
            foreach ($agent_loan as $aa =>$bb)
            {
                if($b['id'] == $bb['bank_loan_id'])
                {
                    $loan[$a]['selected'] = 1;
                }
            }
        }

        $this->assign('bank',$bank);
        $this->assign('loan',$loan);
      
        if (!$row)
            $this->error(__('No Results were found'));
      
        $author = model('admin')->where('id', $row['operator'])->field('username')->find();
       if($row['status'] ==2)
       {
           $row['status'] ='审核通过';
       }else{
           $row['status'] ='审核失败';
       }

        $this->assign('id',$ids);
        $this->assign('num',$num);
        $this->assign('author',$author['username']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
        
    }


    
    /*
     * 编辑
     */
    public function edit($ids = NULL)
    {   
        $where = array('is_deleted'=>0,'level_price'=>array('gt',0));
        $bank = model('bank')->where($where)->field('id,bank_logo,bank_url,bank_name')->select();
       
        //$loan = model('loan_product')->where($where)->field('id,name,loan_id,product_logo,product_url,product_type')->select();
        $where2 = array('a.is_deleted'=>0,'b.level_price'=>array('gt',0));
        $loan = model('loan_product')->alias("a")->join("loan_product_price b","a.id = b.loan_product_id","INNER")->where($where2)->field('a.id,a.name,a.loan_id,a.product_logo,a.product_url,a.product_type')->group("a.id")->select();
       
        $this->assign('bank',$bank);
        $this->assign('loan',$loan);
        $this->assign('id',$ids);
        
        if(empty($ids))
        {
            $ids = $this->request->post("id");
        }
        
        $row = model('agent_apply')->get($ids); 
        $status = $this->request->get('status');
        $this->assign('status',$status);
        if (!$row)
            $this->error(__('No Results were found'));

        if ($this->request->isPost())
        {
            $time = date("Y-m-d H:i:s",time());
            $aid = $row->aid;
            $this->code = -1;
            $params['confirm_remark'] = $this->request->post("applymsg");
            $params['status'] = $this->request->post("check");
            $agent_id = $this->request->post("id");
            $card = $this->request->post("card");
            $loan= $this->request->post("loan");
        
            if($row['status'] == 2){
                $this->msg = '已通过审核';
                return ;
            }          
            if ($params)
            {
                $params['confirm_time'] = $time;
                 if(session('user_name')){
                     $operator = session('user_name');
                     $operator = model('admin')->where('username',$operator)->field('id')->find();
                }
                $params['operator'] = $operator['id'];
                if($params['status'] == 'yes')
                {                  
                    $params['status'] = 2;
                   
              //      $params['operator'] = 'Admin';
                    $result = $row->save($params);
                     
                    if ($result !== false)
                    {
                        $salt='';
                        for ($i = 1; $i <= 6; $i++)
                        {
                            $salt .= chr(rand(97, 122));
                        }
                        if (!empty($aid))
                        {
                            $data['amid'] = $aid;
                            $data['bkltype'] = 'A';
                        }
                        $data['phone'] = $row['phone'];
                        $data['name'] = $row['name'];
                        $data['operator'] = $params['operator'];
                        
                        $password =  $row['password']; 
                        if(!$password)
                        {
                            $data['password'] = md5('123456' . $salt);
                        }
                        else 
                        {
                            $password = base64_decode($password);   
                            $data['password'] = md5($password . $salt);
                        }
                    
                        $data['salt'] = $salt;                     
                        $data['create_time'] = $time;
                        $insert = model('agent')->create($data);

                        if(false !== $insert)
                        {
                            $this->code = 1;
                            if (!empty($aid))
                            {
                                $arg = array();
                                $arg['aid'] = $aid;
                                $arg['status'] = 2;
                                $arg['token'] = md5($aid.$arg['status']."bklTofuturn2017");
                                $result = bklChanggeStatus($arg);
                                $result = json_decode($result, true);
                                if ($result['code']!=1)
                                {
                                    $msg = isset($result['data']['msg']) ? $result['data']['msg'] : (isset($result['msg']) ? $result['msg'] : $result['message']);
                                    $this->error(__($msg));
                                }
                            }
                            
                            if(!empty($card))
                            {
                                $card = explode(',',$card);
                                $count = count($card);
                                $bank_data['agent_id'] = $insert['id'];
                                $bank_data['type'] = 1;
                                $bank_data['create_time'] = $time;
                               
                                foreach ($card as $v)
                                {
                                    $bank_data['bank_loan_id'] = $v;
                                    $agent_bank_loan  = model('agent_bank_loan')->insert($bank_data);
                                    
                                    $bank_table = model('bank')->where(array('id'=>$v,'is_deleted'=>0))->find();
                                    $agent_price_data['bank_id'] = $bank_table['id'];
                                    $agent_price_data['create_time'] = $time;
                                    $agent_price_data['agent_id'] =  $insert['id'];
                                    $agent_price_data['level_price'] = $bank_table['level_price']? $bank_table['level_price']:0.00;
                                    $agent_price_data['level1_price'] = $bank_table['level1_price']?$bank_table['level1_price']:0.00;
                                    $agent_price_data['level2_price'] = $bank_table['level2_price']?$bank_table['level2_price']:0.00;
                                    $agent_price_data['level3_price'] = $bank_table['level3_price']?$bank_table['level3_price']:0.00;
                                    $agent_price_data['type'] = 1;
                                    $agent_price_data['level'] = 0;

                                    $agent_sys_price  = model('agent_sys_price')->insert($agent_price_data);
                                }
                               
                            }
                            
                            if(!empty($loan))
                            { 
                                $loan = explode(',',$loan);
                                $count = count($loan);
                                $loan_data['agent_id'] =  $insert['id'];
                                $loan_data['type'] = 2;
                                $loan_data['create_time'] = $time;
                               
                                foreach ($loan as $v)
                                {
                                    $loan_data['bank_loan_id'] = $v;
                                    $agent_bank_loan  = model('agent_bank_loan')->insert($loan_data);
                                    
                                    
                                    $loan_product = model('loan_product')->where(array('id'=>$v,'is_deleted'=>0))->field('id,name,loan_id')->find(); 
                                    $agent_price_data['bank_id'] = $loan_product['loan_id'];
                                    $agent_price_data['card_id'] = $loan_product['id'];
                                    $loan_product_price = model('loan_product_price')->where(array('loan_product_id'=>$v,'is_deleted'=>0))->field('rule_type,level_price,level1_price,level2_price,level3_price')->select();

                                    if(!empty($loan_product_price))
                                    {
                                        foreach ($loan_product_price as $kk =>$vv)
                                        {
                                            $agent_price_data['agent_id'] =  $insert['id'];
                                            $agent_price_data['create_time'] = $time;
                                            $agent_price_data['level_price'] = $vv['level_price']? $vv['level_price']:0.00;
                                            $agent_price_data['level1_price'] = $vv['level1_price']?$vv['level1_price']:0.00;
                                            $agent_price_data['level2_price'] = $vv['level2_price']?$vv['level2_price']:0.00;
                                            $agent_price_data['level3_price'] = $vv['level3_price']?$vv['level3_price']:0.00;
                                            $agent_price_data['fy_type'] = $vv['rule_type'];
                                            $agent_price_data['type'] = 2;
                                            $agent_price_data['level'] = 0;
                                            
                                            $agent_sys_price  = model('agent_sys_price')->insert($agent_price_data);
                                            
                                        }
                                    }
                                }
                                 
                            }
                     

                            
                        }
                         
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
                    
                    if(false !== $result)
                    {
                        $this->code = 1;
                        if (!empty($aid))
                        {
                            $arg = array();
                            $arg['aid'] = $aid;
                            $arg['status'] = 3;
                            $arg['token'] = md5($aid.$arg['status']."bklTofuturn2017");
                            $result = bklChanggeStatus($arg);
                            $result = json_decode($result, true);
                            if ($result['code']!=1)
                            {
                                $msg = isset($result['data']['msg']) ? $result['data']['msg'] : (isset($result['msg']) ? $result['msg'] : $result['message']);
                                $this->error(__($msg));
                            }
                        }
                    }

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