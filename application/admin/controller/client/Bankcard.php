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

            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();


            $filter = json_decode($_GET['filter'],true);//var_dump($filter);exit;
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
                        case 'card_name':
                            $k='a.card_name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'bank_name':
                            $k='b.bank_name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'name':
                            $k='c.name';
                            $where[$k] = ['like', '%'.$v.'%'];
                            break;
                        case 'card_url':
                            $k='a.card_url';
                            $where[$k] = $v;
                            break;
                        case 'create_time':
                            $k='a.create_time';
                            $where[$k] = $v;
                            break;
                    }

                }
            }
            
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $where['a.create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }

            if(!empty($filter['bank_name'])) $where['bank_name'] = array('like','%'.$filter['bank_name'].'%');
            if(!empty($filter['card_name'])) $where['card_name'] = array('like','%'.$filter['card_name'].'%');
            
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'desc';


            $total = $this->model
                ->alias('a')
                ->join('bank b','a.bank_id=b.id')
                ->join('bank_card_type c','a.card_type_id=c.id')
                ->field('a.id,a.card_name,a.card_logo,a.card_url,a.card_details,a.create_time,b.bank_name,c.name,a.apply_number,a.follow_number,a.displayorder,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.is_deleted,a.price_type,a.is_recommend,a.is_hot')
                ->where($where)
                ->count();
            $list=$this->model
                ->alias('a')
                ->join('bank b','a.bank_id=b.id')
                ->join('bank_card_type c','a.card_type_id=c.id')
                ->field('a.id,a.card_name,a.card_logo,a.card_url,a.card_details,a.create_time,b.bank_name,c.name,a.apply_number,a.follow_number,a.displayorder,a.level_price,a.level1_price,a.level2_price,a.level3_price,a.is_deleted,a.price_type,a.is_recommend,a.is_hot')
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
                $bank_card=  $this->model->create($params);
                //添加代理默认值
                if($bank_card){
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
        $rows=db('bank_card')->where('id',$row['id'])->find();
     //   var_dump($rows);exit;
        $bank=db('bank')->where('id',$rows['bank_id'])->field('bank_name')->find();
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            if ($params)
            {
                $param=array(
                   'displayorder'=>$params['displayorder'],
                   'bank_id'=>$params['bank_id'],
                   'card_type_id'=>$params['card_type_id'],
                   'card_name'=>$params['card_name'],
                   'apply_number'=>$params['apply_number'],
                   'follow_number'=>$params['follow_number'],
                   'card_url'=>$params['card_url'],
                   'card_logo'=>$params['card_logo'],
                   'card_details'=>$params['card_details'],
                   'is_recommend'=>$params['is_recommend'],
                   'is_hot'=>$params['is_hot'],
                   'update_time'=>date('Y-m-d H:i:s',time()),
                );
               $bank_card= $row->save($param);
                if( $bank_card){
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

        $arr=array(
            'id'=>$rows['id'],
            'card_name'=>$rows['card_name'],
            'bank_name'=>$bank['bank_name'],
            'bank_id'=>$rows['bank_id'],
            'card_type_id'=>$rows['card_type_id'],
            'card_logo'=>$rows['card_logo'],
            'card_url'=>$rows['card_url'],
            'card_details'=>$rows['card_details'],
            'is_hot'=>(string)$rows['is_hot'],
            'is_deleted'=>$rows['is_deleted'],
            'displayorder'=>$rows['displayorder'],
            'follow_number'=>$rows['follow_number'],
            'apply_number'=>$rows['apply_number'],
            'is_recommend'=>(string)$rows['is_recommend'],
        );
        $this->view->assign("row", $arr);
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
            if ($params)
            {   $param['create_time']=date('Y-m-d H:i:s',time());
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
            //先查出银行有没有被删，被删了不能恢复
            $check_bank=db('bank')->where('id',$row['bank_id'])->where('is_deleted',0)->select();
            if(!empty($check_bank)){
                $count = $row->save($params);
                if ($count)
                {
                    $this->code = 1;
                }
            }else{
                $this->code = -1;
                $this->msg  ="请先恢复对应银行再进行恢复操作！";
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
