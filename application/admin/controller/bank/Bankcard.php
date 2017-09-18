<?php

namespace app\admin\controller\bank;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Bankcard extends Backend
{

    protected $model = null;
    //当前登录管理员所有子节点组别
    protected $childrenIds = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('BankCard');
        // 取出所有银行分组
        $grouplist = db('Bank')->field('id,bank_name')->select();
        $bankdata = [];
        foreach ($grouplist as $k => $v)
        {
            $bankdata[0] ="请选择对应银行";
            $bankdata[$v['id']] = $v['bank_name'];
        }

        // 取出所有类型分组
        $typelist = db('bank_card_type')->field('id,name')->select();
        $typedata = [];
        foreach ($typelist as $k => $v)
        {
            $typedata[0] ="请选择对应类型";
            $typedata[$v['id']] = $v['name'];
        }

        $this->childrenIds = array_keys($bankdata);
        $this->view->assign('bankdata', $bankdata);
        $this->childrenIds = array_keys($typedata);
        $this->view->assign('typedata', $typedata);
    }

    /**
     * 查看
     */
    public function index()
    {
        //    ->field(['password', 'salt', 'token'], true)
        if ($this->request->isAjax())
        {

//            $childrenAdminIds = model('BankCard')
//                ->field('id')
//                ->where('bank_id', 'in', $this->childrenIds)
//                ->column('id');
//              var_dump($childrenAdminIds);exit;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list=$this->model
                ->alias('a')
                ->join('bank b','a.bank_id=b.id')
                ->join('bank_card_type c','a.card_type_id=c.id')
                ->field('a.id,a.card_name,a.card_logo,a.card_url,a.card_details,a.create_time,b.bank_name,c.name')
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
            $params = $this->request->post("row/a");
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
            $count = $this->model->where('id', 'in', $ids)->delete();
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
