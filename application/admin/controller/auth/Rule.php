<?php

namespace app\admin\controller\auth;

use app\common\controller\Backend;
use fast\Tree;
use think\Cache;

/**
 * 规则管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Rule extends Backend
{

    protected $model = null;
    protected $rulelist = [];
    protected $multiFields = 'ismenu,status';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('AuthRule');
        // 必须将结果集转换为数组
        Tree::instance()->init(collection($this->model->order('weigh', 'desc')->select())->toArray());
        $this->rulelist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'title');
        $ruledata = [0 => __('None')];
        foreach ($this->rulelist as $k => $v)
        {
            if (!$v['ismenu'])
                continue;
            $ruledata[$v['id']] = $v['title'];
        }
        $this->view->assign('ruledata', $ruledata);
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            $list = $this->rulelist;
            $total = count($this->rulelist);

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
                if (!$params['ismenu'] && !$params['pid'])
                {
                    $this->msg = __('The non-menu rule must have parent');
                    return;
                }
                $this->model->create($params);
                Cache::rm('__menu__');
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
            if ($params)
            {
                if (!$params['ismenu'] && !$params['pid'])
                {
                    $this->msg = __('The non-menu rule must have parent');
                    return;
                }
                $row->save($params);
                Cache::rm('__menu__');
                $this->code = 1;
            }

            return;
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $this->code = -1;
        if ($ids)
        {
            $delIds = [];
            foreach (explode(',', $ids) as $k => $v)
            {
                $delIds = array_merge($delIds, Tree::instance()->getChildrenIds($v, TRUE));
            }
            $delIds = array_unique($delIds);
            $count = $this->model->where('id', 'in', $delIds)->delete();
            if ($count)
            {
                Cache::rm('__menu__');
                $this->code = 1;
            }
        }

        return;
    }

}
