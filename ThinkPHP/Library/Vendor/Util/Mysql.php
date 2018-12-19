<?php
/*
* mysql 单例
*/
class Mysql{
//    private $host    ='192.168.1.9'; //数据库主机
//    private $user     = 'root'; //数据库用户名
//    private $pwd     = ''; //数据库用户名密码
//    private $database = 'g-townoa'; //数据库名
//    private $charset = 'GBK'; //数据库编码，GBK,UTF8,gb2312
    private $link;             //数据库连接标识;
    private $rows;             //查询获取的多行数组
    static $_instance; //存储对象
    /**
     * 构造函数
     * 私有
     */
    public function __construct($host,$user,$pwd,$database,$charset = 'GBK',$pconnect = false) {
        if (!$pconnect) {
            $this->link = @ mysql_connect($host, $user, $pwd) or $this->err();
        } else {
            $this->link = @ mysql_pconnect($host, $user, $pwd) or $this->err();
        }
        mysql_select_db($database) or $this->err();
        $this->query("SET NAMES '{$charset}'", $this->link);
        return $this->link;
    }
    /**
     * 防止被克隆
     *
     */
//    private function __clone(){}
//    public static function getInstance($pconnect = false){
//        if(FALSE == (self::$_instance instanceof self)){
//            self::$_instance = new self($pconnect);
//        }
//        return self::$_instance;
//    }
    /**
     * 查询
     */
    public function query($sql, $link = '') {
        $this->result = mysql_query($sql, $this->link) or die('数据插入失败!'.mysql_error());
        return $this->result;
    }

    /**
     * 单行记录
     */
    public function getCount($sql) {
        $result = $this->query($sql);
        return mysql_num_rows($result);
    }
    /**
     * 单行记录
     */
    public function getRow($sql, $type = MYSQL_ASSOC) {
        $result = $this->query($sql);
        return @ mysql_fetch_array($result, $type);
    }
    /**
     * 多行记录
     */
    public function getRows($sql, $type = MYSQL_ASSOC) {
        $result = $this->query($sql);
        while ($row = @ mysql_fetch_array($result, $type)) {
            $this->rows[] = $row;
        }
        return $this->rows;
    }
    /**
     * 作用:插入数据
     * 返回:表內記录
     * 类型:数组
     * 參数:$db->insert('$table',array('title'=>'Zxsv'))
     */
    public function add($table, $args) {
        $sql = "INSERT INTO `$table` SET ";

        $code = self::getCode ( $table, $args );
        $sql .= $code;
        return $this->query($sql);
        //return self::execute ( $sql );
    }

    /**
     * 修改数据
     * 返回:記录数
     * 类型:数字
     * 參数:$db->update($table,array('title'=>'Zxsv'),array('id'=>'1'),$where
     * ='id=3');
     */
    public function update($table, $args, $where) {
        $code = $this->getCode ( $table, $args );
        $sql = "UPDATE `$table` SET ";
        $sql .= $code;
        $sql .= " Where $where";
        return $this->query($sql);
        //return self::execute ( $sql );
    }

    /**
     * 作用:刪除数据
     * 返回:表內記录
     * 类型:数组
     * 參数:$db->delete($table,$condition = null,$where ='id=3')
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM `$table` Where $where";
        return $this->query($sql);
        //return self::execute ( $sql );
    }


    /**
     * 获取要操作的数据
     * 返回:合併后的SQL語句
     * 类型:字串
     */
    private function getCode($table, $args) {
        $code = '';
        if (is_array ( $args )) {
            foreach ( $args as $k => $v ) {
                if ($v == '') {
                    continue;
                }
                $code .= "`$k`='$v',";
            }
        }
        $code = substr ( $code, 0, - 1 );
        return $code;
    }


    /**
     * 错误信息输出
     */
    protected function err($sql = null) {
        //这里输出错误信息
        echo 'error';
        exit();
    }
}

?>