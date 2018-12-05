<?php
/**
 * Created by PhpStorm.
 * User: cyg
 * Date: 2017/8/16
 * Time: 10:01
 */
namespace Admin\Model;
use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
class TaskLogModel extends Model {

    function getList($model, $map, $sort = '') {
        //排序字段 默认为主键名
        if (isset($_REQUEST['_sort'])) {
            $sort = $_REQUEST['_sort'];
        } else if (in_array('sort', get_model_fields($model))) {
            $sort = "sort asc";
        } else if (empty($sort)) {
            $sort = "id desc";
        }

        //取得满足条件的记录数
        $count_model = clone $model;
        //取得满足条件的记录数
        $count = $count_model -> where($map) -> count();

        if ($count > 0) {
            //创建分页对象
            if (!empty($_REQUEST['list_rows'])) {
                $list_rows = $_REQUEST['list_rows'];
            } else {
                $list_rows = 10;
            }
            import("@.ORG.Util.Page");
            $p = new \Page($count, $list_rows);
            //分页查询数据
            $vo_list = $model -> where($map) ->order($sort) -> limit($p -> firstRow . ',' . $p -> listRows) -> select();
            $p -> parameter = $this->search($model);
            //分页显示
            $page = $p -> show();
            if ($vo_list) {
                return array('list'=>$vo_list,'page'=>$page,'sort'=>$sort);
                //return $vo_list;
            }
        }
        return array();

        //return FALSE;
    }

//生成查询条件
    function search($model = null) {
        $map = array();
        //过滤非查询条件
        $request = array_filter(array_keys(array_filter($_REQUEST)), "filter_search_field");
        if (empty($model)) {
            $model = D(CONTROLLER_NAME);
        }
        $fields = get_model_fields($model);
        foreach ($request as $val) {
            $field = substr($val, 3);
            $prefix = substr($val, 0, 3);
            if (in_array($field, $fields)) {
                if ($prefix == "be_") {
                    if (isset($_REQUEST["en_" . $field])) {
                        if (strpos($field, "time") != false) {
                            $start_time = date_to_int(trim($_REQUEST[$val]));
                            $end_time = date_to_int(trim($_REQUEST["en_" . $field])) + 86400;
                            $map[$field] = array( array('egt', $start_time), array('elt', $end_time));
                        }
                        if (strpos($field, "date") != false) {
                            $start_date = trim($_REQUEST[$val]);
                            $end_date = trim($_REQUEST["en_" . substr($val, 3)]);
                            $map[$field] = array( array('egt', $start_date), array('elt', $end_date));
                        }
                    }
                }

                if ($prefix == "li_") {
                    $map[$field] = array('like', '%' . trim($_REQUEST[$val]) . '%');
                }
                if ($prefix == "eq_") {
                    $map[$field] = array('eq', trim($_REQUEST[$val]));
                }
                if ($prefix == "gt_") {
                    $map[$field] = array('egt', trim($_REQUEST[$val]));
                }
                if ($prefix == "lt_") {
                    $map[$field] = array('elt', trim($_REQUEST[$val]));
                }
            }
        }
        return $map;
    }

}