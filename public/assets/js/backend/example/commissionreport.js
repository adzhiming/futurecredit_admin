define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {
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
                    index_url: 'example/commissionreport/index',
                    add_url: 'example/commissionreport/add',
                    edit_url: 'example/commissionreport/edit',
                    del_url: 'example/commissionreport/del',
                    multi_url: 'example/commissionreport/multi',
                    table: 'commissionreport'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'phone', title: __('手机号码')},
                        {field: 'name', title: __('姓名')},
                       // {field: 'confirm_time', title: __('日期'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'card_cnt', title: __('开卡总数')},                     
                        {field: 'card_comm', title: __('信用卡佣金')},
                        {field: 'loan_price', title: __('贷款总金额')},
                        {field: 'loan_comm', title: __('贷款佣金')},                                        
                        //{field: 'operate', title: __('Operate'), events: Table.api.events.operate, formatter: Table.api.formatter.commiss}
                    ]
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                thumb: function (value, row, index) {
                    if (row.mimetype.indexOf("image") > -1) {
                        var style = row.storage == 'upyun' ? '!/fwfh/120x90' : '';
                        return '<a href="' + row.fullurl + '" target="_blank"><img src="' + row.fullurl + style + '" alt="" style="max-height:90px;max-width:120px"></a>';
                    } else {
                        return '<a href="' + row.fullurl + '" target="_blank">' + __('None') + '</a>';
                    }
                },
                url: function (value, row, index) {
                    return '<a href="' + row.fullurl + '" target="_blank" class="label bg-green">' + value + '</a>';
                },
            }
        }

    };
    return Controller;
});