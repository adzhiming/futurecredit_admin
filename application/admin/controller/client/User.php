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
class User extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $filter = $this->request->get("filter", '');
            $filter = json_decode($filter, TRUE);//var_dump($filter);exit;
            $re_where = array();
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $re_where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['user_name'])) $re_where['user_name'] = $filter['user_name'];
            if(!empty($filter['phone'])) $re_where['phone'] = array('like','%'.$filter['phone'].'%');
            
           
            
            $total = $this->model
                ->where($re_where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($re_where)
                ->order('id', 'asc')
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k=>&$v)
            {
                $v['total_loan'] = floor($v['total_loan']);
            }
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
          //  $params = $this->request->post("row/a");
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
