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
                    index_url: 'bank/loan/index',
                    add_url: 'bank/loan/add',
                    edit_url: 'bank/loan/edit',
                    del_url: 'bank/loan/del',
                    multi_url: 'bank/loan/multi',
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
                        {field: 'name', title: __('Name')},
                        {field: 'logo', title: __('Image'), formatter: Table.api.formatter.image, operate: true},
                        {field: 'phone', title: __('电话')},
                        {field: 'address', title: __('地址')},
                        {field: 'loan_url', title: __('链接'), align: 'left', formatter: Table.api.formatter.url},
                        {field: 'comment', title: __('简介'), operate: false},
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