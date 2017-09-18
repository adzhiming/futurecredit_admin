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
class Loan extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Loan');
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where = array();
            

            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);//var_dump($filter);exit;
            
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['name'])) $where['name'] = array('like','%'.$filter['name'].'%');
            if(!empty($filter['phone'])) $where['phone'] = array('like','%'.$filter['phone'].'%');
            
            
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
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
                $params['create_time']=date('Y-m-d H:i:s',time());
                $this->model->create($params);
                $this->code = 1;
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
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            $params['create_time']=date('Y-m-d H:i:s',time());
            if ($params)
            {
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
            $loan_product_data['is_deleted']=1;
            $loan_product_check=db('loan_product')->where('loan_id',$row['id'])->where('is_deleted',0)->find();
            if(!empty($loan_product_check)){
               $loan_product_del=db('loan_product')->where('loan_id',$row['id'])->update($loan_product_data);
               $loan_product_price_check=db('loan_product_price')->where('loan_product_id',$loan_product_check['id'])->where('is_deleted',0)->field('id')->select();
               if(!empty($loan_product_price_check)){
                   $loan_product_price_data['is_deleted']=1;
                   $loan_product_price_del=db('loan_product_price')->where('loan_product_id',$loan_product_check['id'])->update($loan_product_price_data);
                   if($count && $loan_product_del && $loan_product_price_del){
                       $this->code = 1;
                   }else{
                       $this->code = -1;
                       $this->msg ="删除失败";
                   }
               }else{
                   if($count && $loan_product_del){
                       $this->code = 1;
                   }else{
                       $this->code = -1;
                       $this->msg ="删除失败";
                   }
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

}
