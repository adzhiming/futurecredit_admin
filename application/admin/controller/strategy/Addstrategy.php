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
class Addstrategy extends Backend
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

       
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add($ids = null)
    {
      $id =  $this->request->get('ids');       
      $data = model('help_strategy')->get($ids);
  
        if ($this->request->isPost())
        {
            $params['title'] = $this->request->post('title');
            $params['logo'] = $this->request->post('logo');
            $params['comment'] = $this->request->post('comment');
            $params['type'] = $this->request->post('type');
            $params['create_time']=date('Y-m-d H:i:s',time());
            $this->code = -1;
            
         
          if($params)
          {
              $rs =  $this->model->create($params);
              if($rs !== false)  $this->code = 1; 
          }

            return;
        }
        $this->assign('id',$id);
        $this->assign('data',$data);
        return $this->view->fetch();
    }
    
    
    /**
     * 草稿箱
     */
    
    public function draftbox()
    {
        if ($this->request->isPost())
        {
            $id = $this->request->post('id');
            $params['title'] = $this->request->post('title');
            $params['logo'] = $this->request->post('logo');
            $params['comment'] = $this->request->post('comment');
            $params['type'] = $this->request->post('type');
            $params['create_time']=date('Y-m-d H:i:s',time());
            $this->code = -1;
 
            if(!$id)
            {
                $params['status']= 1;
                $rs =  $this->model->create($params);
                if($rs !== false)  $this->code = 1;
            }
            else
            {
                $row = model('help_strategy')->get($id);
                if($row['status'] == 1)
                {
                    $params['status']=0; 
                    $rs =  $row->save($params);
                    if(false != $rs)
                    {
                        $this->code = 2;
                    }
                }
            }
             
        
            return;
        }
    }
    
    /**
     * 更新
     */
    public function change($ids = null)
    {
       
        if ($this->request->isPost())
        {                      
            $params['title'] = $this->request->post('title');
            $params['logo'] = $this->request->post('logo');
            $params['comment'] = $_POST['comment'];
            $params['type'] = $this->request->post('type');
            $params['update_time']=date('Y-m-d H:i:s',time());

            $id = $this->request->post('id');
            $this->code = -1;
            if($id)
            {
                $sql = "UPDATE crd_help_strategy SET logo="."'{$params['logo']}'". " , title=". "'{$params['title']}'" ." , type="."{$params['type']}" ." , `comment`=". "'{$params['comment']}'"." , update_time=". "'{$params['update_time']}'"." WHERE id=".$id;
             
                $rs =   db()->query($sql);
                if($rs !== false)  $this->code = 1;
            }

         
            return;
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
