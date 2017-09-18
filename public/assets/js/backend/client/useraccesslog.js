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
                    index_url: 'client/useraccesslog/index',
                    add_url: 'client/useraccesslog/add',
                    edit_url: 'client/useraccesslog/edit',
                    del_url: 'client/useraccesslog/del',
                    multi_url: 'client/useraccesslog/multi',
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
                        {field: 'user_name', title: __('真实姓名'), operate: false},
                        {field: 'agent_name', title: __('代理名')},
                        {field: 'user_phone', title: __('用户电话')},
                        {field: 'card_name', title: __('信用卡名')},
                        {field: 'loan_name', title: __('贷款业务')},
                        {field: 'login_ip', title: __('登录ip'), operate: false, align: 'left', formatter: Table.api.formatter.url},
                        {field: 'remark', title: __('备注'), operate: false},
                        {field: 'type', title: __('状态'), operate: false, align: 'center', formatter: Controller.api.formatter.menu},
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
            formatter: {
                title: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },
                name: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },
                menu: function (value, row, index) {

                    return value==1 ? "<span class='btn btn-info' >" + __('登录') + "</span>" : value==2 ?"<span class='btn btn-info' >" + __('跳转信用卡申请页面') + "</span>":"<span class='btn btn-info' >" + __('跳转贷款申请页面') + "</span>";
                },

                icon: function (value, row, index) {
                    return '<i class="' + value + '"></i>';
                },
                subnode: function (value, row, index) {
                    return '<a href="javascript:;" data-id="' + row['id'] + '" data-pid="' + row['pid'] + '" class="btn btn-xs '
                        + (row['haschild'] == 1 ? 'btn-success' : 'btn-default disabled') + ' btn-node-sub"><i class="fa fa-sitemap"></i></a>';
                }
            },
            bindevent: function () {
                var iconlist = [];
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', ".btn-search-icon", function () {
                    if (iconlist.length == 0) {
                        $.get(Config.site.cdnurl + "/assets/libs/font-awesome/less/variables.less", function (ret) {
                            var exp = /fa-var-(.*):/ig;
                            var result;
                            while ((result = exp.exec(ret)) != null) {
                                iconlist.push(result[1]);
                            }
                            Layer.open({
                                type: 1,
                                area: ['460px', '300px'], //宽高
                                content: Template('chooseicontpl', {iconlist: iconlist})
                            });
                        });
                    } else {
                        Layer.open({
                            type: 1,
                            area: ['460px', '300px'], //宽高
                            content: Template('chooseicontpl', {iconlist: iconlist})
                        });
                    }
                });
                $(document).on('click', '#chooseicon ul li', function () {
                    $("input[name='row[icon]']").val('fa fa-' + $(this).data("font"));
                    Layer.closeAll();
                });
                $(document).on('keyup', 'input.js-icon-search', function () {
                    $("#chooseicon ul li").show();
                    if ($(this).val() != '') {
                        $("#chooseicon ul li:not([data-font*='" + $(this).val() + "'])").hide();
                    }
                });
            }
        }
    };
    return Controller;
});