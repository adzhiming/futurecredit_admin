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
                    index_url: 'order/bankcardlog/index',
                    add_url: 'order/bankcardlog/add',
                    details_url: 'order/bankcardlog/details',
                    edit_url: 'order/bankcardlog/edit',
                    del_url: '',
                    multi_url: 'order/bankcardlog/multi',
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
                        {field: 'user_name', title: __('申请用户')},
                        {field: 'user_phone', title: __('用户电话')},
                        {field: 'card_name', title: __('信用卡')},
                        {field: 'agent_name', title: __('所属代理商')},
                        {field: 'agent_phone', title: __('代理电话')},
                        {field: 'commission', title: __('代理佣金'), operate: false},
                        {field: 'status', title: __('申请状态'), operate: false, align: 'center', formatter: Controller.api.formatter.menu1},
                        {field: 'apply_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'comment', title: __('备注'), operate: false},
                        {field: 'operate', title: __('审核操作'), events: Table.api.events.operate, formatter: Table.api.formatter.operate3},

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

                    return value==1 ? "<span class='btn btn-info' >" + __('申请中') + "</span>" : value==2 ?"<span class='btn btn-info' >" + __('申请通过') + "</span>":"<span class='btn btn-info' >" + __('申请驳回') + "</span>";
                },
                menu1: function (value, row, index) {

                    return value==1 ? "<span class='btn btn-info' >" + __('申请中') + "</span>" : value==2 ?"<span class='btn btn-info' >" + __('申请通过') + "</span>":value==3 ?"<span class='btn btn-danger' >" + __('申请驳回') + "</span>":"<span class='btn btn-info' >" + __('申请中') + "</span>";
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