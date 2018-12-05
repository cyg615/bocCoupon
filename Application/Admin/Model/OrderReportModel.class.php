<?php

//namespace App;

//use core\lib\Exception;
//use Illuminate\Database\Eloquent\Model;
//use Illuminate\Support\Facades\Log;
//require_once (app_path() . '/Library/Util/Mysql.php');
namespace Admin\Model;
use Think\Model;

class OrderReportModel extends Model
{
    /**
     *  数组生成Excel文件
     * @param unknown $isDownload
     *
     */
    function arrayToExcel($filename,$list,$status){
        //require_once (app_path() . "/Library/PHPExcel.php");
        Vendor('Excel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        //$excel_path=base_path()."/Uploads/Boc/Excel/";
        $excel_path= DOC_ROOT . "/Uploads/Boccoupon/Excel/";
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                ),
            ),
        );
        $objPHPExcel = $this->send($objPHPExcel, $list,$status);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($excel_path . $filename . '.xls');

//        try {
//            foreach($list as $k=>$v)
//            {
//                if ($k == 'returnOrder') {
//                    $filename = "boc365_returnorder_" . date('Ymd') . "_" . $batch_no;
//                    $objPHPExcel = $this->send($objPHPExcel, $v);
//                    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//                    $objWriter->save($excel_path . $filename . '.xls');
//
//                } else {
//                    $filename = "boc365_order_" . date('Ymd') . "_" . $batch_no;
//                    $objPHPExcel = $this->send($objPHPExcel, $v);
//                    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//                    $objWriter->save($excel_path .  $filename . '.xls');
//                }
//            }
//        }catch(Exception $e)
//        {
//            Log::info($e->getMessage());
//        }
    }




    function getExpColor($str){
        $colorList = array('无','黑色','白色','银色','金色','见实物','图色','红色','蓝色','粉色','灰色','紫色','不锈钢色','玫瑰金','棕色','玫瑰金色','绿色','黄色','玫红色','颜色随机','白绿色','银白色','花色','浅绿色','酒红色','香槟色','摩卡金');
        $strArr=explode(' ',$str);
        $color = end($strArr);
        if(in_array($color,$colorList)){
            return $color;
        }
        return '';

    }


    /**
     * 生成正常订单数据
     * @param unknown $objPHPExcel
     * @param unknown $list
     * @return unknown
     */
    function send($objPHPExcel,$list,$status){
        //print_r($list);exit;
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '本地订单号')
            ->setCellValue('B1', '上传时间')
            ->setCellValue('C1', '券号')
            ->setCellValue('D1', '商品sap编码');
//            ->setCellValue('E1', '礼品编号')
//            ->setCellValue('F1', '礼品名称')
//            ->setCellValue('G1', '数量')
//            ->setCellValue('H1', '单价')
//            ->setCellValue('I1', '总计')
//            ->setCellValue('J1', '收货地址')
//            ->setCellValue('K1', '收件人')
//            ->setCellValue('L1', '邮编')
//            ->setCellValue('M1', '备注')
//            ->setCellValue('N1', '颜色')

        if($status>0)
        {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E1', '中行订单号')
                ->setCellValue('F1', '中行订单号')
            ;
        }

        $num = 2;
        $objPHPExcel->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        foreach ($list as $k => $v) {
            //print_r($v);exit;
            //ECHO $v[13];EXIT;
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $num, "'". $v[1]."'", \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('B' . $num, "'". substr($v[3],0,8)."'", \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('C' . $num,mb_convert_encoding($v[6],'UTF-8',mb_detect_encoding($v[6],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('D' . $num, $v[8], \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('E' . $num, $v[17], \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('F' . $num,  mb_convert_encoding($v[16],'UTF-8',mb_detect_encoding($v[16],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('G' . $num, '1', \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('H' . $num, '', \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('I' . $num, '', \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('J' . $num, mb_convert_encoding($v[5],'UTF-8',mb_detect_encoding($v[5],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('K' . $num, mb_convert_encoding($v[6],'UTF-8',mb_detect_encoding($v[6],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('L' . $num, mb_convert_encoding($v[12],'UTF-8',mb_detect_encoding($v[12],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('M' . $num, '', \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('N' . $num, '', \PHPExcel_Cell_DataType::TYPE_STRING);
//           // $objPHPExcel->getActiveSheet()->setCellValueExplicit('0' . '', \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('P' . $num,"'".$v[15]."'", \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('Q' . $num,"'".$v[2]."'", \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('R' . $num,mb_convert_encoding($v[6],'UTF-8',mb_detect_encoding($v[6],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))), \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('S' . $num,$v[10], \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('T' . $num,$v[11], \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('U' . $num,"'". $v[18]."'", \PHPExcel_Cell_DataType::TYPE_STRING);
//            $objPHPExcel->getActiveSheet()->setCellValueExplicit('V' . $num,"'".substr($v[19],0,8)."'", \PHPExcel_Cell_DataType::TYPE_STRING);


            //$color =  $this->getExpColor($v[17]);
            //$date = strtotime($v[1]);
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $num, $v['inner_order_sn'])
                ->setCellValue('B' . $num, date("Y-m-d H:i:s"))
                ->setCellValue('C' . $num,  $v['coupon_sn'])
                ->setCellValue('D' . $num, $v['sequence']);
            if($status>0)
            {
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('E' . $num, $v['out_order_sn'])
                    ->setCellValue('F' . $num, $v['status']);
            }

//                ->setCellValue('E' . $num, $v[17])
//                ->setCellValue('F' . $num, mb_convert_encoding($v[16],'UTF-8',mb_detect_encoding($v[16],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))))
//                ->setCellValue('G' . $num, '1')
//                ->setCellValue('H' . $num, '')
//                ->setCellValue('I' . $num, '')
//                ->setCellValue('J' . $num, mb_convert_encoding($v[5],'UTF-8',mb_detect_encoding($v[5],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))))
//                ->setCellValue('K' . $num, mb_convert_encoding($v[6],'UTF-8',mb_detect_encoding($v[6],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))))
//                ->setCellValue('L' . $num, mb_convert_encoding($v[12],'UTF-8',mb_detect_encoding($v[12],array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'))))
//                ->setCellValue('M' . $num, '')
//                ->setCellValue('N' . $num, '')

            $objPHPExcel->getActiveSheet()
                ->getStyle('B' . $num)
                ->getNumberFormat()
                ->setFormatCode("@");
            $num ++;
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->setTitle('订单列表');
        $objPHPExcel->setActiveSheetIndex(0);
        return $objPHPExcel;
    }
}
