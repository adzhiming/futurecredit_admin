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
class Useraccesslog extends Backend
{

    protected $model = null;
    //当前登录管理员所有子节点组别
    protected $childrenIds = [];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserAccessLog');
        // 取出所有代理分组
        $agentlist = db('agent')->field('id,name')->where('status',1)->select();
        $agentdata = [];
        foreach ($agentlist as $k => $v)
        {
            $agentdata[0] ="请选择对应代理商";
            $agentdata[$v['id']] = $v['name'];
        }

        // 取出所有用户信息
        $userlist = db('user')->field('id,user_name,phone')->order("id",'asc')->select();
        $userdata = [];
        foreach ($userlist as $k => $v)
        {
            $userdata[0] ="请选择用户";
            $userdata[$v['id']] = $v['phone'].'/'.$v['user_name'];
        }

        // 取出所有银行卡信息
        $cardlist = db('bank_card')->field('id,card_name')->order("id",'asc')->select();
        $carddata = [];
        foreach ($cardlist as $k => $v)
        {
            $carddata[0] ="请选择信用卡";
            $carddata[$v['id']] = $v['card_name'];
        }

        // 取出所有贷款业务信息
        $loanlist = db('loan_product')->field('id,name')->order("id",'asc')->select();
        $loandata = [];
        foreach ($loanlist as $k => $v)
        {
            $loandata[0] ="请选择业务";
            $loandata[$v['id']] = $v['name'];
        }

        $this->childrenIds = array_keys($agentdata);
        $this->view->assign('agentdata', $agentdata);
        $this->view->assign('userdata', $userdata);
        $this->view->assign('carddata', $carddata);
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
                        case 'agent_name':
                            $k='b.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'user_phone':
                            $k='c.phone';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'card_name':
                            $k='d.card_name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'loan_name':
                            $k='e.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;

                        case 'create_time':
                            $k='a.create_time';
                            $where[$k] = $v;
                            break;
                    }

                }
            }
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';


            $total = $this->model
                ->alias('a')
                ->join('agent b','a.agent_id=b.id','left')
                ->join('user c','a.user_id=c.id','left')
                ->join('bank_card d','a.bank_id=d.id','left')
                ->join('loan_product e','a.loan_id=e.id','left')
                ->field('a.id,b.name as agent_name,c.phone as user_phone,d.card_name,e.name as loan_name,a.type,a.remark,a.login_ip,a.user_name,a.create_time')
                ->where($where)
                ->count();
            $list=$this->model
                ->alias('a')
                ->join('agent b','a.agent_id=b.id','left')
                ->join('user c','a.user_id=c.id','left')
                ->join('bank_card d','a.bank_id=d.id','left')
                ->join('loan_product e','a.loan_id=e.id','left')
                ->field('a.id,b.name as agent_name,c.phone as user_phone,d.card_name,e.name as loan_name,a.type,a.remark,a.login_ip,a.user_name,a.create_time')
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
