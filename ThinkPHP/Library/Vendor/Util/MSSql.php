<?php
/*MSSql�Ĳ�����*/
class MSSql {
    var $link;
    var $querynum = 0;
    /*����MSSql���ݿ⣬������dbsn->���ݿ��������ַ��dbun->��½�û�����dbpw->��½���룬dbname->���ݿ�����*/
    function Connect($dbsn, $dbun, $dbpw, $dbname) {
        if($this->link = @mssql_connect($dbsn, $dbun, $dbpw, true)) {
            //var_dump($this->link);exit;
            //$query = $this->Query('SET TEXTSIZE 2147483647');
            if (@mssql_select_db($dbname, $this->link)) {
            } else {
                $this->halt('Can not Select DataBase');
            }
        } else {
            //echo 999;exit;
            $this->halt('Can not connect to MSSQL server');
        }
    }
    /*ִ��sql��䣬���ض�Ӧ�Ľ����ʶ*/
    function Query($sql) {
        if($query = mssql_query($sql, $this->link)) {
            //var_dump($query);exit;
            $this->querynum++;
            return $query;
        } else {
            $this->querynum++;
            $this->halt('MSSQL Query Error', $sql);
        }


    }
    /*ִ��Insert Into��䣬����������insert�������������Զ�������id*/
    function Insert($table, $iarr) {
        $value = $this->InsertSql($iarr);
        $query = $this->Query('INSERT INTO ' . $table . ' ' . $value . '; SELECT SCOPE_IDENTITY() AS [insertid];');
        $record = $this->GetRow($query);
        $this->Clear($query);
        return $record['insertid'];
    }
    /*ִ��Update��䣬����������update������Ӱ�������*/
    function Update($table, $uarr, $condition = '') {
        $value = $this->UpdateSql($uarr);
        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $query = $this->Query('UPDATE ' . $table . ' SET ' . $value . $condition . '; SELECT @@ROWCOUNT AS [rowcount];');
        $record = $this->GetRow($query);
        $this->Clear($query);
        return $record['rowcount'];
    }
    /*ִ��Delete��䣬����������Delete������Ӱ�������*/
    function Delete($table, $condition = '') {
        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $query = $this->Query('DELETE ' . $table . $condition . '; SELECT @@ROWCOUNT AS [rowcount];');
        $record = $this->GetRow($query);
        $this->Clear($query);
        return $record['rowcount'];
    }
    /*���ַ�תΪ���԰�ȫ�����mssqlֵ������a'aתΪa''a*/
    function EnCode($str) {
        return str_replace("'", "''", str_replace('', '', $str));
  }
  /*�����԰�ȫ�����mssqlֵתΪ������ֵ������a''aתΪa'a*/
  function DeCode($str) {
      return str_replace("''", "'", $str);
  }
  /*����Ӧ���к�ֵ���ɶ�Ӧ��insert��䣬�磺array('id' => 1, 'name' => 'name')����([id], [name]) VALUES (1, 'name')*/
  function InsertSql($iarr) {
    if (is_array($iarr)) {
      $fstr = '';
      $vstr = '';
      foreach ($iarr as $key => $val) {
        $fstr .= '[' . $key . '], ';
        $vstr .= "'" . $val . "',";
      }
      if ($fstr) {
        $fstr = '(' . substr($fstr, 0, -2) . ')';
        $vstr = '(' . substr($vstr, 0, -2) . ')';
        return $fstr . ' VALUES ' . $vstr;
      } else {
        return '';
      }
    } else {
      return '';
    }
  }
  /*����Ӧ���к�ֵ���ɶ�Ӧ��insert��䣬�磺array('id' => 1, 'name' => 'name')����[id] = 1, [name] = 'name'*/
  function UpdateSql($uarr) {
    if (is_array($uarr)) {
      $ustr = '';
      foreach ($uarr as $key => $val) {
        $ustr .= '[' . $key . '] = \'' . $val . '\', ';
      }
      if ($ustr) {
        return substr($ustr, 0, -2);
      } else {
        return '';
      }
    } else {
      return '';
    }
  }
  /*���ض�Ӧ�Ĳ�ѯ��ʶ�Ľ����һ��*/
  function GetRow($query, $result_type = MSSQL_ASSOC) {

      while ($data[] = mssql_fetch_array($query, $result_type));
      array_pop($data);
      $keylist = array_keys($data[0]);
      if(!isset($data[0])){
          return false;
      }

      foreach($data as $key=>$value) {
          if (!$value)
              continue;
          if (empty($value))
          {
              unset($data[$key]);
          }
          foreach($keylist as $keyname){
              $keyencoding =  mb_detect_encoding($value[$keyname],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
              //error_log('�ֶΣ�'.$keyname.'  ֵ��'.$value[$keyname].' �����ʽ:'.$keyencoding);
              if($keyencoding=='UTF-8'){
                  if(md5($data[$key][$keyname])=='a145d59f9e26d6332893788cd47c5aa9')
                      $data[$key][$keyname] = 'Բͨ'; //�˴����� �������Ϊutf8����ȷ��ʾgbk�쳣���
                  else
                      $data[$key][$keyname]=mb_convert_encoding($value[$keyname],'utf-8','utf-8');//$this->charsetToUTF8();
              }else
                  $data[$key][$keyname]=mb_convert_encoding($value[$keyname],'utf-8',mb_detect_encoding($value[$keyname],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5')));//$this->charsetToUTF8();
          }
      }
      return  $data;
    //return mssql_fetch_array($query, $result_type);
  }
  /*��ղ�ѯ�����ռ�õ��ڴ���Դ*/
  function Clear($query) {
    return mssql_free_result($query);
  }
  /*�ر����ݿ�*/
  function Close() {
    return mssql_close($this->link);
  }
  function halt($message = '', $sql = '') {
    $message .= '<br />MSSql Error:' . mssql_get_last_message();
    if ($sql) {
      $sql = '<br />sql:' . $sql;
    }
    exit("DataBase Error.<br />Message $message $sql");
  }
}
?>