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
                    index_url: 'client/loanproductprice/index',
                    add_url: 'client/loanproductprice/add',
                    edit_url: 'client/loanproductprice/edit',
                    details_url: 'client/loanproductprice/details',
                    del_url: 'client/loanproductprice/del',
                    recovery_url: 'client/loanproductprice/recovery',
                    multi_url: 'client/loanproductprice/multi',
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
                        {field: 'loan_product_name', title: __('贷款业务'), operate: false},

                        {field: 'level_price', title: __('特级佣金'), operate: false,formatter: Controller.api.formatter.check},
                        {field: 'level1_price', title: __('一级佣金'), operate: false,formatter: Controller.api.formatter.check},
                        {field: 'level2_price', title: __('二级佣金'), operate: false,formatter: Controller.api.formatter.check},
                        {field: 'level3_price', title: __('三级佣金'), operate: false,formatter: Controller.api.formatter.check},
                        // {field: 'card_details', title: __('简介'), operate: false},
                        {field: 'rule_type', title: __('规则类型'), operate: false,formatter: Controller.api.formatter.menu2},
                        // {field: 'is_deleted', title: __('状态'), operate: false,formatter: Controller.api.formatter.menu},
                        {field: 'is_deleted', title: __('状态'), operate: false,formatter: Controller.api.formatter.menu},
                        {field: 'remark', title: __('规则描述'), operate: false},
                        {field: 'create_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'update_time', title: __('修改时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'operate', title: __('Operate'), events: Table.api.events.operate, formatter: Table.api.formatter.operate2}
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

                check: function (value, row, index) {
                    return row.rule_type==1 ?  value  : row.rule_type==2 ?  (value*100)/100 + "%" : row.price_type==3 ? value : value;
                },

                name: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },
                menu: function (value, row, index) {
                    // return value==0 ? "<span class='btn btn-info' >" + __('正常使用') + "</span>" :"<span class='btn btn-info'>" +  __('已被删除') + "</span>";
                     return value==0 ? "<span class='btn btn-info' >" + __('正常使用') + "</span>" :"<span class='btn btn-info'>" +  __('已被删除') + "</span>";
                },

                menu1: function (value, row, index) {
                    return value==1 ?  __('普通链接') :  __('接口跳转');
                },

                menu2: function (value, row, index) {
                    return value==1 ? "<span class='btn btn-info' >" +__('CPA(注册数)'): value==2 ? "<span class='btn btn-info' >" + __('CPS(销售额)'): "<span class='btn btn-info' >" + __('CPA(注册数)');
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