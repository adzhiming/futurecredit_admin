<?php

namespace app\admin\controller\strategy;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Strategylist extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('help_strategy');
      
    }

    /**
     * 查看
     */
    public function index()
    {
      

        if ($this->request->isAjax())
        {

            $filter = json_decode($_GET['filter'],true);
            $where = array('is_deleted'=>0,'status'=>0);
           
           

            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(isset($filter['type'])) $where['type'] = $filter['type'];
            if(!empty($filter['title'])) $where['title'] = array('like','%'.$filter['title'].'%');
           ;
            
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';


         //  list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->count();
            $list = $this->model              
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            foreach ($list as $k => &$v)
            {
                if($v['type'] == 0)
                {
                    $v['type'] = '办卡技巧';
                }
                else 
                {
                    $v['type'] = '贷款攻略';
                }
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
           // var_dump($params);exit;
            if ($params)
            {
                $params['apply_time']=date('Y-m-d H:i:s',time());
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
            $params['apply_time']=date('Y-m-d H:i:s',time());
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
