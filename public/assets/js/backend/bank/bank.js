define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
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
                    index_url: 'bank/bank/index',
                    add_url: 'bank/bank/add',
                    edit_url: 'bank/bank/edit',
                    del_url: 'bank/bank/del',
                    multi_url: 'bank/bank/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'id', title: __('Id')},
                        {field: 'bank_name', title: __('Name')},
                        {field: 'bank_logo', title: __('Image'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'bank_phone', title: __('电话')},
                        {field: 'bank_url', title: __('链接'), align: 'left', formatter: Table.api.formatter.url},
                        {field: 'bank_detail', title: __('简介'), operate: false},
                        {field: 'create_time', title: __('添加时间')},
                        {field: 'operate', title: __('Operate'), events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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