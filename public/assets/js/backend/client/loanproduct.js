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
                    index_url: 'client/loanproduct/index',
                    add_url: 'client/loanproduct/add',
                    edit_url: 'client/loanproduct/edit',
                    details_url: 'client/loanproduct/details',
                    del_url: 'client/loanproduct/del',
                    recovery_url: 'client/loanproduct/recovery',
                    multi_url: 'client/loanproduct/multi',
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
                        {field: 'loan_name', title: __('贷款公司')},
                        {field: 'name', title: __('Name')},
                        {field: 'product_logo', title: __('Image'), formatter: Table.api.formatter.image, operate: false},

                        {field: 'unit_rate', title: __('期限单位'), operate: false, align: 'center', formatter: Controller.api.formatter.menu2},
                        {field: 'interest_rate', title: __('利率单位'), operate: false, align: 'center', formatter: Controller.api.formatter.check},
                        {field: 'repayment_cycle_range', title: __('周期范围'), operate: false, align: 'center', formatter: Controller.api.formatter.menu3},

                        {field: 'interest_free_days', title: __('免息天数'), operate: false},


                        {field: 'product_style', title: __('跳转类型'), operate: false, align: 'center', formatter: Controller.api.formatter.menu1},
                        {field: 'product_url', title: __('链接'), operate: false,formatter: Table.api.formatter.url1},
                        {field: 'is_deleted', title: __('使用状态'), operate: false, align: 'center', formatter: Controller.api.formatter.menu},
                        {field: 'add_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
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
        details: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                title: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },

                // check: function (value, row, index) {
                //     return row.price_type==1 ?  value  : row.price_type==2 ?  (value*100)/100 + "%" : row.price_type==3 ? value : value;
                // },

                check: function (value, row, index) {
                    return   value==""?'':(value*100)/100 + "%";
                },

                name: function (value, row, index) {
                    return !row.ismenu ? "<span class='text-muted'>" + value + "</span>" : value;
                },
                menu: function (value, row, index) {
                    return value==0 ? "<span class='btn btn-info' >" + __('正常使用') + "</span>" :"<span class='btn btn-info'>" +  __('已被删除') + "</span>";

                },

                menu1: function (value, row, index) {
                    return value==1 ?  __('普通链接') :  __('接口链接');
                },

                menu2: function (value, row, index) {
                    return value==1 ?  __('月') : value==2 ?  __('周') : value==3 ?  __('日') :'';
                },

                menu3: function (value, row, index) {
                    return row.unit_rate==1 ? value + "(月)" : row.unit_rate==2 ? value + "(周)" : row.unit_rate==3 ? value + "(日)" :'';
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