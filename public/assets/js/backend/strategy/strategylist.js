define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
/*
	$('.draftbox').click(function(){
		
		 $.ajax({
	          url: 'strategy/strategylist',
	          type: 'post',
	          dataType: 'json',
	          data:'draftbox=1' ,
	          success: function (ret) {
	        
	             
	          }
	      });
	});*/
	$(function(){
		$('body').on('blur','.datetimePicker1,.datetimePicker2',function(){
			
	          if($(".datetimePicker2").val()!="" && $(".datetimePicker2").val()<$(".datetimePicker1").val()){
	              $(".datetimePicker1").val($(".datetimePicker2").val())
	          }
		});
		})


    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'strategy/strategylist/index',
                    add_url: 'strategy/strategylist/add',
                    edit_url: 'strategy/strategylist/edit',
                    del_url: 'strategy/strategylist/del',
                    multi_url: 'strategy/strategylist/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'logo', title: __('Image'), formatter: Table.api.formatter.image, operate: true},
                        {field: 'title', title: __('标题')},
                        {field: 'click', title: __('点击率')},
                        {field: 'type', title: __('攻略类型')},
                        {field: 'create_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'operate', title: __('Operate'), events: Table.api.events.strategy, formatter: Table.api.formatter.strategyedit}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});