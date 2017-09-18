<?php
namespace app\common\controller;

/**
 * app返回码不一样，所以多创建一个
  *@description 
  *@author JHChan314
  *@date 2016年4月18日
  **/

class AppResult {
	
	public $code;
	
	public $msg;
	
	public $data;
	
	public function __construct() {
		$this->code = 1;
		$this->msg = '有错误，请重试！';
		$this->data = array();
	}
	
	/**
	 * 输出JSON头和数据
	 */
	public function returnJSON(){
		header('Content-type: text/json');
		$data =  json_encode($this, JSON_UNESCAPED_UNICODE);
		exit($data);
	}
}