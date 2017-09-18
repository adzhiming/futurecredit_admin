<?php

namespace app\admin\controller\wechat;

use app\common\controller\Backend;
use think\Controller;
use think\Request;

/**
 * 微信配置管理
 *
 * @icon fa fa-circle-o
 */
class Commissionwithdrawal extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('agent_withdraw_log');
    }

    
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);   //获取查询的字段
            //var_dump($filter);exit;
            $re_where='';
            if(!empty($filter['apply_time']))
            {
                list($start,$end) = explode(',', $filter['apply_time']);
                $re_where['apply_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['id'])) $re_where['a.id'] = array('like','%'.$filter['id'].'%');
            if(!empty($filter['name'])) $re_where['b.name'] = array('like','%'.$filter['name'].'%');
            if(!empty($filter['status'])) $re_where['a.status'] = $filter['status'];
            // if(!empty($filter['cardquantity'])) $re_where['phone'] = $filter['cardquantity'];
            if(!empty($filter['withdraw'])) $re_where['a.withdraw'] = $filter['withdraw'];
            // if(!empty($filter['income_loan'])) $re_where['income_loan'] = $filter['income_loan'];
            //  if(!empty($filter['level'])) $re_where['level'] = $filter['level'];
            
            //$re_where['status'] = 2;
            
            $total =  $this->model
            ->alias('a')
            ->join('agent b','a.agent_id = b.id','left')
            ->field('b.name,a.*')
            ->where($re_where)
            ->count();
        
           
            
            $list = $this->model
            ->alias('a')
            ->join('agent b','a.agent_id = b.id','left')
            ->field('b.name,a.*')
            ->order($sort, $order)
            ->where($re_where)
            ->limit($offset, $limit)
            ->select();
       // var_dump($list);exit;
            foreach ($list as $k => $v)
            {
            	switch ($v['status']){
            		case 1 :
            			$list[$k]['status_title'] = "审核中";
            		break;
            		case 2 :
            			if($v['pay_status'] ==3){
            				$list[$k]['status_title'] = "审核通过:已支付";
            			}
            			elseif($v['pay_status'] ==4){
            				$list[$k]['status_title'] = "审核通过:支付失败";
            			}
            			else{
            				$list[$k]['status_title'] = "审核通过:等待支付";
            			}
            		break;
            		case 3 :
            			$list[$k]['status_title'] = "拒绝";
            		break;
            		default:
            			break;
            	}
            	
/*                 if($v['status'] == 1)
                {
                    $v['status'] ='审核中';
                }
                elseif ($v['status'] == 2)
                {
                	if($v['pay_status'] ==3){
                		$v['status'] ='审核通过:已支付';
                	}
                	elseif($v['pay_status'] ==4){
                		$v['status'] ='审核通过:支付失败';
                	}
                	else{
                		$v['status'] ='审核通过:等待支付';
                	}
                }
                else 
                { 
                    $v['status'] ='审核失败';                    
                } */
            }
        
           // var_dump($list);exit; 
            $result = array("total" => $total, "rows" => $list);
        
            return json($result);
        }
        return $this->view->fetch();
    }
    
    
   

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
      
        $status = $this->request->get('status');
        $data['confirm_time'] = date("Y-m-d H:i:s");
        $data['id'] = $row['id'];
        
        if($status == 'yes')
        {
	        /* $data['status'] = 2;
	        $list =  $row->save($data); */
        	$out_trade_no = time().rand(100000,999999);
        	$url =  config("bkl_url_base").'/api.php/Withdraw/addWithdrawOrder';
        	$params = array();
        	$params['prj_id'] = 3;
        	$params['out_trade_no'] = $out_trade_no;
        	$params['desc'] = '代理商提现';
        	$params['order_amount'] = $row['withdraw']*100;
        	$params['attach'] = '';
        	$params['withdraw_type'] = 0;
        	$params['sign_t'] = time();
        	
        	$signData =array();
        	$signData['prj_id'] = $params['prj_id'];
		    $signData['out_trade_no'] = $params['out_trade_no'];
		    $signData['order_amount'] = $params['order_amount'];
		    $signData['sign_t'] = $params['sign_t'];
        	$key = getPrjKeyValue($params['prj_id'],'key','');
        	$sign=signData($key,$signData);
        	$params['sign'] =$sign;
        	
        	$url =config("bkl_url_base")."/api.php/Withdraw/addWithdrawOrder";   
        	  	
        	if(api_post_low($url, $params, $return_code, $return_msg, $return_data)){
        		  if($return_code){
        		  	    $save['order_sn'] = $return_data['order_sn'];
        		  	    $save['auto_key'] = $return_data['auto_key'];
        		  	    $save['tx_no'] = $return_data['out_trade_no'];
        		  	    $save['status'] = 2;
        		  	    $where["id"] =$ids;
        		  	   $list =  db("agent_withdraw_log")->where($where)->update($save);
        		  	   $msg ="申请成功";
        		  }
        		  else{
        		  	   $msg =$return_msg;
        		  }
        	}
           
        }
        else if($status == 'pay'){
        	  if($row['status'] ==2 && $row['pay_status'] !=3){
        	  	    //执行提现接口
	        	  	
	        	  	$params = array();
	        	  	$params['prj_id'] = 3;
	        	  	$params['out_trade_no'] =$row['tx_no'];
	        	  	$params['sign_t'] = time();
	        	  	
	        	  	$signData =array();
	        	  	$signData['prj_id'] = $params['prj_id'];
	        	  	$signData['out_trade_no'] = $params['out_trade_no'];
	        	  	$signData['sign_t'] = $params['sign_t'];
	        	  	
	        	  	$key = getPrjKeyValue($params['prj_id'],'key','');
	        	  	$sign=signData($key,$signData);	        	  	
	        	  	$params['sign'] =$sign; 
	        	  	//查询接口
	        	  	$url =  config("bkl_url_base").'/api.php/Withdraw/queryOrder';
	        	  	if(api_post_low($url, $params, $return_code, $return_msg, $return_data)){
	        	  		if($return_code ==1 && $return_data && $return_data['order_status'] ==3){
	        	  			    $where = array();
		        	  		  	$pay = array();
		        	  		  	$pay['confirm_time'] = date("Y-m-d H:i:s",time());
		        	  		  	$pay['operator'] = 2;
	        	  		  	    $pay['pay_status'] =$return_data['order_status'];
	        	  		  	    $pay['pay_type'] ='W';
	        	  		  	    $where["id"] =$ids;
	        	  		  	    $where['tx_no'] = $return_data['out_trade_no'];
	        	  		  	    
	        	  		  	    //更新agent表
	        	  		  	    $up = array();
	        	  		  	    $up['withdraw'] = array(
	        	  		  	    		'exp',
	        	  		  	    		'withdraw +'.$return_data['order_amount']/100
	        	  		  	    );
	        	  		  	    // 启动事务
	        	  		  	    db()->startTrans();
	        	  		  	    try{
	        	  		  	    	$list =  db("agent_withdraw_log")->where($where)->update($pay);
	        	  		  	    	$tb = db("agent")->where("id ='{$row['agent_id']}'")->update($up);
	        	  		  	    	// 提交事务
	        	  		  	    	db()->commit();
	        	  		  	    } catch (\Exception $e) {
	        	  		  	    	// 回滚事务
	        	  		  	    	db()->rollback();
	        	  		  	    }
	        	  		  	    echo "<script>alert('已提现成功');window.location.href='/wechat/commissionwithdrawal';</script>"; exit;
	        	  		}
	        	  	}
	        	  	//提现
	        	    $params['openid'] =$row['openid'];
	        	    $params['auto_key'] = $row['auto_key'];
	        	    $signData['openid'] = $params['openid'];
	        	    $signData['auto_key'] = $params['auto_key'];
	        	    $key = getPrjKeyValue($params['prj_id'],'key','');
	        	    $sign=signData($key,$signData);
	        	    $params['sign'] =$sign;
	        	  	$url =  config("bkl_url_base").'/api.php/Withdraw/withdraw';
	        	  	if(api_post_low($url, $params, $return_code, $return_msg, $return_data)){
	        	  		  if($return_code ==1 && $return_data && $return_data['order_status'] ==3){
		        	  		  	$where = array();
		        	  		  	$pay = array();
		        	  		  	$pay['confirm_time'] = date("Y-m-d H:i:s",time());
		        	  		  	$pay['operator'] = 2;
	        	  		  	    $pay['pay_status'] = $return_data['order_status'];
	        	  		  	    $pay['pay_type'] ='W';
	        	  		  	    $where["id"] =$ids;
	        	  		  	    $where['tx_no'] = $return_data['out_trade_no'];
	        	  		  	    
	        	  		  	    //更新agent表
	        	  		  	    $up = array();
	        	  		  	    $up['withdraw'] = array(
	        	  		  	    		'exp',
	        	  		  	    		'withdraw +'.$return_data['order_amount']/100
	        	  		  	    );
	        	  		  	    // 启动事务
	        	  		  	    db()->startTrans();
	        	  		  	    try{
	        	  		  	    	$list =  db("agent_withdraw_log")->where($where)->update($pay);
	        	  		  	    	$tb = db("agent")->where("id ='{$row['agent_id']}'")->update($up);
	        	  		  	    	// 提交事务
	        	  		  	    	db()->commit();
	        	  		  	    } catch (\Exception $e) {
	        	  		  	    	// 回滚事务
	        	  		  	    	db()->rollback();
	        	  		  	    }
	        	  		  	    $msg ="支付成功";
	        	  		  }
	        	  		  else{
	        	  		  	   $msg = $return_msg;
	        	  		   }
	        	  	}
	        	  	else{
	        	  		$msg ="支付失败-1";
	        	  	}
        	  	
        	  	     echo "<script>alert('{$msg}');window.location.href='/wechat/commissionwithdrawal';</script>"; exit;
        	  }
        	  else
        	  {
        	  	     echo "<script>alert('支付失败');window.location.href='/wechat/commissionwithdrawal';</script>"; exit;
        	  }
        }
         else if ($status == 'no')
        {
        	$data = array();
	        $data['status'] = 3;
	        $data['confirm_time'] = date("Y-m-d H:i:s",time()) ;
	        $list =  db("agent_withdraw_log")->where("id ='{$ids}'")->update($data);
        } 
        
         if(false !== $list)  echo "<script>alert('修改成功');window.location.href='/wechat/commissionwithdrawal';</script>"; exit;
        return $this->view->fetch();
    }

}
