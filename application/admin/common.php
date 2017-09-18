<?php

use app\common\model\Category;
use fast\Form;
use fast\Tree;
use think\Db;

/**
 * 生成下拉列表
 * @param string $name
 * @param mixed $options
 * @param mixed $selected
 * @param mixed $attr
 * @return string
 */
function build_select($name, $options, $selected = [], $attr = [])
{
    $options = is_array($options) ? $options : explode(',', $options);
    $selected = is_array($selected) ? $selected : explode(',', $selected);
    return Form::select($name, $options, $selected, $attr);
}

/**
 * 生成单选按钮组
 * @param string $name
 * @param array $list
 * @param mixed $selected
 * @return string
 */
function build_radios($name, $list = [], $selected = null)
{
    $html = [];
    $selected = is_null($selected) ? key($list) : $selected;
    $selected = is_array($selected) ? $selected : explode(',', $selected);
    foreach ($list as $k => $v)
    {
        $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
    }
    return implode(' ', $html);
}

/**
 * 生成复选按钮组
 * @param string $name
 * @param array $list
 * @param mixed $selected
 * @return string
 */
function build_checkboxs($name, $list = [], $selected = null)
{
    $html = [];
    $selected = is_null($selected) ? [] : $selected;
    $selected = is_array($selected) ? $selected : explode(',', $selected);
    foreach ($list as $k => $v)
    {
        $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
    }
    return implode(' ', $html);
}

/**
 * 生成分类下拉列表框
 * @param string $name
 * @param string $type
 * @param mixed $selected
 * @param array $attr
 * @return string
 */
function build_category_select($name, $type, $selected = null, $attr = [], $header = [])
{
    $tree = Tree::instance();
    $tree->init(Category::getCategoryArray($type), 'pid');
    $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
    $categorydata = $header ? $header : [];
    foreach ($categorylist as $k => $v)
    {
        $categorydata[$v['id']] = $v['name'];
    }
    $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
    return build_select($name, $categorydata, $selected, $attr);
}

/**
 * 生成表格操作按钮栏
 * @param array $btns 按钮组
 * @param array $attr 按钮属性值
 * @return string
 */
function build_toolbar($btns = NULL, $attr = [])
{
    $btns = $btns ? $btns : ['refresh', 'add', 'edit', 'delete'];
    $btns = is_array($btns) ? $btns : explode(',', $btns);
    $btnAttr = [
        'refresh' => ['javascript:;', 'btn btn-primary btn-refresh', 'fa fa-refresh', ''],
        'add'     => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add')],
        'edit'    => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit')],
        'delete'  => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Delete')],
    ];
    $btnAttr = array_merge($btnAttr, $attr);
    $html = [];
    foreach ($btns as $k => $v)
    {
        if (!isset($btnAttr[$v]))
        {
            continue;
        }
        list($href, $class, $icon, $text) = $btnAttr[$v];
        $html[] = '<a href="' . $href . '" class="' . $class . '" ><i class="' . $icon . '"></i> ' . $text . '</a>';
    }
    return implode(' ', $html);
}

/**
 * 生成页面Heading
 *
 * @param string $title
 * @param string $content
 * @return string
 */
function build_heading($title = NULL, $content = NULL)
{
    if (is_null($title) && is_null($content))
    {
        $path = request()->pathinfo();
        $path = $path[0] == '/' ? $path : '/' . $path;
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data)
        {
            $title = $data['title'];
            $content = $data['remark'];
        }
    }
    if (!$content)
        return '';
    return '<div class="panel-heading"><div class="panel-lead"><em>' . $title . '</em>' . $content . '</div></div>';
}


/*
 * get形式调用接口获取数据
 */
function http_request($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data =  curl_exec($ch);
    if(curl_errno($ch)){return 'ERROR'.curl_error($ch);}
    curl_close($ch);
    return $data;
}

/*
 * post形式调用接口获取数据
 */
function http_request_post($url,$data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data))
    {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}


/*
 * 超管审核通过更改帮客来的状态
 */
function bklChanggeStatus($params)
{
    $url = config('bkl_url_base') . '/api.php/BklTofuturnCredit/applyAgentNotify';
    $result = http_request1($url, $params);
    return $result;
}

function signData($key, $data) {
	ksort($data);
	$data['key'] = $key;
	$sign_now = strtoupper(MD5(urldecode(http_build_query($data))));

	return $sign_now;
}

function getPrjKeyValue($index,$key){
	$arr = config("PRJ_INFO");
	return $arr[$index][$key];
}

function api_post_low($url, $params, &$return_code, &$return_msg, &$return_data){
	//echo $url;
	//die;
	$return_content = "";
	$data_string = array("params" => $params);
	$data_string = json_encode($params);
	$ch = curl_init();
	// curl_setopt ($ch, CURLOPT_PROXY, "http://127.0.0.1:8888");
	//curl_setopt ($ch, CURLOPT_PROXY, "http://127.0.0.1:8888");
	//curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=94cu843ig05b7dmrj8ab9fiuh7; XDEBUG_SESSION=ECLIPSE_DBGP");
	if(!empty($params['session_id'])){
		curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID={$params['session_id']}");
		curl_setopt($ch, CURLOPT_HEADER, false);
	}
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);

	$header = array(
			'Content-Type: application/json; charset=utf-8',
			'Content-Length: ' . strlen($data_string) );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	ob_start();
	$rc = curl_exec($ch);
	$return_content = ob_get_contents();
/* 	echo $return_content;die; */
	ob_end_clean();
	if ($rc === false) {
		//\Think\Log::record($url."\n".$return_content);
		return false;
	}
	$return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($return_code == 200){
		$data = json_decode($return_content, true); 
		if (!empty($data) && isset($data["code"])){
			$return_code = $data["code"];
			$return_msg = $data["msg"];
			if ( isset($data["data"])){
				$return_data = $data["data"];
			}
			else {
				$return_data = array();
			}
			
		
			return true;
		}
		$return_data = $return_content;
		//\Think\Log::record($url."\n".$return_content);
		return false;
	}
	$return_data = $return_content;
	//\Think\Log::record($url."\n".$return_content);
	return false;
}
