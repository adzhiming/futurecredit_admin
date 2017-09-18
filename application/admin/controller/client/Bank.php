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
class Bank extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Bank');
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
            $filter = json_decode($filter, TRUE);
            if(!empty($filter['create_time']))
            {
                list($start,$end) = explode(',', $filter['create_time']);
                $where['create_time'] = array('between',array(date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)));
            }
            if(!empty($filter['bank_name'])) $where['bank_name'] =  array('like', '%'.$filter['bank_name'].'%');
            if(!empty($filter['bank_phone'])) $where['bank_phone'] = array('like', '%'.$filter['bank_phone'].'%');
         
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $lists =db('bank')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
          //  var_dump($lists);exit;
           $list=array();
            foreach ($lists as $k=>$v){

               //$rule_description = empty($v['rule_description'])?"": $this->substr_text($v['rule_description'],0,5);
               $rule_description = empty($v['rule_description'])?"": mb_substr($v['rule_description'],0,15,'utf-8');
                 $list[]=array(
                     'id'=>$v['id'],
                     'bank_name'=>$v['bank_name'],
                     'bank_logo'=>$v['bank_logo'],
                     'level_price'=>$v['level_price'],
                     'level1_price'=>$v['level1_price'],
                     'level2_price'=>$v['level2_price'],
                     'level3_price'=>$v['level3_price'],
                     'rule_description'=>$rule_description,
                     'price_type'=>$v['price_type'],
                     'is_deleted'=>$v['is_deleted'],
                     'create_time'=>$v['create_time'],
                 );
             }

            $result = array("total" => $total, "rows" => $list);
            return json($result);

        }
        return $this->view->fetch();
    }

     public   function substr_text($str, $start=0, $length, $charset="utf-8", $suffix="")

    {

        if(function_exists("mb_substr")){

            return mb_substr($str, $start, $length, $charset).$suffix;

        }

        elseif(function_exists('iconv_substr')){

            return iconv_substr($str,$start,$length,$charset).$suffix;

        }

        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";

        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";

        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";

        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

        preg_match_all($re[$charset], $str, $match);

        $slice = join("",array_slice($match[0], $start, $length));

        return $slice.$suffix;

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

                $params['create_time']=date('Y-m-d H:i:s',time());
                $bank_add= $this->model->create($params);
                $bankId= db('bank')->getLastInsID();
                $agents_sys_price=array(
                    'type'=>1,
                    'bank_id'=>$bankId,
                    'agent_id'=>-1,
                    'level'=>-1,
                    'level_price'=>$params['level_price'],
                    'level1_price'=>$params['level1_price'],
                    'level2_price'=>$params['level2_price'],
                    'level3_price'=>$params['level3_price'],
                    'create_time'=>date('Y-m-d H:i:s',time()),

                );
               db('agent_sys_price')->insert($agents_sys_price);
                $agents_sys_price_log=array(
                    'type'=>1,
                    'bank_id'=>$bankId,
                    'fy_type'=>1,
                    'agent_id'=>-1,
                    'level'=>-1,
                    'level_price'=>$params['level_price'],
                    'level1_price'=>$params['level1_price'],
                    'level2_price'=>$params['level2_price'],
                    'level3_price'=>$params['level3_price'],
                    'create_time'=>date('Y-m-d H:i:s',time()),

                );
                db('agent_sys_price_log')->insert($agents_sys_price_log);

                if($bank_add){
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
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $this->code = -1;
            $params = $this->request->post("row/a", [], 'strip_tags');
            $params['create_time']=date('Y-m-d H:i:s',time());
            if ($params)
            {
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
               $bank_edit= $row->save($params);
                $agents_sys_price=array(
                    'type'=>1,
                    'agent_id'=>-1,
                    'level'=>-1,
                    'level_price'=>$params['level_price'],
                    'level1_price'=>$params['level1_price'],
                    'level2_price'=>$params['level2_price'],
                    'level3_price'=>$params['level3_price'],
                );
                //检测是否存在price
                $check=db('agent_sys_price')
                    ->where('type',1)
                    ->where('bank_id',$row['id'])
                    ->where('agent_id',-1)
                    ->where('level',-1)
                    ->select();

                if(!empty($check)){
                    $agents_sys_price['update_time']=date('Y-m-d H:i:s',time());
                    $agents_edit=db('agent_sys_price')
                        ->where('type',1)
                        ->where('bank_id',$row['id'])
                        ->where('agent_id',-1)
                        ->where('level',-1)
                        ->update($agents_sys_price);
                }
                else{
                    $agents_sys_price['bank_id']=$row['id'];
                    $agents_sys_price['create_time']=date('Y-m-d H:i:s',time());
                    $agents_edit=db('agent_sys_price')->insert($agents_sys_price);
                }



                $check_log_all=db('agent_sys_price_log')
                    ->where('type',1)
                    ->where('bank_id',$row['id'])
                    ->where('agent_id',-1)
                    ->where('level',-1)
                    ->select();


                if(!empty($check_log_all)){
                    $agents_sys_price_log=array(
                        'level_price'=>$params['level_price'],
                        'level1_price'=>$params['level1_price'],
                        'level2_price'=>$params['level2_price'],
                        'level3_price'=>$params['level3_price'],
                        'update_time'=>date('Y-m-d H:i:s',time()),
                    );
                   db('agent_sys_price_log')
                        ->where('type',1)
                        ->where('bank_id',$row['id'])
                        ->where('agent_id',-1)
                        ->where('level',-1)
                        ->update($agents_sys_price_log);


                }
                else{
                    $agents_sys_price_log=array(
                        'type'=>1,
                        'agent_id'=>-1,
                        'level'=>-1,
                        'level_price'=>$params['level_price'],
                        'level1_price'=>$params['level1_price'],
                        'level2_price'=>$params['level2_price'],
                        'level3_price'=>$params['level3_price'],
                    );
                    $agents_sys_price['create_time']=date('Y-m-d H:i:s',time());
                    $agents_edit=db('agent_sys_price_log')->insert($agents_sys_price_log);
                }

                $check_price=db('agent_sys_price')
                    ->where('type',1)
                    ->where('bank_id',$row['id'])
                    ->where('agent_id',0)
                    ->where('level',0)
                    ->select();

                if(!empty($check_price)){
                    $agents_all_list=array(
                        'level_price'=>$params['level_price'],
                        'update_time'=>date('Y-m-d H:i:s',time()),
                    );
                    db('agent_sys_price')
                        ->where('type',1)
                        ->where('bank_id',$row['id'])
                        ->where('agent_id',0)
                        ->where('level',0)
                        ->update($agents_all_list);
                }


                $check_log=db('agent_sys_price_log')
                    ->where('type',1)
                    ->where('bank_id',$row['id'])
                    ->where('agent_id',0)
                    ->where('level',0)
                    ->select();

                if(!empty($check_log)){
                    //如果不为空，修改log表数据
                    $agents_log_list=array(
                        'op_type'=>2,
                        'level_price'=>$params['level_price'],
                        'update_time'=>date('Y-m-d H:i:s',time()),
                    );
                    db('agent_sys_price_log')
                        ->where('type',1)
                        ->where('bank_id',$row['id'])
                        ->where('agent_id',0)
                        ->where('level',0)
                        ->update($agents_log_list);
                }



                if($bank_edit && $agents_edit){
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
        $this->view->assign("row", $row);
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

            $bank_card_data['is_deleted']=1;
            $bank_card_check=db('bank_card')->where('bank_id',$row['id'])->where('is_deleted',0)->select();
            if(!empty($bank_card_check)){
                $bank_card_del=db('bank_card')->where('bank_id',$row['id'])->update($bank_card_data);
                if ($count && $bank_card_del)
                {
                    $this->code = 1;
                }else{
                    $this->code=-1;
                    $this->msg="删除失败";
                }
            }else{
                if ($count)
                {
                    $this->code = 1;
                }else{
                    $this->code=-1;
                    $this->msg="删除失败";
                }
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
