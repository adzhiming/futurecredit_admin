<?php

namespace app\admin\controller\client;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Loanproduct extends Backend
{

    protected $model = null;
    //当前登录管理员所有子节点组别
    protected $childrenIds = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('LoanProduct');
        // 取出所有贷款公司分组
        $grouplist = db('loan')->field('id,name')->select();
        $loandata = [];
        foreach ($grouplist as $k => $v)
        {
            $loandata[0] ="请选择对应贷款公司";
            $loandata[$v['id']] = $v['name'];
        }

        // 取出所有步骤信息
        $flowlist = db('loan_flow')->field('id,flow_name')->order("id",'asc')->select();
        $flowdata = [];
        foreach ($flowlist as $k => $v)
        {
            $flowdata[0] ="请选择对应步骤";
            $flowdata[$v['id']] = $v['flow_name'];
        }

        $this->childrenIds = array_keys($loandata);
        $this->view->assign('loandata', $loandata);
        $this->view->assign('flowdata', $flowdata);

    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax())
        {

            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $filter = json_decode($_GET['filter'],true);
            $where = array();
            if (!empty($filter))
            {
                foreach( $filter as $k=>$v)
                {
                    switch ($k) {
                        case 'id':
                            $k='a.id';
                            $where[$k] = $v;
                            break;
                        case 'name':
                            $k='a.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'loan_name':
                            $k='b.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'add_time':
                            $k='a.add_time';
                            $where[$k] = $v;
                            break;
                    }

                }
            }
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            if(!empty($filter['add_time']))
            {
                list($start,$end) = explode(',', $filter['add_time']);
                $where['a.add_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            
            if(!empty($filter['name'])) $where['name'] = array('like','%'.$filter['name'].'%');
            if(!empty($filter['phone'])) $where['phone'] = $filter['phone'];
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';
            $total = $this->model
                ->alias('a')
                ->join('loan b','a.loan_id=b.id')
                ->field('a.id,a.name,a.product_logo,a.product_url,a.product_type,a.product_details,a.add_time,a.product_style,a.apply_number,a.play_type,a.loan_range,a.loan_term,a.loan_flow,a.product_comment,a.product_datum,b.name as loan_name,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.is_deleted,a.unit_rate,a.interest_rate,a.repayment_cycle_range,a.interest_free_days')
                ->where($where)
                ->count();
            $list=$this->model
                ->alias('a')
                ->join('loan b','a.loan_id=b.id')
                ->field('a.id,a.name,a.product_logo,a.product_url,a.product_type,a.product_details,a.add_time,a.product_style,a.apply_number,a.play_type,a.loan_range,a.loan_term,a.loan_flow,a.product_comment,a.product_datum,b.name as loan_name,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.is_deleted,a.price_type,a.unit_rate,a.interest_rate,a.repayment_cycle_range,a.interest_free_days')
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
            $group = $this->request->post("group/a", [], 'strip_tags');

            $loan_term=$params['loan_term'];
            $repayment_cycle_range=$params['repayment_cycle_range'];
            if(!empty($loan_term)){
                $loan_frist= substr($loan_term,0,strpos($loan_term, '-'));
                $loan_last = trim(strrchr($loan_term, '-'),'-');
                preg_match_all('/\d+/',$loan_last,$arr);
                $last_one = join('',$arr[0]);
            }
            if(!empty($repayment_cycle_range)){
                $range_frist= substr($repayment_cycle_range,0,strpos($repayment_cycle_range, '-'));
                $range_last = trim(strrchr($repayment_cycle_range, '-'),'-');
            }
            if(!empty($loan_term) && !empty($repayment_cycle_range)){
                if((int)$range_frist < (int)$loan_frist){
                    $this->code =-1;
                    $this->msg ="周期范围最小数不能小于贷款期限最小数！";
                    exit;
                }

                if((int)$range_last > (int)$last_one){
                    $this->code =-1;
                    $this->msg ="周期范围最大数不能大于贷款期限最大数！";
                    exit;
                }

                if($params['unit_rate'] == 3 && !empty($params['interest_free_days'])){
                    if((int)$range_frist < (int)$params['interest_free_days']){
                        $this->code =-1;
                        $this->msg ="日期规则时最小周期数不能小于免息天数";
                        exit;
                    }
                }
            }


            $arr = array_diff($group, ["0"]);
            $groups=implode(',',$arr);
            if ($params) {
                $params['add_time'] = date('Y-m-d H:i:s', time());
                $params['loan_flow'] = $groups;
                $loan_add=$this->model->create($params);
                if( $loan_add){
                    $this->code = 1;
                }else{
                    $this->code = -1;
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
        $rows=db('loan_product')->where('id',$row['id'])->find();
        $loan=db('loan')->where('id',$rows['loan_id'])->field('name')->find();
        $rows['loan_name']=$loan['name'];

        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            $loan_term=$params['loan_term'];
            $repayment_cycle_range=$params['repayment_cycle_range'];
            if(!empty($loan_term)){
                $loan_frist= substr($loan_term,0,strpos($loan_term, '-'));
                $loan_last = trim(strrchr($loan_term, '-'),'-');
                preg_match_all('/\d+/',$loan_last,$arr);
                $last_one = join('',$arr[0]);
            }
            if(!empty($repayment_cycle_range)){
                $range_frist= substr($repayment_cycle_range,0,strpos($repayment_cycle_range, '-'));
                $range_last = trim(strrchr($repayment_cycle_range, '-'),'-');
            }
            if(!empty($loan_term) && !empty($repayment_cycle_range)){
                if((int)$range_frist < (int)$loan_frist){
                    $this->code =-1;
                    $this->msg ="周期范围最小数不能小于贷款期限最小数！";
                    exit;
                }

                if((int)$range_last > (int)$last_one){
                       $this->code =-1;
                       $this->msg ="周期范围最大数不能大于贷款期限最大数！";
                       exit;
                }

                if($params['unit_rate'] == 3 && !empty($params['interest_free_days'])){
                    if((int)$range_frist < (int)$params['interest_free_days']){
                        $this->code =-1;
                        $this->msg ="日期规则时最小周期数不能小于免息天数";
                        exit;
                    }
                }
            }


            if ($params) {
            //   var_dump($params);exit;
                $param=array(
                    'loan_id'=>$params['loan_id'],
                    'product_type'=>$params['product_type'],
                    'product_style'=>$params['product_style'],
                    'name'=>$params['name'],
                    'product_url'=>$params['product_url'],
                    'product_logo'=>$params['product_logo'],
                    'apply_number'=>$params['apply_number'],
                    'play_type'=>$params['play_type'],
                    'loan_range'=>$params['loan_range'],
                    'loan_term'=>$params['loan_term'],
                    'product_comment'=>$params['product_comment'],
                    'product_datum'=>$params['product_datum'],
                    'product_details'=>$params['product_details'],
                    'unit_rate'=>$params['unit_rate'],
                    'interest_rate'=>$params['interest_rate'],
                    'repayment_cycle_range'=>$params['repayment_cycle_range'],
                    'interest_free_days'=>$params['interest_free_days'],
                    'update_time'=>date('Y-m-d H:i:s',time()),
                );

                $group = $this->request->post("group/a", [], 'strip_tags');
                $arr = array_diff($group, ["0"]);
                $groups=implode(',',$arr);
                $params['loan_flow']=$groups;
                $params['update_time']=date('Y-m-d H:i:s',time());
                $loan_edit=$row->save($param);
                //添加代理默认值
                if( $loan_edit){
                    $this->code = 1;
                }else{
                    $this->code = -1;
                }

            }
            return;
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }
        $this->view->assign("row", $rows);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }


    /**
     * 详情
     */
    public function details($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);

        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params) {
                $group = $this->request->post("group/a", [], 'strip_tags');
                $groups=implode(',',$group);
                $params['loan_flow']=$groups;
                $params['add_time']=date('Y-m-d H:i:s',time());
                $row->save($params);
                $this->code = 1;

            }
            return;
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }
        $this->view->assign("row", $row);
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
             $loan_price_data['is_deleted']=1;
             $loan_price_check=db('loan_product_price')->where('loan_product_id',$row['id'])->where('is_deleted',0)->select();
             if(!empty($loan_price_check)){
                 $loan_price_del=db('loan_product_price')->where('loan_product_id',$row['id'])->update($loan_price_data);
                 if($count && $loan_price_del){
                     $this->code = 1;
                 }else{
                     $this->code = -1;
                     $this->msg ="删除失败";
                 }
             }else{
                 if ($count)
                 {
                     $this->code = 1;
                 }else{
                     $this->code = -1;
                     $this->msg ="删除失败";
                 }
             }

        }else{
            echo 1;
        }

        return;
    }


    //数据恢复recovery
    public function recovery($ids = "")
{
    $this->code = -1;
    if (!empty($ids))
    {
        $row = $this->model->get(['id' => $ids]);
        $params['is_deleted'] = 0;
        //先获取到上级机构是否删除
        $check_loan=db('loan')->where('id',$row['loan_id'])->where('is_deleted',0)->find();
        if(!empty($check_loan)){
            $count = $row->save($params);
            $data['is_deleted']=0;
            if ($count)
            {
                $this->code = 1;
            }
        }else{
            $this->code =-1;
            $this->msg="请先恢复贷款机构再进行当前恢复操作！";
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
        return;
    }

}
