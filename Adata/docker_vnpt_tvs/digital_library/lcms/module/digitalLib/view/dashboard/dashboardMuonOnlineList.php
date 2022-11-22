<script>
  var ref_frame_type = '<?= isset($ref_frame_type) ? "$ref_frame_type" : null; ?>';
  var ref_frame_id = '<?= isset($ref_frame_id) ? "$ref_frame_id" : null; ?>';
</script>
<?php
$this->setViewParam('pageTitle', 'Báo cáo - Thống kê số lượng mượn online ấn phẩm');
$this->registerJsFile("/assets/module/digitalLib/js/digitalLibDashboardMuonOnline.js");
?>
<div id="dashboardMuonOnlineList" class="portlet light" v-cloak>
  <div class="portlet-title">
    <div class="caption font-green-sharp col-md-10">
      <span class="caption-subject"><a href="/module/digitalLib/dashboard/dashboardmanage">BÁO CÁO</a> >> THỐNG KÊ SỐ LƯỢNG MƯỢN ONLINE ẤN PHẨM</span>
    </div>
  </div>
  <div class="portlet-body flip-scroll" >
    <div class="row" style="background: #e1e5ec; margin-left:5px; margin-right:5px;" method="get">
      <div class="row" style="margin-top: 15px; margin-bottom:15px;">
        <div class="form-group col-md-4">
          <div class="col-md-6">
              <label class="control-label">
                Từ ngày *
              </label>
              <div class="input-group date datetimepicker" id="StartDateReport" style="z-index: initial;">
                <input type="text" id="tu_ngay" placeholder="Từ ngày..." class="form-control" >
                <span class="input-group-addon">
                  <span class="glyphicon glyphicon-calendar" ></span>
                </span>
              </div>
          </div>
          <div class="col-md-6">
              <label class="control-label">
                Tới ngày *
              </label>
              <div class="input-group date datetimepicker" id="EndDateReport"  style="z-index: initial;">
                <input type="text" id="den_ngay" placeholder="Tới ngày..." class="form-control">
                <span class="input-group-addon">
                  <span class="glyphicon glyphicon-calendar"></span>
                </span>
              </div>
              <span v-if="error && error!= ''" class="required" v-cloak>{{error}}</span>
          </div>
        </div>
        <div class="form-group col-md-2" >
          <label class="control-label text-bold">Người tạo *</label>
          <treeselect v-model="mFilter.created_user" id="created_user" @search-change="getRefBorrowDefault('creator')"  :options="creators" :multiple="false" placeholder="Tất cả" no-results-text="Không có kết quả nào phù hợp!" />
        </div>
        <div class="form-group col-md-2">
          <label class="control-label text-bold">Ấn phẩm *</label>
          <treeselect v-model="mFilter.id" id="id"  :options="documents" placeholder="Tất cả" no-results-text="Không có kết quả nào phù hợp!" />
        </div>
        <div class="col-md-1 text-center">
          <label class="control-label text-bold"></label>
          <div><a  class="btn btn-warning" @click="getReportDocumentList()">Xem báo cáo</a></div>
        </div>
        <div class="col-md-1">
          <label class="control-label text-bold"></label>
          <div><a  class="btn btn-info" @click.prevent="openDownload">Xuất ra excel</a></div>
        </div>
    </div>
  </div>
    <div class="row" v-if="pager.current_page && pager.current_page != ''" v-cloak>
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-8">
          <br><br>
          <h5 class=" font-black-sharp bold ">SỞ GIÁO DỤC THÀNH PHỐ HỒ CHÍ MINH</h5>
        </div>
        <div class="col-md-4">
          <br><br>
          <h5 class=" font-black-sharp bold text-center ">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h5>
          <h6 class="font-black-sharp bold text-center">Đôc lập - tự do - hạnh phúc</h6>
          <br><br><br>
        </div>
      </div>
    </div>
    <div class="row">
        <div class="col-md-12">
        <h5 class=" font-black-sharp bold text-center ">THỐNG KÊ SỐ LƯỢNG MƯỢN ONLINE ẤN PHẨM</h5>
        <h6 v-if="filterEvent.tu_ngay && filterEvent.tu_ngay != ''" class=" font-black-sharp bold text-center " v-cloak>Từ ngày {{filterEvent.tu_ngay}} Đến ngày {{filterEvent.den_ngay}}</h6>
        <br>
        </div>
      <div class="table-responsive col-md-12">
        <table class="table table-bordered margin-top-10" id="datatable_ajax_store">
          <thead>
            <th width="2%" class="text-center">Stt</th>
            <th width="25%" class="text-center">Tên ấn phẩm</th>
            <th width="25%"class="text-center">Thuộc danh mục ấn phẩm</th>
            <th width="13%"class="text-center">Thể loại</th>
            <th width="13%"class="text-center">Người tạo</th>
            <th width="13%"class="text-center">Ngày tạo</th>
            <th width="13%" class="text-center">Lượt mượn</th>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="text-center">
                <div class="col-md-12">
                  <div class="alert alert-warning margin-bottom-0"><i class="fa fa-spin fa-spinner"></i> Đang tải dữ liệu ...</div>
                </div>
              </td>
            </tr>
            <tr v-if="loaded && pager.rows.length === 0">
              <td colspan="12" class="text-center">
                <div class="col-md-12">
                  <div class="alert alert-warning margin-bottom-0">Không tìm thấy danh sách mượn sách nào</div>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && loaded" v-for="(row,index) in pager.rows">
              <td class="text-center">{{index+1}}</td>
              <td>
                {{row.name}}
              </td>
              <td>
                {{row.category_name}}
              </td>
              <td class="text-center">
                {{row.genre_name}}
              </td>
              <td class="text-center">
              {{row.user_name}}
              </td>
              <td class="text-center">
              {{row.created_date}}<br>   
              </td>
              <td class="text-center">
              {{row.count_borrow}}
              </td>
            </tr>
          </tbody>
        </table>
        <div class="text-center" v-if="pager.total_page > 1 && !loading">
          <ul class="pagination">
            <li class="page-item" :class="{disabled:pager.current_page <= 1}">
              <a class="page-link" v-if="pager.current_page <=1">Trang trước</a>
              <a class="page-link" v-if="pager.current_page > 1" v-on:click="getReportDocumentList(pager.current_page-1)">Trang trước</a>
            </li>
            <li v-for="i in pager.total_page" v-if="i >= (pager.current_page-3) && i <= (pager.current_page+3)" v-bind:class="{ active: i == pager.current_page }"><a v-on:click="getReportDocumentList(i)">{{i}}</a></li>
            <li class="page-item" :class="{disabled:pager.current_page >= pager.total_page}">
              <a v-if="pager.current_page >= pager.total_page" class="page-link">Trang sau</a>
              <a href="#" class="page-link" v-if="pager.current_page < pager.total_page" v-on:click="getReportDocumentList(pager.current_page+1)">Trang sau</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div>
  </div>
</div>

