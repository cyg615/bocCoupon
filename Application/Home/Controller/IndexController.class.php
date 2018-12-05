<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){

        //echo  think_ucenter_md5("GTgogo123", UC_AUTH_KEY) ;exit;
        $this->redirect('/Admin/');
       // phpinfo();

//        $category = D('Category')->getTree();
//        $lists    = D('Document')->lists(null);
//
//        $this->assign('category',$category);//栏目
//        $this->assign('lists',$lists);//列表
//        $this->assign('page',D('Document')->page);//分页
//
//
//        $this->display();
        //$conn = mssql_connect("server-dsn1", "molly", "Gtown123");
        //print_r($conn);
        //echo 111;exit;
//        $sql ="select top 1 U_OrderId,U_OStatus,U_ExpNo,U_ExpCom,U_UpdateDate from [@ti_z0081]";
//        //$sql="SELECT  T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  U_OStatus='8' AND T0.DocEntry=18236";
//        $link=mssql_connect('192.168.1.10','molly','Gtown123',true);
//        if($link){
//           //var_dump($link);exit;
//            mssql_select_db("UAT", $link);
//            $query = mssql_query($sql, $link);
//            while($data[]=mssql_fetch_array($query))
//            {
//
//            }
//            print_r($data);
//
//        }
//        else   {
//            echo   "失败 ";
//        }
//         exit;
        //$sql ="select top 1 U_OrderId,U_OStatus,U_ExpNo,U_ExpCom,U_UpdateDate from [@ti_z0081]";
//        $sql="SELECT  T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  U_OStatus='8' AND T0.DocEntry=18236";
//        //import("@.ORG.Util.Sqlserver");
//        Vendor('Util.MSSql');
//        $sqlser = new \MSSql();
//        $conn=$sqlser->Connect('192.168.1.10:1433','sa2','sasa','UAT');
//        $query=$sqlser->Query($sql);
//        //var_dump($query);
//        //$cardList = $sqlser->GetRow($query);
//        //print_r($cardList);
//        //$cardList = $sqlser->query($sql);
//        $data=$sqlser->GetRow($query);
//        print_r($data);exit;
//        while($date=$sqlser->GetRow($query))
//        {
//
//        }
//        //array_pop($data);
//        print_r($data);

//        $msdb=mssql_connect($sqlserver,$sqluser,$sqlpassword);
//        mssql_select_db($sqldatabase,$msdb);
//        $result = mssql_query($sql, $sqlconnection);
//        $data =null;
//        while($row = mssql_fetch_array($result)) {
//            $data = $row;
//        }


//        $host = '192.168.1.10';
////        $sqldatabase= 'UAT';
////        $sqluser    = 'molly';
////        $sqlpassword= 'Gtown123';
//        $sqldatabase= 'UAT';
//        $sqluser    = 'sa2';
//        $sqlpassword= 'sasa';
//        $msdb=mssql_connect($host,$sqluser,$sqlpassword);
//        mssql_select_db($sqldatabase,$msdb);
//        $sql="SELECT  T3.U_CardCode,T4.CardFName,T2.u_orderid+'~'+CONVERT(NVARCHAR(50),T2.docentry)+'~'+CONVERT(NVARCHAR(50),T2.lineid) ordersn,''shippingcode,''shippingname,T2.U_CSTNAME username,T2.U_address address,T2.u_zipcode zipcode,t2.u_phone phone,T2.U_OTPHONE tel,'' shippingfee,t2.U_STATE province,t2.u_city city,t2.U_County region,T0.u_itemcode item_no,t0.u_qty num FROM [@ti_z0131] t0 LEFT JOIN [@ti_z0130] T1 ON t0.docentry=t1.docentry LEFT JOIN [@ti_z0081] T2 ON t0.U_BsEntry=t2.docentry AND t0.u_bsline=t2.lineid left join [@ti_Z0080] T3 ON T2.docentry=t3.docentry left join ocrd T4 on t3.u_cardcode=t4.cardcode WHERE  U_OStatus='8' AND T0.DocEntry=18236";
//        //$sql ="select top 1 U_OrderId,U_OStatus,U_ExpNo,U_ExpCom,U_UpdateDate from [@ti_z0081]";
//        $result = mssql_query($sql, $msdb);
//        while($row = mssql_fetch_array($result)) {
//            print_r($row);
//        }

//        $host = '192.168.1.10';
//        $sqldatabase= 'GT';
//        $sqluser    = 'sa2';
//        $sqlpassword= 'sasa';
//        $msdb=mssql_connect($host,$sqluser,$sqlpassword);
//        mssql_select_db($sqldatabase,$msdb);
//        $sql ="select top 1 U_OrderId,U_OStatus,U_ExpNo,U_ExpCom,U_UpdateDate from [@ti_z0081]";
//        $result = mssql_query($sql, $msdb);
//        print_r($result);
//        while($row = mssql_fetch_array($result)) {
//            //print_r($row);
//        }


    }

}