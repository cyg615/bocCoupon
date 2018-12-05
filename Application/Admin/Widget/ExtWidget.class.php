<?php
namespace Admin\Widget;
use Think\Controller;
/**
 * 扩展插件
 * @author Administrator
 */
class ExtWidget extends Controller {
	
	/**
	 * Kindeditor编辑器
	 */
	public function kindeditor($data) {
		$this->setData($data);
		$this->display ( 'Widget:Ext/kindeditor' );
	}
	
	/**
	 * uploadify上传插件
	 */
	public function uploadify($data) {
		$this->setData($data);
		$this->display ( 'Widget:Ext/uploadify' );
	}

	/**
	 * 渲染局部页面
	 */
	public function renderPartial($data) {
		$this->setData($data);
		$this->display ($data['partial']);
	}
	
	/**
	 * 循环赋值给模版
	 */
	public function setData($data){
		foreach ( $data as $key => $vule ) {
			$this->assign ( $key, $vule );
		}
	}
}
?>