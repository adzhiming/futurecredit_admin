<?php

namespace app\admin\controller\theme;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\db\Query;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Addtheme extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('theme'); 
    }

    /**
     * 查看
     */
    public function index()
    {

       
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add($ids = null)
    {
      $row = $this->model->get($ids);
        
        if ($this->request->isPost())
        {
        
            $params['name'] = $this->request->post('theme');
            $params['order'] = $this->request->post('order');
            $params['logo'] = $this->request->post('logo');
            $params['remark'] = $this->request->post('remark');
            $params['status'] = $this->request->post('status');
            
            if( $params['status'] == 1)
            {
                $rs = $this->model->where('status',1)->field('id')->select();
                if(count($rs) >=4){
                    $this->code =2;
                    $this->msg = '显示栏已满';
                    return ;
                }
            }
            $params['create_time'] = date('Y-m-d H:i:s',time());
            $this->code = -1;
          
            if(empty($params['order'])) $params['order']  = 0;
        
            if($params)
            {
                $rs = $this->model->insert($params);
                if(false !== $rs)
                {
                    $this->code = 1;
                }

                 return;
            }
        }
        $this->assign('id',$ids);
        $this->assign('row',$row);
        return $this->view->fetch();
    }

    
    
    /**
     * 更新
     */
    public function change()
    {

        
        
        if ($this->request->isPost())
        {
      
            
            $id = $this->request->post('id');
            $row = $this->model->get(['id' => $id]);
           
            $params['name'] = $this->request->post('theme');
            $params['order'] = $this->request->post('order');
            $params['logo'] = $this->request->post('logo');
            $params['remark'] = $this->request->post('remark');
            $params['status'] = $this->request->post('status');
            
            if( $params['status'] == 1)
            {
                $rs = $this->model->where('status',1)->field('id')->select();
                if(count($rs) >=4){
                    $this->code = 2;
                    $this->msg = '显示栏已满';
                    return ;
                }
            }
            
            
            $params['update_time'] = date('Y-m-d H:i:s',time());
            $this->code = -1;
    
            if(empty($params['order'])) $params['order']  = 0;
    
            if($id)
            {
             //   $rs =   $row->save($params);
                $sql = "UPDATE crd_theme SET logo="."'{$params['logo']}'". " , update_time=". "'{$params['update_time']}'" ." , remark="."'{$params['remark']}'" ." , name=". "'{$params['name']}'"." , `order`=". $params['order']." , status=". $params['status']." WHERE id=".$id;
            
                $rs =   db()->query($sql);
                if(false !== $rs)
                {
                    $this->code = 1;
                }
    
                return;
            }
        }

    }
    
    /**
     * 添加卡
     */
    public function addcard()
    {
        $id = $this->request->get('ids');
        $row = $this->model->get(['id' => $id]);
        $where = array('is_deleted'=>0);
        $bank_card = model('bank_card')->where($where)->field('id,bank_id,card_type_id,card_logo,card_url,card_name')->select();
    
        $theme_card = model('theme_bank_card')->where('theme_id',$id)->field('theme_id,card_id')->order('card_id')->select();
       
        foreach ($bank_card as $k=>$v){
            $bank_card[$k]['selected']=0;
            foreach ($theme_card as $kk =>$vv){            
                if($v['id'] == $vv['card_id']){ 
                    $bank_card[$k]['selected'] = 1;
                }
             
            }
        }
       
      //  var_dump($bank_card);exit;
        //$select_name='';
       /*  if(!empty($theme_card))
        {
            $str='';
            foreach ($theme_card as $k => $v)
            {
                if($str ==""){
                    $str = $v['card_id'];
                }
                else{
                    $str .= ','. $v['card_id'];
                }
               
            }
            
           // $data['id'] = array('in',$str);
            
           // $select_name = model('bank_card')->where($data)->field('id,bank_id,card_type_id,card_logo,card_url,card_name')->select();
        } */
       

        
        if($this->request->isPost())
        {
            $card_id = $this->request->post('str');
            $theme_id = $this->request->post('id');
            $arr = explode(",",$card_id);
            $count =  count($arr)-1;
           
         
            for ($i =0;$i<=$count;$i++)
            {
                $data['theme_id'] = $theme_id;
                $data['create_time'] = date("Y-m-d H:i:s",time());
                $data['card_id'] = $arr[$i];
                //var_dump($data);exit;

                $list = model('theme_bank_card')->insert($data);
            }
            if(false !== $list)
            {
                $this->code = 1;
            }
            
            return ;
        }
     
       
        //$this->assign('select_name',$select_name);
        $this->assign('theme_card',$theme_card);
        $this->assign('id',$id);
        $this->assign('row',$row);
        $this->assign('card',$bank_card);
        return $this->view->fetch();
    }

    
    
    /**
     * 更新卡
     */
    
    public function changecard()
    {

        if($this->request->isPost())
        {
            $card_id = $this->request->post('str');
            $theme_id = $this->request->post('id');
            
            
            if(empty($card_id))
            {
                $arr = explode(",",$card_id);
                $count =  count($arr)-1;
                $theme_card = model('theme_bank_card')->where('theme_id',$theme_id)->field('card_id')->select();
                $arr2 ='';
                foreach ($theme_card as $v)
                {
                    $arr2 .= ",".$v['card_id'];
                }
                
                $arr2 = explode(",",trim($arr2,','));
                
                $diff_delete = array_diff($arr2,$arr);  
                $diff_update = '';
            }else {
                $arr = explode(",",$card_id);
                $count =  count($arr)-1;
                $theme_card = model('theme_bank_card')->where('theme_id',$theme_id)->field('card_id')->select();
                $arr2 ='';
                foreach ($theme_card as $v)
                {
                    $arr2 .= ",".$v['card_id'];
                }
                
                $arr2 = explode(",",trim($arr2,','));
                
                $diff_delete = array_diff($arr2,$arr);    //比较库再删除
                $diff_update = array_diff($arr,$arr2); //比较库再添加
            }
            
           
            
            $del='';
            $list='';
            if(!empty($diff_delete))
            {
                foreach ($diff_delete as $v)
                {
                    $del_where = array('theme_id'=>$theme_id,'card_id'=> $v);
                    $del = model('theme_bank_card')->where($del_where)->delete();
                }
            }
          
            if(!empty($diff_update))
            { 
                foreach ($diff_update as $v)
                {
                    $del_where = array('theme_id'=>$theme_id,'card_id'=> $v);
                    $data['theme_id'] = $theme_id;
                    $data['create_time'] = date("Y-m-d H:i:s",time());
                    $data['card_id'] =  $v;
                    $list = model('theme_bank_card')->insert($data);
                }
            
            }
            if(false !== $list || $del !== false)
            {
                $this->code = 1;
            }
    
            return ;
        }

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
