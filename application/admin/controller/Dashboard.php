<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Model;

/**
 * 首页概括
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    public $model;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('agent');
    }

    /**
     * 查看
     */
    public function index()
    {
       // $start_time = date('Y-m-d',strtotime("-8 day")); 
       // $end_time = date('Y-m-d',strtotime("-1 day"));//echo $start_time.",".$end_time;exit;
        
      //  if ($start_time) $start_time = $start_time." 00:00:00";//var_dump($date);exit;
       // if ($end_time) $end_time = $end_time." 23:59:59";
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $cradData = $this->cradDate('week');     // 信用卡
        
        $loanData = $this->loanDate('week');    //贷款
        if ($this->request->isAjax())
        {
         $date = $this->request->post('date');
         
         $start = $this->request->post('start');
         $end = $this->request->post('end');
         
         if ($start) $start = $start." 00:00:00";
         if ($end) $end = $end." 23:59:59";
         
         $ajax_where = array(
             'a.confirm_time'  => array('between',array($start,$end)),
             'status' => 2
         );
      
         $Card ='';
         $loan = '';
         if($start && $end)
         {
             $Card =  model('bank_card_log')
             ->field('b.card_name,c.*,count(a.agent_id) as total,a.confirm_time')
             ->alias('a')
             ->join('bank_card b','a.card_id = b.id','left')
             ->join('bank c','b.bank_id = c.id','left')
             ->group('a.confirm_time')
             ->where($ajax_where)
             ->limit($limit,10)
             ->select();
            
             $loan =  model('loan_log')
             ->field('b.name,a.confirm_time,sum(a.loan_price) as totalmoney')
             ->alias('a')
             ->join('crd_loan b','a.loan_product_id = b.id','left')
             ->group('a.confirm_time')
             ->where($ajax_where)
             ->limit($limit,10)
             ->select();
          
         }
       
      
           // echo $date ;exit;
            
          switch ($date)
          {
              case 'week': 
                  $cradData = $this->cradDate($date);     // 信用卡                  
                  $loanData = $this->loanDate($date);    //贷款
                  break;
              case 'month':
                  $cradData = $this->cradDate($date);     // 信用卡
                  $loanData = $this->loanDate($date);    //贷款
                  break;
              case 'years':
                  $cradData = $this->cradDate($date);     // 信用卡
                  $loanData = $this->loanDate($date);    //贷款
                  break;
                  
          }
          if(!$cradData) $cradData ='';
          if(!$loanData) $loanData ='';

          $dataList = array(
              'cradData' => $cradData,
              'loanData'=> $loanData,
              'creditCard' => $Card,
              'loan' =>$loan
          );
          
          $this->code = 0;
          $this->data = $dataList;
         // var_dump(json_encode($dataList));exit;
         
          return; //json($dataList);
        }
        
        
      
        $agentTotal = $this->model->field('id')->where('status',1)->select();
        $userAccess = model('user_access_log')->field('id')->order($sort,$order)->find();
        $userTotal = model('user')->field('id')->select();
        $withdrawOrder = model('agent_withdraw_log')->field('id,agent_id,withdraw,status')->where("status = 1 or status = 2")->select();
        $apply = [];
        $success= [];
     
        foreach ($withdrawOrder as $k => $v)
        {
            if($v['status'] == 1){
                $apply[] = $v['withdraw'];
              
           
            }else{
                $success[] = $v['withdraw'];
            }
        }
        
        $successOrderSum = count($success);
        $successOrderTotal = array_sum($success);
        
        $applyOrderSum = count($apply);
        $applyOrderTotal = array_sum($apply);
        
        $bankCardTotal = model('bank_card')->field('id')->where("is_deleted",0)->select();
        $dredgeCard = model('bank_card_log')->field('id')->where("status",2)->select();
        $loan = model('loan')->field('id')->where("is_deleted",0)->select(); 
      //$loan_log = model('loan_log')->feild("id")->where("status",2)->select();
        $totalLoanAmount =  model('loan_log')->query("select sum(loan_price) as loan_price from crd_loan_log where status =2");
        
        $creditCard =  model('bank_card_log')->field('b.card_name,c.*,count(a.agent_id) as total,a.confirm_time')->alias('a')->join('bank_card b','a.card_id = b.id','left')->join('bank c','b.bank_id = c.id','left')->group('a.confirm_time')->where("status",2)->limit($limit,1)->select();
        $loandata =  model('loan_log')->field('b.name,a.confirm_time,sum(a.loan_price) as totalmoney')->alias('a')->join('crd_loan b','a.loan_product_id = b.id','left')->group("a.confirm_time")->where("a.status",2)->limit($limit,10)->select();
        
      // var_dump($loandata);exit;
        
        //var_dump($cradDate);exit;

        $this->view->assign([
            'agenttotal'          => $agentTotal ? count($agentTotal) : 0,
            'userAccess'         => $userAccess ? count($userAccess) : 0,
            'userTotal'         => $userTotal ?  count($userTotal) : 0,
            'successOrderSum'   => $successOrderSum ? $successOrderSum : 0,
            'successOrderTotal'     => $successOrderTotal ? $successOrderTotal : 0,
            'applyOrderSum'    => $applyOrderSum ? $applyOrderSum : 0,
            'applyOrderTotal'         => $applyOrderTotal ? $applyOrderTotal : 0,
            'bankCardTotal' => $bankCardTotal ? count($bankCardTotal) : 0,
            'dredgeCard'           => $dredgeCard ? count($dredgeCard) : 0,
            'loan'           => $loan ? count($loan) : 0,
            'totalLoanAmount' =>$totalLoanAmount[0]['loan_price'],
            'creditCard'  =>$creditCard,
            'loandata' => $loandata,
            'cradData' => json_encode($cradData),
            'loanData'=> json_encode($loanData),
        ]);
//var_dump(json_encode($cradDate));exit;

        return $this->view->fetch();
    }
    
    public function loanDate($date)
    {
        switch ($date) {
            case "week":
                $StartDate = date("Y-m-d", strtotime("-7 day"));
                for ($i = 1; $i <= 7; $i ++) {
                    $time = date('Y-m-d', strtotime($StartDate . ' +' . $i . ' day'));
                    $dateList[] = $time;
        
        
                    if($time) $start_date = $time." 00:00:00";  $end_date = $time." 23:59:59";
        
                    $arr = array(
                        'confirm_time' => array('between',array($start_date,$end_date)),
                        'status' => 2
                    );
                    $loanmoney =  model('loan_log')->field('sum(loan_price) as money')->where($arr)->find();//var_dump($loanmoney);exit;
                    $dataList[] =  (int)$loanmoney['money'] ?  (int)$loanmoney['money']:0;
        
        
                }
                break;
            case "month":
                $StartDate = date("Y-m-d", strtotime("-12 month"));
                for ($i = 1; $i <= 12; $i ++) {
                    $time = date('Y-m', strtotime($StartDate . ' +' . $i . ' month'));
        
                    $dateList[] = $time;
                   
                    $arr = array(
                        'confirm_time' => array('like','%'. $time .'%'),
                        'status' => 2
                    );
                    $loanmoney =  model('loan_log')->field('sum(loan_price) as money')->where($arr)->find();
                    $dataList[] = (int)$loanmoney['money'] ?  (int)$loanmoney['money']:0;
        
                }
                break;
            case "years":
                $StartDate = date("Y-m-d", strtotime("-6 year"));
                for ($i = 1; $i <= 6; $i ++) {
                    $time = date('Y', strtotime($StartDate . ' +' . $i . ' year'));
                    $dateList[] = $time;
          
                    $arr = array(
                        'confirm_time' => array('like','%'. $time .'%'),
                        'status' => 2
                    );
                    $loanmoney =  model('loan_log')->field('sum(loan_price) as money')->where($arr)->find();
                    $dataList[] = (int)$loanmoney['money'] ?  (int)$loanmoney['money']:0;
                    
                     
                }
                break;
        }
     //   var_dump($dataList);exit;
        
        $array = array(
            'dataList' => $dataList,
            'dateList' => $dateList
        );
        
        return $array;
    }
    
    public function cradDate($date)
    {
        switch ($date) {
            case "week":
                $StartDate = date("Y-m-d", strtotime("-7 day"));
                for ($i = 1; $i <= 7; $i ++) {
                    $time = date('Y-m-d', strtotime($StartDate . ' +' . $i . ' day'));
                    $dateList[] = $time;
                
                    
                    if($time) $start_date = $time." 00:00:00";  $end_date = $time." 23:59:59";
                    
                    $arr = array(
                        'confirm_time' => array('between',array($start_date,$end_date)),
                        'status' => 2
                    );
                    $creditCard =  model('bank_card_log')->field('count(id) as count')->where($arr)->find();
                    $dataList[] =  $creditCard['count'];
                    
                  
                }
                break;
            case "month":
                $StartDate = date("Y-m-d", strtotime("-12 month"));
                for ($i = 1; $i <= 12; $i ++) {
                    $time = date('Y-m', strtotime($StartDate . ' +' . $i . ' month'));
        
                    $dateList[] = $time;
                    
                     $arr = array(
                        'confirm_time' => array('like','%'. $time .'%'),
                        'status' => 2
                    );
                    $creditCard =  model('bank_card_log')->field('count(id) as count')->where($arr)->find();
                    $dataList[] =  $creditCard['count'];
                  
                }
                break;
            case "years":
                $StartDate = date("Y-m-d", strtotime("-6 year"));
                for ($i = 1; $i <= 6; $i ++) {
                    $time = date('Y', strtotime($StartDate . ' +' . $i . ' year'));
                    $dateList[] = $time;
                        $arr = array(
                        'confirm_time' => array('like','%'. $time .'%'),
                        'status' => 2
                    );
                    $creditCard =  model('bank_card_log')->field('count(id) as count')->where($arr)->find();
                    $dataList[] =  $creditCard['count'];
        
                 
                }
                break;
        }
      
        //var_dump($dataList);exit;
        $array = array(
            'dataList' => $dataList,
            'dateList' => $dateList
        );
        
        return $array;
    }

}
