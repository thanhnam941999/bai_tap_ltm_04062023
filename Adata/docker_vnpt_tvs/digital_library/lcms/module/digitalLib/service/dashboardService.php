<?php

/**
 * author: NguyenThanhNam
 */

namespace module\digitalLib\service;

use module\digitalLib\model\dashboardModel;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use lib\auth\AccessControl;
use lib\core\BaseService;
use lib\helper\FileHelper;
use model\core\CoreConfigSystem;
use model\core\CoreDomain;
use model\core\CoreDonViModel;
use model\core\CoreSite;
use module\coc\model\CocHuyen;

class dashboardService extends \lib\core\BaseService
{
    protected $dModel;

    public function __construct()
    {
      parent::__construct();
      $this->dModel = new dashboardModel();
    }
    
    /**
    * content: get danh sach tài khoản đã tạo
    * theo các tháng phục vụ module thống kê
    * author: Nguyễn Thành Nam 
    * ====================================
    * 
    */
    public function SoTaiKhoan($start, $limit, $options = [])
    {
      $siteId = \phpviet::getSiteId();
      $donviId = \phpviet::getCurrentDonviId();
      $sWhere = "cu.status = 1 AND cu.site_id='".$siteId."' AND cu.donvi_id='".$donviId."'";
      $params = [];

      if(!empty($options['from_date_create']))
      {
        $replacement = '01-';
        $options['from_date_create'] = substr_replace($options['from_date_create'], $replacement, 0, 0);
        $options['from_date_create'] = date("Y-m-d", strtotime($options['from_date_create']));
        $sWhere .= " AND cu.created_date >= :from_date_create";
        $params['from_date_create'] = $options['from_date_create'];
      }

        $rest = substr($options['to_date_create'], -7, 2);

      if(!empty($options['to_date_create']))
      {
        if ($rest == "02") 
        {
            $replacement = '28-';
            $options['to_date_create'] = substr_replace($options['to_date_create'], $replacement, 0, 0);
            $options['to_date_create'] = date("Y-m-d", strtotime($options['to_date_create']));
        } 
        else 
        {
            $replacement = '30-';
            $options['to_date_create'] = substr_replace($options['to_date_create'], $replacement, 0, 0);
            $options['to_date_create'] = date("Y-m-d", strtotime($options['to_date_create']));
        }

        $sWhere .= " AND cu.created_date <= :to_date_create";
        $params['to_date_create'] = $options['to_date_create'];
      }

      $data = self::DBSLAVE()->select("cu.*")
                           ->from(core_user, "cu")
                           ->where($sWhere)
                           ->order("cu.created_date desc")
                           ->limit($start, $limit)
                           ->getRows($params);

      return $data;

    }
    public function getReportMemberCreated()
    {
      $data = self::getArrRequest('formData',[]);
      $option['from_date_create'] = !empty($data['from_date']) ? $data['from_date'] : '01-'.date("Y");
      $option['to_date_create'] = !empty($data['to_date']) ? $data['to_date'] : date("m-Y");
      $rows = $this->SoTaiKhoan(0,10000,$option);

      if(!empty($option['from_date_create']))
      {
          $replacement = '01-';
          $option['from_date_create'] = substr_replace($option['from_date_create'], $replacement, 0, 0);
          $option['from_date_create'] = date("Y-m-d", strtotime($option['from_date_create']));
      }
      $rest = substr($option['to_date_create'], -7, 2);

      if(!empty($option['to_date_create']))
      {
          if ($rest == "02") 
          {
              $replacement = '28-';
              $option['to_date_create'] = substr_replace($option['to_date_create'], $replacement, 0, 0);
              $option['to_date_create'] = date("Y-m-d", strtotime($option['to_date_create']));
          } 
          else 
          {
              $replacement = '30-';
              $option['to_date_create'] = substr_replace($option['to_date_create'], $replacement, 0, 0);
              $option['to_date_create'] = date("Y-m-d", strtotime($option['to_date_create']));
          }
        }
      $listDayOfRows = [];
      $listMonthOfRows = [];
      $year = (int)date("Y", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
      $i= date("m", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
      $i -=1;
      $data = [];
      $result = [];
      $YFrom = date("Y", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
      $YTo = date("Y", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
      $ito=date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
      $ito -=1;
      $ito+=13;

      if (!empty($rows)) 
      {
          foreach ($rows as $value) 
          {
              $listDayOfRows[] = strtotime(date("d-m-Y", strtotime($value['created_date'])));
          }
          sort($listDayOfRows);

          foreach ($listDayOfRows as $value) 
          {
              $listMonthOfRows[] = date("m", $value) . "/" . (int)date("Y", $value);
          }
      
      $data = array_count_values($listMonthOfRows);
        } else
        $data = [];
      $listMonthOfRowsDefault = [];
          
      if($YFrom == $YTo){
        for ($i=$i +1;
            $i <= date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
            $i++)
        {
          if($i<10){
            $i="0".$i; 
          }

          $listMonthOfRowsDefault += [$i."/".$year => 0];
        }
      }else{
        for($i=$i +1;
            $i <= $ito;
            $i++)
        {
          if($i<10){
            $i="0".$i; 
          }
          if($i == 13){
            $i = 1;
            $ito = date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create'])))); 
            $year +=1;
          }
        $listMonthOfRowsDefault += [$i."/".$year => 0];
        }
      }

        $result = array_merge($listMonthOfRowsDefault, $data);
    
      $this->success("OK",$result);
  } 
    public function getBaoCaoTruyCapBST()
    {
      $page = self::getIntRequest('page');
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getBaoCaoTruyCapBST($conds, $page);
      $this->success('Thành công', $data);
    }
    public function getDieuKienLocBST() 
    {
      $docId = self::getStrRequest('doc_id');
      $untiTree = self::getStrRequest('untiTree', null);
      $jsonInput = file_get_contents('php://input');
      $jsonInputArr = json_decode($jsonInput, true);
      $data = $this->dModel->getDieuKienLocBST($docId, $jsonInputArr, $untiTree ? null : true);
      $this->success('Thành công', $data);
    }
    public function getDieuKienLocMuonOnline() 
    {
        $docId = self::getStrRequest('doc_id');
        $untiTree = self::getStrRequest('untiTree', null);
        $jsonInput = file_get_contents('php://input');
        $jsonInputArr = json_decode($jsonInput, true);
        $data = $this->dModel->getDieuKienLocMuonOnline($docId, $jsonInputArr, $untiTree ? null : true);
        $this->success('Thành công', $data);
    }
    public function getBaoCaoMuonOnline()
    {
        $page = self::getIntRequest('page');
        $jsonInput = file_get_contents('php://input');
        $conds = json_decode($jsonInput, true);
        $data = $this->dModel->GetBaoCaoMuonOnline($conds, $page);
        $this->success('Thành công', $data);
      
    }
    public function getDocumentList() 
    {
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getTypeDocumentList($conds);
      $this->success('Thành công', $data);
    }
    public function getBaoCaoTruyCapAnPham()
    {
        $page = self::getIntRequest('page');
        $jsonInput = file_get_contents('php://input');
        $conds = json_decode($jsonInput, true);
        $data = $this->dModel->getBaoCaoTruyCapAnPham($conds, $page);
        $this->success('Thành công', $data);
    }
    public function getDieuKienLocTruyCapAnPham() 
    {
        $docId = self::getStrRequest('doc_id');
        $untiTree = self::getStrRequest('untiTree', null);
        $jsonInput = file_get_contents('php://input');
        $jsonInputArr = json_decode($jsonInput, true);
        $data = $this->dModel->getDieuKienLocTruyCapAnPham($docId, $jsonInputArr, $untiTree ? null : true);
        $this->success('Thành công', $data);
    }
    /**
    *  get Report Document Created
    * author: Nguyen Thanh Nam 99
    * =============================================
    * call by digitalLibDashboardList.js
    */
    public function SoTaiLieu($start, $limit, $options = [])
    {
        $sWhere = 'cu.site_id = :site_id AND cu.donvi_id = :donvi_id AND cu.status !=3';
        $params = 
        [
            'site_id' => \phpviet::getSiteId(),
            'donvi_id'=>\phpviet::getCurrentDonviId(),
        ];
        $filter = \lib\auth\AccessControl::filterDonviForBackendV2('dv');

        if(!empty($filter))
        {
            $sWhere .= $filter['condition'];
            $params = array_merge($params, $filter['params']);
        }

        if(!empty($options['from_date_create']))
        {
            $replacement = '01-';
            $options['from_date_create'] = substr_replace($options['from_date_create'], $replacement, 0, 0);
            $options['from_date_create'] = date("Y-m-d", strtotime($options['from_date_create']));
            $sWhere .= " AND cu.created_date >= :from_date_create";
            $params['from_date_create'] = $options['from_date_create'];
        }
        $rest = substr($options['to_date_create'], -7, 2);

        if(!empty($options['to_date_create']))
        {
            if ($rest == "02") 
            {
                $replacement = '28-';
                $options['to_date_create'] = substr_replace($options['to_date_create'], $replacement, 0, 0);
                $options['to_date_create'] = date("Y-m-d", strtotime($options['to_date_create']));
            } 
            else 
            {
                $replacement = '30-';
                $options['to_date_create'] = substr_replace($options['to_date_create'], $replacement, 0, 0);
                $options['to_date_create'] = date("Y-m-d", strtotime($options['to_date_create']));
            }

            $sWhere .= " AND cu.created_date <= :to_date_create";
            $params['to_date_create'] = $options['to_date_create'];
        }
        
        $data = self::DB()->select("cu.*")
                        ->from('tvs_document', "cu")
                        ->leftjoin(core_user, "u", "u.id = cu.created_user")
                        ->innerjoin(core_don_vi, "dv", "dv.id = cu.donvi_id")
                        ->where($sWhere)
                        ->order("cu.created_date desc")
                        ->limit($start, $limit)
                        ->getRows($params);

        return $data;
    }
    /***
     * Thực hiện lấy ra số lượng tài liệu tải lên theo từng tháng
     * (sau bổ sung: tháng nào không có thì thống kê tháng đó vẫn hiện ra 0)
     */
    public function getReportDocumentCreated()
    {
        $data = self::getArrRequest('formData',[]);
        $option['from_date_create'] = !empty($data['from_date']) ? $data['from_date'] : '01-'.date("Y");
        $option['to_date_create'] = !empty($data['to_date']) ? $data['to_date'] : date("m-Y");
        $rows = $this->SoTaiLieu(0,10000,$option);

        if(!empty($option['from_date_create']))
        {
            $replacement = '01-';
            $option['from_date_create'] = substr_replace($option['from_date_create'], $replacement, 0, 0);
            $option['from_date_create'] = date("Y-m-d", strtotime($option['from_date_create']));
        }
        $rest = substr($option['to_date_create'], -7, 2);

        if(!empty($option['to_date_create']))
        {
            if ($rest == "02") 
            {
                $replacement = '28-';
                $option['to_date_create'] = substr_replace($option['to_date_create'], $replacement, 0, 0);
                $option['to_date_create'] = date("Y-m-d", strtotime($option['to_date_create']));
            } 
            else 
            {
                $replacement = '30-';
                $option['to_date_create'] = substr_replace($option['to_date_create'], $replacement, 0, 0);
                $option['to_date_create'] = date("Y-m-d", strtotime($option['to_date_create']));
            }
          }
        $listDayOfRows = [];
        $listMonthOfRows = [];
        $year = (int)date("Y", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
        $i= date("m", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
        $i -=1;
        $data = [];
        $result = [];
        $YFrom = date("Y", strtotime(date("d-m-Y", strtotime($option['from_date_create']))));
        $YTo = date("Y", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
        $ito=date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
        $ito -=1;
        $ito+=13;

        if (!empty($rows)) 
        {
            foreach ($rows as $value) 
            {
                $listDayOfRows[] = strtotime(date("d-m-Y", strtotime($value['created_date'])));
            }
            sort($listDayOfRows);

            foreach ($listDayOfRows as $value) 
            {
                $listMonthOfRows[] = date("m", $value) . "/" . (int)date("Y", $value);
            }
        
        $data = array_count_values($listMonthOfRows);
          } else
          $data = [];
        $listMonthOfRowsDefault = [];
            
        if($YFrom == $YTo){
          for ($i=$i +1;
              $i <= date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create']))));
              $i++)
          {
            if($i<10){
              $i="0".$i; 
            }
  
            $listMonthOfRowsDefault += [$i."/".$year => 0];
          }
        }else{
          for($i=$i +1;
              $i <= $ito;
              $i++)
          {
            if($i<10){
              $i="0".$i; 
            }
            if($i == 13){
              $i = 1;
              $ito = date("m", strtotime(date("d-m-Y", strtotime($option['to_date_create'])))); 
              $year +=1;
            }
          $listMonthOfRowsDefault += [$i."/".$year => 0];
          }
        }

          $result = array_merge($listMonthOfRowsDefault, $data);
      
        $this->success("OK",$result);
    } 
    /**
     * content: get member list
     * author: ndhung88@vnpt.vn
     * ====================================
     * return boolean
    */
    public function getMemberList() 
    {
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getMemberList($conds);
      $this->success('Thành công', $data);
    }

    public function getMemberDetailList() 
    {
      $page = self::getIntRequest('page');
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getMemberDetailList($conds, $page);
      $this->success('Thành công', $data);
    }

    public function getReaderDetailList() 
    {
      $page = self::getIntRequest('page');
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getMemberDetailList($conds, $page);
      $this->success('Thành công', $data);
    }

    public function getAdminDetailList() 
    {
      $page = self::getIntRequest('page');
      $jsonInput = file_get_contents('php://input');
      $conds = json_decode($jsonInput, true);
      $data = $this->dModel->getAdminDetailList($conds, $page);
      $this->success('Thành công', $data);
    }
    /**
     * content: get document list
     * author: ndhung88@vnpt.vn
     * ====================================
     * return boolean
    */

  public function exportList(){
    $conds =self::getStrRequest('cond');
    var_dump($conds); die();
    $res = $this ->dModel->getBaoCaoMuonOnlineExcel();
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue("A1","STT");
    $sheet->setCellValue("B1","Tên ấn phẩm");
    $sheet->setCellValue("C1","Thuộc danh mục ấn phẩm");
    $sheet->setCellValue("D1","Thể loại");
    $sheet->setCellValue("E1","Người tạo");
    $sheet->setCellValue("F1","Ngày tạo");
    $sheet->setCellValue("G1","Lượt mươn");

    if(!empty($res))
    {
        foreach ($res as $k=>$item)
        {
            $sheet->setCellValue("A".($k+2),$k+1);
            $sheet->setCellValue("B".($k+2),$item['name']);
            $sheet->setCellValue("C".($k+2),$item['category_name']);
            // $countUser = $item['siteGeneralReport']['user']['detail'];
            // $siteGeneralReport = $item['siteGeneralReport'];
            $sheet->setCellValue("D".($k+2),$item['genre_name']);
            $sheet->setCellValue("E".($k+2),$item['user_name']);
            $sheet->setCellValue("F".($k+2),$item['created_date']);
            $sheet->setCellValue("G".($k+2),$item['count_borrow']);
            // $sheet->setCellValue("H".($k+2),$siteGeneralReport['question']['count']);
        }
    }

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="bao-cao-site-sgd-pgd.xls"');
    header('Cache-Control: max-age=0');

    $writer =  new Xls($spreadsheet);
    $writer->save('php://output');
}

}
