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
class Loanproductprice extends Backend
{

    protected $model = null;
    //当前登录管理员所有子节点组别
    protected $childrenIds = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('LoanProductPrice');
        // 取出所有贷款公司分组
        $grouplist = db('loan_product')->field('id,name')->where('is_deleted',0)->select();
        $loandata = [];
        foreach ($grouplist as $k => $v)
        {
            $loandata[0] ="请选择对应贷款业务";
            $loandata[$v['id']] = $v['name'];
        }

        $this->childrenIds = array_keys($loandata);
        $this->view->assign('loandata', $loandata);


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
                        case 'loan_product_name':
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
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 15;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'loan_product_id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';
            $total = $this->model
                ->alias('a')
                ->join('loan_product b','a.loan_product_id=b.id')
                ->field('a.id,a.loan_product_id,a.rule_type,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.remark,a.is_deleted,b.name as loan_product_name,a.create_time,a.update_time')
                ->where($where)
                ->count();

            //获取贷款业务
            $list=db('loan_product_price')
                ->alias('a')
                ->join('loan_product b','a.loan_product_id=b.id')
                ->field('a.id,a.loan_product_id,a.rule_type,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.remark,a.is_deleted,b.name as loan_product_name,a.create_time,a.update_time')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $arr=array();
            foreach ($list as $k =>$v){
              $type='未知';
              switch ($v['rule_type']){
                  case 1:
                      $type="CPA";
                      break;
                  case 2:
                      $type="CPS";
                      break;

              }
              $remark=mb_substr($v['remark'],0,18,'utf-8');
              if(!empty($remark)){
                  $remarks=$remark;
              }else{
                  $remarks="";
              }
                $arr[]=array(
                  'id'=>$v['id'],
                  'loan_product_id'=>$v['loan_product_id'],
                  'rule_type'=>$v['rule_type'],
                  'level_price'=>$v['level_price'],
                  'level1_price'=>$v['level1_price'],
                  'level2_price'=>$v['level2_price'],
                  'level3_price'=>$v['level3_price'],
                  'remark'=>(string)$remarks,
                  'is_deleted'=>$v['is_deleted'],
                 'loan_product_name'=>$v['loan_product_name'].'('.$type.')',
                  'create_time'=>$v['create_time'],
                  'update_time'=>$v['update_time'],
                );
            }
            $result = array("total" => $total, "rows" => $arr);
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
            var_dump($params);exit;
            if ($params) {
                $check=db('loan_product_price')->where('loan_product_id',$params['loan_product_id'])->where('rule_type',$params['rule_type'])->find();
                if(empty($check)){

                    if(empty($params['loan_product_id'])){
                        $this->code = -1;
                        $this->msg ="贷款业务不能为空！";
                        die;
                    }

                    if($params['level1_price']>$params['level_price'] || $params['level1_price']>$params['level_price'] ){
                        $this->code = -1;
                        $this->msg ="一级代理不能大于特级代理";
                        die;
                    }

                    if($params['level_price'] == $params['level1_price'] || $params['level1_price']>$params['level_price'] ){
                        $this->code = -1;
                        $this->msg ="一级代理不能大于特级代理";
                        die;
                    }


                    if($params['level2_price']>$params['level1_price'] || $params['level2_price']>$params['level1_price'] ){
                        $this->code = -1;
                        $this->msg ="二级代理不能大于一级代理";
                        die;
                    }

                    if($params['level3_price']>$params['level2_price'] || $params['level2_price']>$params['level2_price'] ){
                        $this->code = -1;
                        $this->msg ="三级代理不能大于二级代理";
                        die;
                    }

                    $params['create_time']=date('Y-m-d H:i:s',time());
                    $bank_card=  $this->model->create($params);
                    $loan_productId= db('loan_product_price')->getLastInsID();
                    //添加代理默认值


                    if($bank_card){
                        //添加代理默认值
                        $loan_id=db('loan_product')->field('loan_id')->where('id',$params['loan_product_id'])->find();
                        $agents_sys_price=array(
                            'type'=>2,
                            'bank_id'=>$loan_id['loan_id'],
                            'product_price_id'=>$loan_productId,
                            'card_id'=>$loan_id['loan_id'],
                            'fy_type'=>$params['rule_type'],
                            'agent_id'=>-1,
                            'level'=>-1,
                            'level_price'=>$params['level_price'],
                            'level1_price'=>$params['level1_price'],
                            'level2_price'=>$params['level2_price'],
                            'level3_price'=>$params['level3_price'],
                            'update_time'=>date('Y-m-d H:i:s',time()),

                        );
                        if(isset($loan_productId) && !empty($loan_productId)){
                            //添加代理关系
                            $check=db('agent_sys_price')
                                ->where('type',2)
                                ->where('product_price_id',$loan_productId)
                                ->where('agent_id',-1)
                                ->where('level',-1)
                                ->select();


                            if(!empty($check)){
                                $agents_edit2=db('agent_sys_price')
                                    ->where('type',2)
                                    ->where('product_price_id',$loan_productId)
                                    ->where('agent_id',-1)
                                    ->where('level',-1)
                                    ->update($agents_sys_price);
                            }
                            else{

                                $agents_sys_price['create_time']=date('Y-m-d H:i:s',time());
                                $agents_edit2=db('agent_sys_price')->insert($agents_sys_price);
                                //添加日志表
                                $agents_sys_price_log['type']=2;
                                $agents_sys_price_log['fy_type']=$params['rule_type'];
                                $agents_sys_price_log['op_type']=1;
                                $agents_sys_price_log['bank_id']=$loan_id['loan_id'];
                                $agents_sys_price_log['product_price_id']=$loan_productId;
                                $agents_sys_price_log['card_id']=$params['loan_product_id'];
                                $agents_sys_price_log['agent_id']=-1;
                                $agents_sys_price_log['level']=-1;
                                $agents_sys_price_log['level_price']=$params['level_price'];
                                $agents_sys_price_log['level1_price']=$params['level1_price'];
                                $agents_sys_price_log['level2_price']=$params['level2_price'];
                                $agents_sys_price_log['level3_price']=$params['level3_price'];
                                $agents_sys_price_log['create_time']=date('Y-m-d H:i:s',time());
                                db('agent_sys_price_log')->insert($agents_sys_price_log);
                            }

                            if($agents_edit2){
                                $this->code = 1;
                            }else{
                                $this->code = -1;
                            }
                        }else{
                            $this->code = -1;
                            $this->msg ="数据添加失败";
                        }

                    }

                }else{
                    $this->code = -1;
                    $this->msg = "该规则已添加，请修改";
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
        $rows=db('loan_product_price')->where('id',$row['id'])->find();
        $bank=db('loan_product')->where('id',$rows['loan_product_id'])->field('id,name,loan_id')->find();
        $rows['loan_name']=$bank['name'];

        if (!$rows)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {

            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');

            if(empty($params['level_price']) || empty($params['level1_price'])  || empty($params['level2_price'])  || empty($params['level3_price']) ){
                $this->code = -1;
                $this->msg ="代理佣金不能为空";
                exit;
            }

            if(floatval($params['level1_price']) > floatval($params['level_price']) ){
                $this->code = -1;
                $this->msg ="一级代理不能大于特级代理";
                exit;
            }

            if(floatval($params['level1_price']) == floatval($params['level_price']) ){
                $this->code = -1;
                $this->msg ="一级代理不能等于特级代理";
                exit;
            }

            if(floatval($params['level2_price']) > floatval($params['level1_price']) ){
                $this->code = -1;
                $this->msg ="二级代理不能大于一级代理";
                exit;
            }

            if(floatval($params['level2_price']) == floatval($params['level1_price']) ){
                $this->code = -1;
                $this->msg ="二级代理不能等于一级代理";
                exit;
            }

            if(floatval($params['level3_price']) > floatval($params['level2_price']) ){
                $this->code = -1;
                $this->msg ="三级代理不能大于二级代理";
                exit;
            }

            if(floatval($params['level3_price']) == floatval($params['level2_price']) ){
                $this->code = -1;
                $this->msg ="三级代理不能等于二级代理";
                exit;
            }
            $check=db('loan_product_price')->where('id',$row['id'])->where('is_deleted',1)->find();
            if(empty($check)){
                if ($params)
                {
                    $param=array(
                        'level_price'=>$params['level_price'],
                        'level1_price'=>$params['level1_price'],
                        'level2_price'=>$params['level2_price'],
                        'level3_price'=>$params['level3_price'],
                        'remark'=>$params['remark'],
                        'update_time'=>date('Y-m-d H:i:s',time()),
                    );
                    $edit= $row->save($param);


                    if($edit){
                        $agents_sys_price=array(
                            'level_price'=>$params['level_price'],
                            'level1_price'=>$params['level1_price'],
                            'level2_price'=>$params['level2_price'],
                            'level3_price'=>$params['level3_price'],
                            'update_time'=>date('Y-m-d H:i:s',time()),

                        );

                        $check=db('agent_sys_price')
                            ->where('type',2)
                            ->where('product_price_id',$row['id'])
                            ->where('agent_id',-1)
                            ->where('level',-1)
                            ->select();

                        if(!empty($check)){
                            //修改对应bank_id（当前表id）
                            $agents_edit2=db('agent_sys_price')
                                ->where('type',2)
                                ->where('product_price_id',$row['id'])
                                ->where('agent_id',-1)
                                ->where('level',-1)
                                ->update($agents_sys_price);
                        }
                        else{
                            $agents_sys_prices['type']=2;
                            $agents_sys_prices['fy_type']=$rows['rule_type'];
                            $agents_sys_prices['bank_id']=$bank['loan_id'];
                            $agents_sys_prices['product_price_id']=$row['id'];
                            $agents_sys_prices['card_id']=$bank['id'];
                            $agents_sys_prices['agent_id']=-1;
                            $agents_sys_prices['level']=-1;
                            $agents_sys_prices['level_price']=$params['level_price'];
                            $agents_sys_prices['level1_price']=$params['level1_price'];
                            $agents_sys_prices['level2_price']=$params['level2_price'];
                            $agents_sys_prices['level3_price']=$params['level3_price'];
                            $agents_sys_prices['create_time']=date('Y-m-d H:i:s',time());
                            $agents_edit2=db('agent_sys_price')->insert($agents_sys_prices);

                        }


                        $log_check=db('agent_sys_price_log')
                            ->where('type',2)
                            ->where('product_price_id',$row['id'])
                            ->where('agent_id',-1)
                            ->where('level',-1)
                            ->select();
                        if(!empty($log_check)){
                            $agents_sys_price_log['level_price']=$params['level_price'];
                            $agents_sys_price_log['level1_price']=$params['level1_price'];
                            $agents_sys_price_log['level2_price']=$params['level2_price'];
                            $agents_sys_price_log['level3_price']=$params['level3_price'];
                            db('agent_sys_price_log')
                                ->where('type',2)
                                ->where('product_price_id',$row['id'])
                                ->where('agent_id',-1)
                                ->where('level',-1)
                                ->update($agents_sys_price_log);
                        }
                        else{
                            //添加日志表
                            $agents_sys_price_log['type']=2;
                            $agents_sys_price_log['fy_type']=$rows['rule_type'];
                            $agents_sys_prices['bank_id']=$bank['loan_id'];
                            $agents_sys_prices['product_price_id']=$row['id'];
                            $agents_sys_price_log['card_id']=$bank['id'];
                            $agents_sys_price_log['agent_id']=-1;
                            $agents_sys_price_log['level']=-1;
                            $agents_sys_price_log['level_price']=$params['level_price'];
                            $agents_sys_price_log['level1_price']=$params['level1_price'];
                            $agents_sys_price_log['level2_price']=$params['level2_price'];
                            $agents_sys_price_log['level3_price']=$params['level3_price'];
                            $agents_sys_price_log['op_type']=1;
                            $agents_sys_price_log['create_time']=date('Y-m-d H:i:s',time());
                            db('agent_sys_price_log')->insert($agents_sys_price_log);

                        }



                        $check_all=db('agent_sys_price')
                            ->where('card_id',$bank['id'])
                            ->where('agent_id',0)
                            ->where('level',0)
                            ->select();
                        if(!empty($check_all)){
                            $upload_list['level_price']=$params['level_price'];
                            $upload_list['level_prify_typece']=$rows['rule_type'];
                            db('agent_sys_price')->where('card_id',$bank['id'])->update($upload_list);
                        }
                        if($agents_edit2){
                            $this->code = 1;
                        }else{
                            $this->code = -1;
                        }
                    }else{
                        $this->code=-1;
                        $this->msg="数据修改失败";
                    }
                }
            }else{
                $this->code = -1;
                $this->msg = '删除状态下不可修改';
            }
            return;
        }
        $grouplist = $this->auth->getGroups($rows['id']);
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
        $rows=db('loan_product_price')->where('id',$row['id'])->find();
        $bank=db('loan_product')->where('id',$rows['loan_product_id'])->field('name')->find();
        $rows['loan_name']=$bank['name'];
        if (!$rows)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {

            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params)
            {
                $param=array(
                    'level_price'=>$params['level_price'],
                    'level1_price'=>$params['level1_price'],
                    'level2_price'=>$params['level2_price'],
                    'level3_price'=>$params['level3_price'],
                    'remark'=>$params['remark'],
                    'update_time'=>date('Y-m-d H:i:s',time()),
                );
                $row->save($param);
                $this->code = 1;
            }
            return;
        }
        $grouplist = $this->auth->getGroups($rows['id']);
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
            echo -1;
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

        $loan_product_list=db('loan_product')->where('id',$row['loan_product_id'])->field('id,loan_id')->find();

        $loan_check=db('loan')->where('id',$loan_product_list['loan_id'])->where('is_deleted',0)->find();
        $loan_product_check=db('loan_product')->where('id',$row['loan_product_id'])->where('is_deleted',0)->find();

        if(!empty($loan_check) && !empty($loan_product_check)){
            $count = $row->save($params);
            if ($count)
            {
                $this->code = 1;
            }
        }elseif (empty($loan_check) && empty($loan_product_check)){
            $this->code=-1;
            $this->msg="请先恢复对应贷款机构和贷款业务,再进行该操作！";

        }elseif (empty($loan_check) && !empty($loan_product_check)){
            $this->code=-1;
            $this->msg="请先恢复对应贷款机构,再进行该操作！";
        }elseif (!empty($loan_check) && empty($loan_product_check)){
            $this->code=-1;
            $this->msg="请先恢复对应贷款业务,再进行该操作！";
        }

    }else{
        echo -1;
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
