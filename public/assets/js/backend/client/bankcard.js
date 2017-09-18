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
                    index_url: 'client/bankcard/index',
                    add_url: 'client/bankcard/add',
                    edit_url: 'client/bankcard/edit',
                    recovery_url: 'client/bankcard/recovery',
                    details_url: 'client/bankcard/details',
                    del_url: 'client/bankcard/del',
                    multi_url: 'client/bankcard/multi',
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
                        {field: 'name', title: __('类型名'),operate: false},
                        {field: 'card_name', title: __('卡名称')},
                        {field: 'card_logo', title: __('Image'), operate: false, formatter: Table.api.formatter.image, operate: false},
                        // {field: 'card_url', title: __('链接'), operate: false, align: 'left', formatter: Table.api.formatter.url},
                        // {field: 'displayorder', title: __('排序'), operate: false},
                        // {field: 'apply_number', title: __('申请人数'), operate: false},
                        // {field: 'follow_number', title: __('关注人数'), operate: false},

                        // {field: 'level_price', title: __('特级佣金'), operate: false,operate: false, align: 'center', formatter: Controller.api.formatter.check},
                        // {field: 'level1_price', title: __('一级佣金'), operate: false,operate: false, align: 'center', formatter: Controller.api.formatter.check},
                        // {field: 'level2_price', title: __('二级佣金'), operate: false,operate: false, align: 'center', formatter: Controller.api.formatter.check},
                        // {field: 'level3_price', title: __('三级佣金'), operate: false,operate: false, align: 'center', formatter: Controller.api.formatter.check},
                        // // {field: 'card_details', title: __('简介'), operate: false},
                        // {field: 'price_type', title: __('返佣类型'), operate: false, align: 'center', formatter: Controller.api.formatter.menu2},
                        {field: 'product_url', title: __('链接'), operate: false,align: 'center',formatter: Table.api.formatter.url1},

                        {field: 'is_recommend', title: __('是否推荐'), operate: false, align: 'center', formatter: Controller.api.formatter.check1},
                        {field: 'is_hot', title: __('是否热门'), operate: false, align: 'center', formatter: Controller.api.formatter.check2},
                        {field: 'is_deleted', title: __('状态'), operate: false, align: 'center', formatter: Controller.api.formatter.menu},
                        {field: 'create_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
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
                    return row.price_type==1 ?  value  : row.price_type==2 ?  (value*100)/100 + "%" : row.price_type==3 ? value : value;
                },

                check1: function (value, row, index) {
                    return  value==1?"是":"否";
                },

                check2: function (value, row, index) {
                    return  value==1?"是":"否";
                },
                name: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },
                menu: function (value, row, index) {
                    return value==0 ? "<span class='btn btn-info' >" + __('正常使用') + "</span>" :"<span class='btn btn-info'>" +  __('已删除') + "</span>";

                },

                menu1: function (value, row, index) {
                    return value==1 ?  __('普通链接') : __('接口链接');
                },

                menu2: function (value, row, index) {
                    return value==1 ?__('按金额'): value==2 ?  __('按比例'): __('按数量');
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