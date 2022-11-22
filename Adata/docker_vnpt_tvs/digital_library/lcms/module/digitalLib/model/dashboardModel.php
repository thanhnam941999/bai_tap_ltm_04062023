<?php

namespace module\digitalLib\model;

use lib\core\BaseModel;
use lib\util\TreeView;
use lib\auth\AccessControl;


class dashboardModel extends BaseModel
{
  private $limit = 10;
  private $offset = 0;
  private $mTable = 'core_user';
  private $nTable = 'tvs_document';
  private $cTable = 'tvs_collection';
  private $rTables = 
  [
    'core_profile' => 'core_profile',
    'core_user' => 'core_user',
    'core_user_profile' => 'core_user_profile',
    'core_user_has_don_vi' => 'core_user_has_don_vi',
    'core_don_vi' => 'core_don_vi',
    'core_user_site' => 'core_user_site',
    'tvs_category' => 'tvs_category',
    'tvs_document_content' => 'tvs_document_content',
    'tvs_document_content_offline' => 'tvs_document_content_offline',
    'tvs_document_lang' => 'tvs_document_lang',
    'tvs_document_has_user_borrowing' => 'tvs_document_has_user_borrowing',
    'tvs_document_meta_extend' => 'tvs_document_meta_extend',
    'tvs_document_type' => 'tvs_document_type',
    'tvs_document_has_category' => 'tvs_document_has_category',
    'tvs_document_has_genre' => 'tvs_document_has_genre',
    'tvs_doccument'=>'tvs_doccument',
    'tvs_document_has_collection'=> 'tvs_document_has_collection',
    'tvs_author' => 'tvs_author',
    'tvs_category' => 'tvs_category',
    'tvs_genre' => 'tvs_genre',
    'tvs_collection' => 'tvs_collection',
    'tvs_document_has_user_readed' => 'tvs_document_has_user_readed',
    'tvs_document_has_user_favourite' => 'tvs_document_has_user_favourite',
    'tvs_collection_has_user_favourite' => 'tvs_collection_has_user_favourite',
    'tvs_document' => 'tvs_document',
    
  ];
  private $roleTypes = [
    'sys_admin' => 'Quản trị toàn hệ thống',
    'partner_admin' => 'Quản trị đơn vị',
    'librarian' => 'Thủ thư',
    'creator' => 'Biên soạn',
    'reader' => 'Bạn đọc'
  ];
  private $tree = null;
  public function __construct()
  {
    parent::__construct();
    $this->tree = new TreeView();

  }
   /**
   * content: get data for highchart pie
   * author: nguyenthanhnam99
   * date: 6/10/2022
   * ====================================
   * return array data
   */
  public function getMemberList( $page = 1, $opts=[],$conds = [])
  {
    $res = 
    [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => [],
      'admins' =>[],
      'members' =>[],
      'readers' =>[],
      'gendersM' =>[],
      'gendersW' =>[],
    ];
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = "m.status != 3 AND m.site_id='".$siteId."' AND m.donvi_id='".$donviId."'";
    $whereArr = [];
    $conds['creator_id'] = \phpviet::getUserId();

    $selectQuery = self::DB()->select("*")
                             ->from($this->mTable, 'm')
                             ->join($this->rTables['core_user_profile'], 'p', 'p.user_id=m.id')
                             ->order('m.updated_date desc');
    $totalQuery = self::DB(true)->select('count(m.id)')
                                ->from($this->mTable, 'm');
    $totalAdminsQuery = self::DB(true)->select('count(m.id)')
                                      ->from($this->mTable, 'm')
                                      ->where('m.role_type != "reader"');
    $totalReadersQuery = self::DB(true)->select('count(m.id)')
                                       ->from($this->mTable, 'm')
                                       ->where('m.role_type="reader"');
    $totalGendersMQuery = self::DB(true)->select('count(m.id)')
                                        ->from($this->mTable, 'm')
                                        ->where('m.gender="1"');
    $totalGendersWQuery = self::DB(true)->select('count(m.id)')
                                        ->from($this->mTable, 'm')
                                        ->where('m.gender="2"');

    $conds = array_filter($conds, function ($item) 
    {
      return $item != null && trim($item) != '';
    });

    $tablesRef = [
      'user_id' => ['table' => $this->rTables['core_profile'], 'alias' => 'p', 'type' => '1n'],
      'role_type' => 'role_type',
      'status' => 'status'
    ];
    /**
     * mục đích vòng for để tạo 1 mảng các giá trị có thểfilter các điều kiện có join bảng
     */
    $arrCondsKey = [];
    $arrCondsValue = [];

    foreach ($tablesRef as $key => $item) 
    {
      if (!isset($conds[$key])) 
      {
        continue;
      }
      if (is_string($item)) 
      {
        $arrCondsKey[$key] = 'm.' . $key . '=:' . $key . '';
        $arrCondsValue[$key] = $conds[$key];
      } 
      else 
      {
        $arrCondsKey[$key] = isset($item['type']) && $item['type'] == 'mn' ? $item['alias'] . '.' . $key . '=:' . $key : 'm.' . $key . '=:' . $key;
        $arrCondsValue[$key] = $conds[$key];
        $refStr = $item['alias'] . '.id=m.' . $key;
        $selectQuery->join($item['table'], $item['alias'], $refStr);
        $totalQuery->join($item['table'], $item['alias'], $refStr);
      }
    }

    if (!empty($arrCondsKey)) 
    {
      $whereStr = implode(' AND ', $arrCondsKey) . ' AND ' . $whereStr;
      $whereArr = array_merge($whereArr, $arrCondsValue);
    }

    $selectQuery = $selectQuery->group('m.id');
    $res['rows'] = $selectQuery->where($whereStr)
                               ->getRows($whereArr);
    
    if ($res['rows'] && !empty($res['rows'])) 
    {
      $totalRows = $totalQuery->where($whereStr)
                              ->getCount($whereArr);
      $totalAdminsRows =$totalAdminsQuery->where($whereStr)
                                         ->getCount($whereArr);
      $totalGendersMRows =$totalGendersMQuery->where($whereStr)
                                             ->getCount($whereArr);
      $totalGendersWRows =$totalGendersWQuery->where($whereStr)
                                             ->getCount($whereArr);
      $totalReadersRows =$totalReadersQuery->where($whereStr)
                                           ->getCount($whereArr);
      $res['members'] = $totalRows;
      $res['admins'] = $totalAdminsRows;
      $res['readers'] = $totalReadersRows;
      $res['gendersM'] = $totalGendersMRows;
      $res['gendersW'] = $totalGendersWRows;
    }

    return $res;
  }
   /**
   * content: get data for highchart container4
   * author: nguyenthanhnam99
   * date: 6/10/2022
   * ====================================
   * return array data
   */
  public function getTypeDocumentList($conds = [])
  {
    $res = 
    [
      'row' => [],
    ];
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = '';
    $whereArr = [];
    $conds['creator_id'] = \phpviet::getUserId();
    $whereStr .= "m.site_id='".$siteId."' AND m.donvi_id='".$donviId."' AND m.status != 3";

    $fileTypes = 
    [
      'PDF' => 'pdf',
      'Audio' => 'audio',
      'Html' => 'html',
      'Word' => 'word',
      'Excel' => 'excel',
      'Powerpoint' => 'powerpoint',
      'Epub' => 'epub',
      'Mobile' => 'mobile',
      'Image' => 'image',
      'Video' => 'video',
    ];

    foreach ($fileTypes as $key => $item) 
    {
      $totalfileQuery = self::DB(true)->select('count(m.document_type_id)')
                                      ->from($this->nTable, 'm')
                                      ->where ('m.document_type_id like "%'.$item.'%"');
      $totalfileRows = $totalfileQuery->where($whereStr)
                                      ->getCount($whereArr);  
      $res[$key] = $totalfileRows;
    }
    
    $selectQuery = self::DB()->select("m.id")
                             ->from($this->nTable, 'm')
                             ->order('m.updated_date desc');
    $totalQuery = self::DB(true)->select('count(m.id)')
                                ->from($this->nTable, 'm');
    $conds = array_filter($conds, function($item) 
    {
      return $item != null && trim($item) != '';
    });

    $arrCondsKey = [];
    $arrCondsValue = [];
 
    if (!empty($arrCondsKey)) 
    {
      $whereStr = implode(' AND ', $arrCondsKey).' AND '.$whereStr;
      $whereArr = array_merge($whereArr, $arrCondsValue);
    } 
    // extend data
    $selectQuery = $selectQuery->group('m.id');
    $totalQuery = $totalQuery->group('m.id');
    // query data
    $res['rows'] = $selectQuery->where($whereStr)
                               ->getRows($whereArr);
    if ($res['rows'] && !empty($res['rows'])) {
    }
    return $res;
  }
    /**
   * content: show option for mFilter
   * author: nguyenthanhnam99
   * date: 12/10/2022
   * ====================================
   */
  public function getDieuKienLocBST($docId=null, $filters=[], $useTreeview = true) 
  {
    $donviId = \phpviet::getCurrentDonviId();
    $siteId = \phpviet::getCurrentDonviId();
    $andJoinDefault = ' AND _talias_.site_id="'.$siteId.'" AND _talias_.donvi_id="'.$donviId.'"';
    
    if ($docId) 
    {
      $isJoinRef = false;
      if (!$isJoinRef) 
      {
        $andJoinDefault = '';
      } 
      $creatorsSeleted = self::DB()->select('cu.*, null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->rTables['core_user'],'cu')
                                ->join($this->cTable, 'tc')
                                ->where('cu.status=1 AND cu.id=tc.created_user')
                                ->getRows(['doc_id' => $docId]);
      $collectionsSelected = self::DB()->select('tc.*,null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->cTable,'tc')
                                ->where('tc.status=1 AND tc.id=:doc_id')
                                ->getRows(['doc_id' => $docId]);
    }
  
    // creators
    $creatorName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'creator' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $creators = self::DB()->select('*, null as parent_id')
                            ->from($this->rTables['core_user'])
                            ->where('status=1 AND user_name LIKE :name')
                            ->getRows(['name' => $creatorName]);
    
    if (!empty($creatorsSeleted)) 
    {
      $creators = array_unique(array_merge($creators, $creatorsSeleted), SORT_REGULAR);
    }

    $creators = $this->tree->buildVueTree($creators);

    // documents
    $collectionName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'collection' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $collections = self::DB()->select('*, null as parent_id')
                          ->from($this->cTable)
                          ->where('status=1 AND name LIKE :name')
                          ->getRows(['name' => $collectionName]);
  
    if (!empty($collectionsSelected)) 
    {
      $collections = array_unique(array_merge($collections, $collectionsSelected), SORT_REGULAR);
    }
    
    $collections = $this->tree->buildVueTree($collections);
    
    return 
    [
      'collections' => $collections,
      'creators' => $creators,
    ];
  }
  public function getBaoCaoTruyCapBST($conds = [], $page = 1,$opts = [])
  {
    $res = 
    [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => [],
    ];
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = '';
    $whereArr = [];
    $conds['tu_ngay'] = date("Y-m-d", strtotime($conds['tu_ngay']));
    $conds['den_ngay'] = date("Y-m-d", strtotime($conds['den_ngay']));
    $page = $page && $page > 0 ? $page : 1;
    $this->offset = ($page - 1) * $this->limit;
    $nameLike = isset($conds['key_search']) ? "%".$conds['key_search']."%" : "%%";
    $whereStr .= "tc.name LIKE :name AND tc.site_id='".$siteId."' AND tc.donvi_id='".$donviId."'";
    $whereArr['name'] = $nameLike;

    if (!empty($conds['status'])){
      $whereStr .= " AND tc.status =:status";
      $whereArr['status'] = $conds['status'];
    }
    
    $whereStr .= " AND tc.created_date >= :tu_ngay";
    $whereStr .= " AND tc.created_date <= :den_ngay";
    $whereArr['tu_ngay'] = $conds['tu_ngay'];
    $whereArr['den_ngay'] = $conds['den_ngay'];
    $res['rows'] = self::DB()->select("tc.*, core_user.user_name, count(tdhc.collection_id) as count")
                      ->from($this->cTable, 'tc')
                      ->order('tc.count_view desc')
                      ->leftJoin($this->rTables['tvs_document_has_collection'],'tdhc', 'tdhc.collection_id=tc.id')
                      ->leftJoin($this->rTables['core_user'], 'core_user', 'tc.creator_id=core_user.id')
                      ->group('tc.id') 
                      ->where($whereStr)
                      ->limit($this->offset, $this->limit)
                      ->getRows($whereArr);
   
    if ($res['rows'] && !empty($res['rows'])) 
    {
      $totalRows = self::DB()->select('count(tc.id)')
      ->from($this->cTable, 'tc')
      ->leftJoin($this->rTables['core_user'], 'u', 'tc.creator_id=u.id')
      ->where($whereStr)
      ->getCount($whereArr);

      $res['total_rows'] = $totalRows;
      $res['total_page'] = ceil($totalRows/$this->limit);
    }

    $res['conds'] = $conds;

    return $res;
  }        
  public function getBaoCaoMuonOnline($conds = [], $page = 1,$opts = [])
  {
    $res = 
    [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => [],
    ];
    
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = '';
    $whereArr = [];
    $conds['tu_ngay'] = date("Y-m-d", strtotime($conds['tu_ngay']));
    $conds['den_ngay'] = date("Y-m-d", strtotime($conds['den_ngay']));
    $this->offset = ($page - 1) * $this->limit; 
    $whereStr .= "td.site_id='".$siteId."' AND td.donvi_id='".$donviId."'";
    
    $whereStr .= " AND td.created_date >= :tu_ngay";
    $whereStr .= " AND td.created_date <= :den_ngay";
    $whereArr['tu_ngay'] = $conds['tu_ngay'];
    $whereArr['den_ngay'] = $conds['den_ngay'];
    // query data
    $res['rows'] = self::DB()->select("td.*, tvs_category.name as category_name,tvs_genre.name as genre_name, core_user.user_name, count(tdhb.document_id) as count_borrow ")
                             ->from($this->nTable, 'td')
                             ->order('count_borrow desc')
                             ->leftJoin($this->rTables['tvs_document_has_user_borrowing'],'tdhb', 'tdhb.document_id=td.id')
                             ->leftJoin($this->rTables['core_user'], 'core_user', 'core_user.id = td.created_user')
                             ->leftJoin($this->rTables['tvs_document_has_category'], 'tvs_document_has_category', 'tvs_document_has_category.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_category'], 'tvs_category', 'tvs_category.id=tvs_document_has_category.category_id')
                             ->leftJoin($this->rTables['tvs_document_has_genre'], 'tvs_document_has_genre', 'tvs_document_has_genre.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_genre'], 'tvs_genre', 'tvs_genre.id=tvs_document_has_genre.genre_id')
                             ->leftJoin($this->rTables['tvs_document_has_user_borrowing'], 'tdhub', 'tdhub.document_id=td.id')
                             ->where($whereStr)
                             ->group('td.id')
                             ->limit($this->offset, $this->limit)
                             ->getRows($whereArr);
                        
    if ($res['rows'] && !empty($res['rows'])) 
    {
      $totalRows = self::DB()->select('count(td.id)')
      ->from($this->nTable, 'td')
      ->where($whereStr)
      ->getCount($whereArr);
      $res['total_page'] = ceil($totalRows/$this->limit);
    }

    $res['conds'] = $conds;

    return $res;
  }    
  public function getBaoCaoMuonOnlineExcel()
  {
    $res = [];
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = '';
    $whereArr = [];
    $whereStr .= "td.site_id='".$siteId."' AND td.donvi_id='".$donviId."'";
    // query data
    $res = self::DB()->select("td.*, tvs_category.name as category_name,tvs_genre.name as genre_name, core_user.user_name, count(tdhb.document_id) as count_borrow ")
                             ->from($this->nTable, 'td')
                             ->order('count_borrow desc')
                             ->leftJoin($this->rTables['tvs_document_has_user_borrowing'],'tdhb', 'tdhb.document_id=td.id')
                             ->leftJoin($this->rTables['core_user'], 'core_user', 'core_user.id = td.created_user')
                             ->leftJoin($this->rTables['tvs_document_has_category'], 'tvs_document_has_category', 'tvs_document_has_category.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_category'], 'tvs_category', 'tvs_category.id=tvs_document_has_category.category_id')
                             ->leftJoin($this->rTables['tvs_document_has_genre'], 'tvs_document_has_genre', 'tvs_document_has_genre.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_genre'], 'tvs_genre', 'tvs_genre.id=tvs_document_has_genre.genre_id')
                             ->leftJoin($this->rTables['tvs_document_has_user_borrowing'], 'tdhub', 'tdhub.document_id=td.id')
                             ->where($whereStr)
                             ->group('td.id')
                             ->getRows($whereArr);
    if ($res && !empty($res)) 
    {

    }

    return $res;
  }     
  public function getDieuKienLocMuonOnline($docId=null, $filters=[], $useTreeview = true) 
  {
    $donviId = \phpviet::getCurrentDonviId();
    $siteId = \phpviet::getCurrentDonviId();
    $andJoinDefault = ' AND _talias_.site_id="'.$siteId.'" AND _talias_.donvi_id="'.$donviId.'"';

    if ($docId) 
    {
      $isJoinRef = false;
      if (!$isJoinRef) 
      {
        $andJoinDefault = '';
      } 
      $creatorsSeleted = self::DB()->select('cu.*, null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->rTables['core_user'],'cu')
                                ->join($this->nTable, 'td')
                                ->where('cu.status=1 AND cu.id=td.created_user')
                                ->getRows(['doc_id' => $docId]);
      $genresSelected = self::DB()->select('g.*, null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->rTables['tvs_genre'], 'g')
                                ->join($this->rTables['tvs_document_has_genre'], 'dhg', 'g.id=dhg.genre_id')
                                ->where('g.status=1 AND dhg.document_id=:doc_id')
                                ->getRows(['doc_id' => $docId]);
      $documentsSelected = self::DB()->select('td.*,null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->nTable,'td')
                                ->where('td.status=1 AND td.id=:doc_id')
                                ->getRows(['doc_id' => $docId]);
    }
    // creators
    $creatorName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'creator' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $creators = self::DB()->select('*, null as parent_id')
                            ->from($this->rTables['core_user'])
                            ->where('status=1 AND user_name LIKE :name')
                            ->getRows(['name' => $creatorName]);

    if (!empty($creatorsSeleted)) 
    {
      $creators = array_unique(array_merge($creators, $creatorsSeleted), SORT_REGULAR);
    }

    $creators = $this->tree->buildVueTree($creators);

    // DOCUMENTS
    $documentName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'document' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $documents = self::DB()->select('*, null as parent_id')
                          ->from($this->nTable)
                          ->where('status=1 AND name LIKE :name')
                          ->getRows(['name' => $documentName]);

    if (!empty($documentsSelected)) 
    {
      $documents = array_unique(array_merge($documents, $genresSelected), SORT_REGULAR);
    }

    $documents = $this->tree->buildVueTree($documents);

    return 
    [
      'documents' => $documents,
      'creators' => $creators,
    ];
  }
  public function getBaoCaoTruyCapAnPham($conds = [], $page = 1,$opts = [])
  {
    $res = 
    [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => [],
    ];
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $whereStr = '';
    $whereArr = [];
    $conds['creator_id'] = \phpviet::getUserId();
    $conds['tu_ngay'] = date("Y-m-d", strtotime($conds['tu_ngay']));
    $conds['den_ngay'] = date("Y-m-d", strtotime($conds['den_ngay']));
    $this->offset = ($page - 1) * $this->limit;
    $whereStr .= "td.site_id='".$siteId."' AND td.donvi_id='".$donviId."'";
    $whereStr .= " AND td.created_date >= :tu_ngay";
    $whereStr .= " AND td.created_date <= :den_ngay";
    $whereArr['tu_ngay'] = $conds['tu_ngay'];
    $whereArr['den_ngay'] = $conds['den_ngay'];
    // query data
    $res['rows'] = self::DB()->select("td.*, tvs_category.name as category_name,tvs_genre.name as genre_name, core_user.user_name, td.count_view as luot_muon ")
                             ->from($this->nTable, 'td')
                             ->order('td.count_view desc')
                             ->leftJoin($this->rTables['core_user'], 'core_user', 'core_user.id = td.created_user')
                             ->leftJoin($this->rTables['tvs_document_has_category'], 'tvs_document_has_category', 'tvs_document_has_category.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_category'], 'tvs_category', 'tvs_category.id=tvs_document_has_category.category_id')
                             ->leftJoin($this->rTables['tvs_document_has_genre'], 'tvs_document_has_genre', 'tvs_document_has_genre.document_id=td.id')
                             ->leftJoin($this->rTables['tvs_genre'], 'tvs_genre', 'tvs_genre.id=tvs_document_has_genre.genre_id')
                             ->leftJoin($this->rTables['tvs_document_has_user_borrowing'], 'tvs_document_has_user_borrowing', 'tvs_document_has_user_borrowing.document_id=td.id')
                             ->group('td.id')
                             ->where($whereStr)
                             ->limit($this->offset, $this->limit)
                             ->getRows($whereArr);


    if ($res['rows'] && !empty($res['rows'])) 
    {
      $totalRows = self::DB()->select("count(td.id)")
                            ->from($this->nTable, 'td')
                            ->where($whereStr)
                            ->getCount($whereArr);

      $res['total_page'] = ceil($totalRows/$this->limit);
    }

    $res['conds'] = $conds;

    return $res;
  }        
   /**
   * content: show option for mFilter
   * author: nguyenthanhnam99
   * date: 12/10/2022
   * ====================================
   */
  public function getDieuKienLocTruyCapAnPham($docId=null, $filters=[], $useTreeview = true) 
  {
    $donviId = \phpviet::getCurrentDonviId();
    $siteId = \phpviet::getCurrentDonviId();
    $andJoinDefault = ' AND _talias_.site_id="'.$siteId.'" AND _talias_.donvi_id="'.$donviId.'"';

    if ($docId) 
    {
      $isJoinRef = false;
      if (!$isJoinRef) 
      {
        $andJoinDefault = '';
      } 
      $creatorsSeleted = self::DB()->select('cu.*, null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->rTables['core_user'],'cu')
                                ->join($this->nTable, 'td')
                                ->where('cu.status=1 AND cu.id=td.created_user')
                                ->getRows(['doc_id' => $docId]);
      $genresSelected = self::DB()->select('g.*, null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->rTables['tvs_genre'], 'g')
                                ->join($this->rTables['tvs_document_has_genre'], 'dhg', 'g.id=dhg.genre_id')
                                ->where('g.status=1 AND dhg.document_id=:doc_id')
                                ->getRows(['doc_id' => $docId]);
      $documentsSelected = self::DB()->select('td.*,null as parent_id, "'.$docId.'" as doc_id')
                                ->from($this->nTable,'td')
                                ->where('td.status=1 AND td.id=:doc_id')
                                ->getRows(['doc_id' => $docId]);
    }
    // creators
    $creatorName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'creator' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $creators = self::DB()->select('*, null as parent_id')
                            ->from($this->rTables['core_user'])
                            ->where('status=1 AND user_name LIKE :name')
                            ->getRows(['name' => $creatorName]);

    if (!empty($creatorsSeleted)) 
    {
      $creators = array_unique(array_merge($creators, $creatorsSeleted), SORT_REGULAR);
    }

    $creators = $this->tree->buildVueTree($creators);
    // DOCUMENTS
    $documentName = isset($filters['filter_by_type']) && $filters['filter_by_type']['type'] == 'document' ? "%".$filters['filter_by_type']['value']."%" : '%%';
    $documents = self::DB()->select('*, null as parent_id')
                          ->from($this->nTable)
                          ->where('status=1 AND name LIKE :name')
                          ->getRows(['name' => $documentName]);

    if (!empty($documentsSelected)) 
    { 
      $documents = array_unique(array_merge($documents, $genresSelected), SORT_REGULAR);
    }

    $documents = $this->tree->buildVueTree($documents);
    
    return 
    [
      'documents' => $documents,
      'creators' => $creators,
    ];
  }
  public function getDetailList($conds = [], $page = 1, $opts = [])
  {
    $res = [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => []
    ];
    $page = $page && $page > 0 ? $page : 1;
    $donviId = \phpviet::getCurrentDonviId();
    $this->offset = ($page - 1) * $this->limit;
    $sWhere = '';
    $sWhere .= 'cu.donvi_id=:donvi_id';
    $sParams = [
      'donvi_id' => $donviId,
    ];
    // query data
    $res['rows'] = self::DB()->select('cu.*,cup.phone,cup.gioi_tinh, count(tdhur.user_id) as da_doc, count(tdhuf.user_id) as doc_love,count(tchuf.user_id) as collect_love ,count(tc.created_user) as collect_created')
      ->from($this->mTable, 'cu')
      ->where($sWhere)
      ->join($this->rTables['core_user_profile'],'cup','cu.id = cup.user_id')
      ->leftJoin($this->rTables['tvs_document_has_user_readed'],'tdhur','cu.id=tdhur.user_id')
      ->leftJoin($this->rTables['tvs_document_has_user_favourite'],'tdhuf','cu.id=tdhuf.user_id')
      ->leftJoin($this->rTables['tvs_collection'],'tc','cu.id=tc.created_user')
      ->leftJoin($this->rTables['tvs_collection_has_user_favourite'],'tchuf','cu.id=tchuf.user_id')
      ->group('cu.id')
      ->limit($this->offset, $this->limit)
      ->getRows($sParams);

    if ($res['rows'] && !empty($res['rows'])) {
      $totalRows = self::DB()->select('count(cu.id)')
        ->from($this->mTable, 'cu')
        ->where($sWhere)
        ->getCount($sParams);
      $res['total_page'] = ceil($totalRows / $this->limit);
    }
    return $res;
  }
   public function getMemberDetailList($conds = [], $page = 1, $opts = [])
  {
    $res = [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => []
    ];
    $page = $page && $page > 0 ? $page : 1;
    $siteId = \phpviet::getCurrentDonviId();
    $donviId = \phpviet::getCurrentDonviId();
    $this->offset = ($page - 1) * $this->limit;
    $sWhere = '';
    $sWhere .= 'cu.donvi_id=:donvi_id AND cu.site_id=:site_id ';
    $sParams = [
      'donvi_id' => $donviId,
      'site_id' => $siteId,
    ];
    // query data
    $res['rows'] = self::DB()->select('cu.*,cup.phone,cup.gioi_tinh, count(tdhur.user_id) as da_doc, count(tdhuf.user_id) as doc_love,count(tchuf.user_id) as collect_love ,count(tc.created_user) as collect_created')
      ->from($this->mTable, 'cu')
      ->where($sWhere)
      ->join($this->rTables['core_user_profile'],'cup','cu.id = cup.user_id')
      ->leftJoin($this->rTables['tvs_document_has_user_readed'],'tdhur','cu.id=tdhur.user_id')
      ->leftJoin($this->rTables['tvs_document_has_user_favourite'],'tdhuf','cu.id=tdhuf.user_id')
      ->leftJoin($this->rTables['tvs_collection'],'tc','cu.id=tc.created_user')
      ->leftJoin($this->rTables['tvs_collection_has_user_favourite'],'tchuf','cu.id=tchuf.user_id')
      ->group('cu.id')
      ->limit($this->offset, $this->limit)
      ->getRows($sParams);

    if ($res['rows'] && !empty($res['rows'])) {
      $totalRows = self::DB()->select('count(cu.id)')
        ->from($this->mTable, 'cu')
        ->where($sWhere)
        ->getCount($sParams);
      $res['total_page'] = ceil($totalRows / $this->limit);
    }
    return $res;
  }
  public function getAdminDetailList($conds = [], $page = 1, $opts = [])
  {
    // defined response
    $res = [
      'current_page' => (int) $page,
      'per_page' => $this->limit,
      'total_page' => 0,
      'rows' => []
    ];

    $whereStr = '';
    $whereArr = [];
    $conds['creator_id'] = \phpviet::getUserId();
    $siteId = \phpviet::getSiteId();
    $donviId = \phpviet::getCurrentDonviId();
    $roleTypeUser = \phpviet::getUserRoleType();

    $this->offset = ($page - 1) * $this->limit;
    if ($roleTypeUser != SYSTEM_ADMIN || 1==1) {
      $whereStr .= "cu.site_id='".$siteId."' AND cu.donvi_id='".$donviId."'";
    }
    if (!isset($conds['role_type']) || !$conds['role_type']) {
      $whereStr .= " AND cu.role_type != 'reader'";
    } else {
      $whereStr .= " AND cu.role_type =:role_type";
      $whereArr['role_type'] = $conds['role_type'];
    }
    $virtualRoleAlias = '
      CASE WHEN (cu.role_type="sys_admin") THEN "Quản trị toàn hệ thống"
      WHEN (cu.role_type="partner_admin") THEN "Quản trị đơn vị"
      WHEN (cu.role_type="librarian") THEN "Thủ thư"
      WHEN (cu.role_type="creator") THEN "Biên soạn"
      ELSE "Bạn đọc" END
      AS role_type_alias
    ';

    $selectQuery = self::DB(true) ->select("cu.*, $virtualRoleAlias, cup.gioi_tinh, cup.phone, 
    count(td.created_user) as count_doc_created, sum(case when td.status = 1 then 1 else 0 end) as count_doc_realise, 
    count(tc.created_user) as count_collect_created,sum(case when tc.status = 1 then 1 else 0 end) as count_collect_realise")
                              ->from($this->mTable, 'cu')
                              ->leftJoin($this->rTables['core_user_profile'],'cup','cu.id = cup.user_id')
                              ->leftJoin($this->rTables['tvs_document'],'td','cu.id=td.created_user')
                              ->leftJoin($this->rTables['tvs_collection'],'tc','cu.id = tc.created_user')
                              ->group('cu.id')
                              ->limit($this->offset, $this->limit);

    $totalQuery = self::DB(true)->select('count(cu.id)')
      ->from($this->mTable, 'cu');

      $res['rows'] = $selectQuery->where($whereStr)->getRows($whereArr);
    if ($res['rows'] && !empty($res['rows'])) {
      $totalRows = $totalQuery->where($whereStr)
                              ->getCount($whereArr);
      $res['total_page'] = ceil($totalRows / $this->limit);
    }
    return $res;
  }

}