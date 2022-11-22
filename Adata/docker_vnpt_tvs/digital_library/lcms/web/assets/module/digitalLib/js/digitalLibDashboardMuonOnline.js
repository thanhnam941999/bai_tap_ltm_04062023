Vue.component('treeselect', VueTreeselect.Treeselect);
var dashboardMuonOnlineList;
$(document).ready(function(){
  dashboardMuonOnlineList = new Vue({
    el: "#dashboardMuonOnlineList",
    data() {
      return{
        loading: false,
        loaded: false,
        ref_frame_type: ref_frame_type ? ref_frame_type : null,
        ref_frame_id: ref_frame_id  ? ref_frame_id : null,
        typeSelectedId: null,
        selectFileTypeBeforeCreate: false,
        showBtnCreate: true,
        pager: {
          current_page: 0,
          total_page: 0,
          per_page: 20,
          offset: 0,
          rows: []
        },
        filter:{
          donvi_id:'',
          s:''
      },
        itemRowByType: {
          type: null,
          name: null,
          rows: []
        },
        mFilter: {
          "tu_ngay" : "",
          "den_ngay" : "",
        },
        filterEvent:{
          "tu_ngay" : "",
          "den_ngay" : "",
        },
        pagerDynamic: {
          current_page: 0,
          total_page: 0,
          per_page: 20,
          offset: 0,
          rows: []
        },

        error: "",
        creators: [],
        documents: [],
        AppCfg: AppCfg,
        docRowSelected: null,
        boxCreateNewDocument: '',
        boxManageDynamic: '',
        boxListRefByType: '',
        cfg: App.cfg,
      }
    },
    watch:{
    },
    methods:{
      moment: function (date, format) {
        return moment(date, format);
      },

      //Hàm lấy danh sách thống kê tài liệu theo điều kiên lọc
      getReportDocumentList: function(page=1, reset = 2) {
        var me = this
        var dataPost = me.mFilter
        me.filterEvent.tu_ngay = $("#tu_ngay").val();
        me.filterEvent.den_ngay = $("#den_ngay").val();
        me.mFilter.tu_ngay = $("#tu_ngay").val();
        me.mFilter.den_ngay = $("#den_ngay").val();
        me.error = "";

        if(me.filterEvent.tu_ngay && me.filterEvent.den_ngay){
          var tu = new Date(me.filterEvent.tu_ngay).getTime();
          var den = new Date(me.filterEvent.den_ngay).getTime();

          if(tu > den ){
            me.error = "Thời gian đến không phù hợp ";
          }else{
            me.error = "";
          }
        }else{
          me.error = "Thời gian đến không phù hợp ";
        }

        me.loading = true;
        me.loaded = false;

        axios.post(`/module/digitalLib/service/dashboard/getBaoCaoMuonOnline?page=${page}`, dataPost)
            .then(res => {
          
                if (res.status == 200 && res.data) {
                  me.pager = {...me.pager, ...res.data.data}
                }

                setTimeout(function() {
                  me.loading = false;
                  me.loaded = true;
                }, 100)
                
            })
            .catch(function (err) {
                  me.loading = false;
                  me.loaded = true;
                  App.showConfirm('Có lỗi xảy ra, vui lòng thử lại sau', function () {})
            })
      },

      //Hàm gán giá trị vào exel cho chức năng xuất exel
      openDownload:function () {
        var me = this
        var dataPost = me.mFilter
        me.filterEvent.tu_ngay = $("#tu_ngay").val();
        me.filterEvent.den_ngay = $("#den_ngay").val();
        me.mFilter.tu_ngay = $("#tu_ngay").val();
        me.mFilter.den_ngay = $("#den_ngay").val();
        me.error = "";

        if(me.filterEvent.tu_ngay && me.filterEvent.den_ngay){
          var tu = new Date(me.filterEvent.tu_ngay).getTime();
          var den = new Date(me.filterEvent.den_ngay).getTime();

          if(tu > den ){
            me.error = "Thời gian đến không phù hợp ";
          }else{
            me.error = "";
          }
        }else{
          me.error = "Thời gian đến không phù hợp ";
        }
        console.log(dataPost["[object Object]"]);
        console.log(dataPost);
        
        openDownloadFile('/module/digitalLib/service/dashboard/exportList?cond='+dataPost);
      },
      
      //Hàm lấy phần tử  cho lựa chọn điều kiện lọc
      getRefBorrowDefault: function(type=null) {
      var me = this
      let dataPost = {}
      if (type) {
        let treeNameInputValue = $(`#${type}_id .vue-treeselect__input`).val()
        if (treeNameInputValue) {
          dataPost.filter_by_type = {
            type: type,
            value: treeNameInputValue
          }
        }
      }
      axios.post(`/module/digitalLib/service/dashboard/getDieuKienLocMuonOnline`, dataPost).then(res => {
        if (res.status == 200 && res.data.success) {
          let dataRes = res.data.data
          me.creators = dataRes.creators
          me.documents = dataRes.documents
          me.genres = dataRes.genres
        }
        
      });
      
    },
    },
    created: function (){
      this.getRefBorrowDefault()
      // this.reloadLists(1)
    },
    components:{
    },
    mounted : function (){
      $("#StartDateReport").datetimepicker({
        format: 'DD-MM-YYYY',
        viewMode: "days",
        locale: 'vi',
      });
      $("#EndDateReport").datetimepicker( {
        format: 'DD-MM-YYYY',
        viewMode: "days",
        locale: 'vi',
      });
    }
  })
});
