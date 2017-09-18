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
class Banktype extends Backend
{

    protected $model = null;
    //当前登录管理员所有子节点组别
    protected $childrenIds = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BankCardType');
        // 取出所有银行分组
        $grouplist = db('Bank')->field('id,bank_name')->select();
        $bankdata = [];
        foreach ($grouplist as $k => $v)
        {
            $bankdata[0] ="请选择对应银行";
            $bankdata[$v['id']] = $v['bank_name'];
        }
        $this->childrenIds = array_keys($bankdata);
        $this->view->assign('bankdata', $bankdata);

    }

    /**
     * 查看
     */
    public function index()
    {
        //    ->field(['password', 'salt', 'token'], true)
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['name'])) $where['name'] = $filter['name'];

            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list=$this->model
                ->field('id,name,bank_id,remark,create_time,is_deleted')
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
            if ($count)
            {
                $this->code = 1;
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
