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
                    index_url: 'bank/bankcard/index',
                    add_url: 'bank/bankcard/add',
                    edit_url: 'bank/bankcard/edit',
                    del_url: 'bank/bankcard/del',
                    multi_url: 'bank/bankcard/multi',
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
                        {field: 'bank_name', title: __('银行名')},
                        {field: 'name', title: __('类型名')},
                        {field: 'card_name', title: __('Name')},
                        {field: 'card_logo', title: __('Image'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'card_url', title: __('链接'), align: 'left', formatter: Table.api.formatter.url},
                        {field: 'card_details', title: __('简介'), operate: false},
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