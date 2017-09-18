define(['jquery', 'bootstrap', 'moment', 'bootstrap-table', 'bootstrap-table-lang', 'bootstrap-table-mobile', 'bootstrap-table-export', 'bootstrap-table-commonsearch', 'bootstrap-table-template'], function ($, undefined, Moment) {
    var Table = {
        list: {},
        // Bootstrap-table 基础配置
        defaults: {
            url: '',
            url1: '',
            sidePagination: 'server',
            method: 'get',
            toolbar: "#toolbar",
           // search: true,
            cache: false,
            commonSearch: true,
            searchFormVisible: false,
            titleForm: '', //为空则不显示标题，不定义默认显示：普通搜索
            idTable: 'commonTable',
            showExport: true,
            exportDataType: "all",
            exportTypes: ['json', 'xml', 'csv', 'txt', 'doc', 'excel'],
            pageSize: 10,
            pageList: [10, 25, 50, 'All'],
            pagination: true,
            clickToSelect: true,
            showRefresh: false,
            locale: 'zh-CN',
            showToggle: true,
            showColumns: true,
            pk: 'id',
            sortName: 'id',
            sortOrder: 'desc',
            paginationFirstText: __("First"),
            paginationPreText: __("Previous"),
            paginationNextText: __("Next"),
            paginationLastText: __("Last"),
            mobileResponsive: true,
            cardView: true,
            checkOnInit: true,
            escape: true,
            extend: {
                index_url: '',
                add_url: '',
                edit_url: '',
                details_url:'',
                priceedit_url:'',
                del_url: '',
                recovery_url: '',
                multi_url: '',
                dragsort_url: 'ajax/weigh',
            }
        },
        // Bootstrap-table 列配置
        columnDefaults: {
            align: 'center',
            valign: 'middle',
        },
        config: {
            firsttd: 'tbody tr td:first-child:not(:has(div.card-views))',
            toolbar: '.toolbar',
            refreshbtn: '.btn-refresh',
            addbtn: '.btn-add',
            editbtn: '.btn-edit',
            detailsbtn: '.btn-details',
            priceeditbtn: '.btn-priceedit',
            recoverybtn: '.btn-recovery',
            delbtn: '.btn-del',
            multibtn: '.btn-multi',
            disabledbtn: '.btn-disabled',
            editonebtn: '.btn-editone',
            dragsortfield: 'weigh',
        },
        api: {
            init: function (defaults, columnDefaults, locales) {
                defaults = defaults ? defaults : {};
                columnDefaults = columnDefaults ? columnDefaults : {};
                locales = locales ? locales : {};
                // 写入bootstrap-table默认配置
                $.extend(true, $.fn.bootstrapTable.defaults, Table.defaults, defaults);
                // 写入bootstrap-table column配置
                $.extend($.fn.bootstrapTable.columnDefaults, Table.columnDefaults, columnDefaults);
                // 写入bootstrap-table locale配置
                $.extend($.fn.bootstrapTable.locales[Table.defaults.locale], {
                    formatCommonSearch: function () {
                        return __('Common search');
                    },
                    formatCommonSubmitButton: function () {
                        return __('Submit');
                    },
                    formatCommonResetButton: function () {
                        return __('Reset');
                    },
                    formatCommonCloseButton: function () {
                        return __('Close');
                    },
                    formatCommonChoose: function () {
                        return __('Choose');
                    }
                }, locales);
            },
            // 绑定事件
            bindevent: function (table) {
                //Bootstrap-table的父元素,包含table,toolbar,pagnation
                var parenttable = table.closest('.bootstrap-table');
                //Bootstrap-table配置
                var options = table.bootstrapTable('getOptions');
                //Bootstrap操作区
                var toolbar = $(options.toolbar, parenttable);
                //当刷新表格时
                table.on('load-error.bs.table', function (status, res) {
                    Toastr.error(__('Unknown data format'));
                });
                //当刷新表格时
                table.on('refresh.bs.table', function (e, settings, data) {
                    $(Table.config.refreshbtn, toolbar).find(".fa").addClass("fa-spin");
                });
                //当双击单元格时
                table.on('dbl-click-row.bs.table', function (e, row, element, field) {
                    $(Table.config.editonebtn, element).trigger("click");
                });
                //当内容渲染完成后
                table.on('post-body.bs.table', function (e, settings, json, xhr) {
                    $(Table.config.refreshbtn, toolbar).find(".fa").removeClass("fa-spin");
                    $(Table.config.disabledbtn, toolbar).toggleClass('disabled', true);

                    if ($(Table.config.firsttd, table).find("input[type='checkbox'][data-index]").size() > 0) {
                        // 挺拽选择,需要重新绑定事件
                        require(['drag', 'drop'], function () {
                            $(Table.config.firsttd, table).drag("start", function (ev, dd) {
                                return $('<div class="selection" />').css('opacity', .65).appendTo(document.body);
                            }).drag(function (ev, dd) {
                                $(dd.proxy).css({
                                    top: Math.min(ev.pageY, dd.startY),
                                    left: Math.min(ev.pageX, dd.startX),
                                    height: Math.abs(ev.pageY - dd.startY),
                                    width: Math.abs(ev.pageX - dd.startX)
                                });
                            }).drag("end", function (ev, dd) {
                                $(dd.proxy).remove();
                            });
                            $(Table.config.firsttd, table).drop("start", function () {
                                Table.api.toggleattr(this);
                            }).drop(function () {
                                Table.api.toggleattr(this);
                            }).drop("end", function () {
                                Table.api.toggleattr(this);
                            });
                            $.drop({
                                multi: true
                            });
                        });
                    }
                });

                // 处理选中筛选框后按钮的状态统一变更
                table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table fa.event.check', function () {
                    var ids = Table.api.selectedids(table);
                    $(Table.config.disabledbtn, toolbar).toggleClass('disabled', !ids.length);
                });

                // 刷新按钮事件
                $(toolbar).on('click', Table.config.refreshbtn, function () {
                    table.bootstrapTable('refresh');
                });
                // 添加按钮事件
                $(toolbar).on('click', Table.config.addbtn, function () {
                    var ids = Table.api.selectedids(table);
                    Fast.api.open(options.extend.add_url + "/ids" + (ids.length > 0 ? '/' : '') + ids.join(","), __('Add'));
                });
                // 批量编辑按钮事件
                $(toolbar).on('click', Table.config.editbtn, function () {
                    var ids = Table.api.selectedids(table);
                    //循环弹出多个编辑框
                    $.each(ids, function (i, j) {
                        Fast.api.open(options.extend.edit_url + "/ids/" + j, __('Edit'));
                    });
                });


                // 批量编辑按钮事件
                $(toolbar).on('click', Table.config.detailsbtn, function () {
                    var ids = Table.api.selectedids(table);
                    //循环弹出多个编辑框
                    $.each(ids, function (i, j) {
                        Fast.api.open(options.extend.detailsbtn_url + "/ids/" + j, __('Details'));
                    });
                });
                // 批量编辑按钮事件
                $(toolbar).on('click', Table.config.priceeditbtn, function () {
                    var ids = Table.api.selectedids(table);
                    //循环弹出多个编辑框
                    $.each(ids, function (i, j) {
                        Fast.api.open(options.extend.priceeditbtn_url + "/ids/" + j, __('Priceedit'));
                    });
                });


                // 批量操作按钮事件
                $(toolbar).on('click', Table.config.multibtn, function () {
                    var ids = Table.api.selectedids(table);
                    Table.api.multi($(this).data("action"), ids, table, this);
                });
                // 批量删除按钮事件
                $(toolbar).on('click', Table.config.delbtn, function () {
                    var that = this;
                    var ids = Table.api.selectedids(table);
                    var index = Layer.confirm(
                            __('Are you sure you want to delete the %s selected item?', ids.length),
                            {icon: 3, title: __('Warning'), offset: 0, shadeClose: true},
                            function () {
                                Table.api.multi("del", ids, table, that);
                                Layer.close(index);
                            }
                    );
                });


                // 批量恢复按钮事件
                // $(toolbar).on('click', Table.config.recoverybtn, function () {
                //     var that = this;
                //     var ids = Table.api.selectedids(table);
                //     var index = Layer.confirm(
                //         __('Are you sure you want to recovery the %s selected item?', ids.length),
                //         {icon: 3, title: __('Warning'), offset: 0, shadeClose: true},
                //         function () {
                //             Table.api.multi("recovery", ids, table, that);
                //             Layer.close(index);
                //         }
                //     );
                // });

                $(toolbar).on('click', Table.config.recoverybtn, function () {
                    var ids = Table.api.selectedids(table);
                    Table.api.multi($(this).data("action"), ids, table, this);
                });


                // 拖拽排序
                require(['dragsort'], function () {
                    //绑定拖动排序
                    $("tbody", table).dragsort({
                        itemSelector: 'tr',
                        dragSelector: "a.btn-dragsort",
                        dragEnd: function () {
                            var data = table.bootstrapTable('getData');
                            var current = data[parseInt($(this).data("index"))];
                            var options = table.bootstrapTable('getOptions');
                            //改变的值和改变的ID集合
                            var ids = $.map($("tbody tr:visible", table), function (tr) {
                                return data[parseInt($(tr).data("index"))][options.pk];
                            });
                            var changeid = current[options.pk];
                            var pid = typeof current.pid != 'undefined' ? current.pid : '';
                            var params = {
                                url: table.bootstrapTable('getOptions').extend.dragsort_url,
                                data: {
                                    ids: ids.join(','),
                                    changeid: changeid,
                                    pid: pid,
                                    field: Table.config.dragsortfield,
                                    orderway: options.sortOrder,
                                    table: options.extend.table
                                }
                            };
                            Fast.api.ajax(params, function (data) {
                                Toastr.success(__('Operation completed'));
                                table.bootstrapTable('refresh');
                            });
                        },
                        placeHolderTemplate: ""
                    });
                });
                $(table).on("click", "input[data-id][name='checkbox']", function (e) {
                    table.trigger('fa.event.check');
                });
                $(table).on("click", "[data-id].btn-change", function (e) {
                    e.preventDefault();
                    Table.api.multi($(this).data("action") ? $(this).data("action") : '', [$(this).data("id")], table, this);
                });

                // edit设置
                $(table).on("click", "[data-id].btn-edit", function (e) {
                    e.preventDefault();
                    Fast.api.open(options.extend.edit_url + "/ids/" + $(this).data("id"), __('Edit'));
                });
                //添加details
                $(table).on("click", "[data-id].btn-details", function (e) {
                    e.preventDefault();
                    Fast.api.open(options.extend.details_url + "/ids/" + $(this).data("id"), __('详情'));
                });


                //添加details
                $(table).on("click", "[data-id].btn-priceedit", function (e) {
                    e.preventDefault();
                    Fast.api.open(options.extend.priceedit_url + "/ids/" + $(this).data("id"), __('详情'));
                });

                $(table).on("click", "[data-id].btn-del", function (e) {
                    e.preventDefault();
                    var id = $(this).data("id");
                    var that = this;
                    var index = Layer.confirm(
                            __('Are you sure you want to delete this item?'),
                            {icon: 3, title: __('Warning'), shadeClose: true},
                            function () {
                                Table.api.multi("del", id, table, that);
                                Layer.close(index);
                            }
                    );

                });


                $(table).on("click", "[data-id].btn-recovery", function (e) {
                    e.preventDefault();
                    var id = $(this).data("id");
                    var that = this;
                    var index = Layer.confirm(
                        __('Are you sure you want to recovery this item?'),
                        {icon: 3, title: __('Warning'), shadeClose: true},
                        function () {
                            Table.api.multi("recovery", id, table, that);
                            Layer.close(index);
                        }
                    );

                });


                var id = table.attr("id");
                Table.list[id] = table;
                return table;
            },
            // 批量操作请求
            multi: function (action, ids, table, element) {
                var options = table.bootstrapTable('getOptions');
                var data = element ? $(element).data() : {};
                var url = typeof data.url !== "undefined" ? data.url : (action == "del" ? options.extend.del_url : action=="recovery"? options.extend.recovery_url:options.extend.multi_url);
                url = url + (url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + ($.isArray(ids) ? ids.join(",") : ids);
                var params = typeof data.params !== "undefined" ? (typeof data.params == 'object' ? $.param(data.params) : data.params) : '';
                var options = {url: url, data: {action: action, ids: ids, params: params}};
                Fast.api.ajax(options, function (data) {
                    Toastr.success(__('Operation completed'));
                    table.bootstrapTable('refresh');
                });
            },
            // 单元格元素事件
            events: {
                operate: {
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        Fast.api.open(options.extend.edit_url + "/ids/" + row[options.pk], __('Edit'));
                    },
                    //添加details事件
                    'click .btn-detailsone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        Fast.api.open(options.extend.details_url + "/ids/" + row[options.pk], __('详情'));
                    },
                    'click .btn-details' : function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        Fast.api.open("example/agentmanage/details?ref=addtabs"+ "&status=" + status + "&ids=" + row[options.pk]  , __('详情'));
                    },
                    //添加details事件
                    'click .btn-priceeditone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        Fast.api.open(options.extend.priceedit_url + "/ids/" + row[options.pk], __('详情'));
                    },
                    'click .btn-priceedit' : function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status');
                        Fast.api.open("example/agentmanage/priceedit?ref=addtabs"+ "&status=" + status + "&ids=" + row[options.pk]  , __('详情'));
                    },
                    'click .btn-commiss' : function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        top.location="commissionreport/details?ref=addtabs"+ "&ids=" + row[options.pk];
                    },
                   
                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                                __('Are you sure you want to delete this item?'),
                                {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                function () {
                                    var table = $(that).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("del", row[options.pk], table, that);
                                    Layer.close(index);
                                }
                        );
                    },


                    'click .btn-recoveryone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                            __('您确定要恢复吗?'),
                            {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                            function () {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("recovery", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    }

                }, 
                apply: {
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        Fast.api.open(options.extend.edit_url + "/ids/" + row[options.pk] + "?status=" + status , __('Edit'));
                    },
           /*         'click .btn-edit': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        Fast.api.open("example/agentapply/agentedit?ref=addtabs"+ "&status=" + status + "&ids=" + row[options.pk]  , __('编辑'));
                    },*/
                    'click .btn-details' : function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        Fast.api.open("example/agentapply/details?ref=addtabs"+ "&status=" + status + "&ids=" + row[options.pk]  , __('详情'));
                    },

                    'click .btn-priceedit' : function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status');
                        Fast.api.open("example/agentapply/priceedit?ref=addtabs"+ "&status=" + status + "&ids=" + row[options.pk]  , __('详情'));
                    },

                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                                __('Are you sure you want to delete this item?'),
                                {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                function () {
                                    var table = $(that).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("del", row[options.pk], table, that);
                                    Layer.close(index);
                                }
                        );
                    }
                },
                strategy:{
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        top.location ="/strategy/addstrategy/add" +"?ref=addtabs&ids=" + row[options.pk];
                    },
                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                                __('Are you sure you want to delete this item?'),
                                {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                function () {
                                    var table = $(that).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("del", row[options.pk], table, that);
                                    Layer.close(index);
                                }
                        );
                    }
                },
                theme:{
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        top.location ="addtheme/add" +"?ref=addtabs&ids=" + row[options.pk];
                    },
                    'click .btn-addcard':function(e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        Fast.api.open("theme/addtheme/addcard" +"?ref=addtabs&ids=" + row[options.pk] ,__('主题卡'));
                    },
                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                                __('Are you sure you want to delete this item?'),
                                {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                function () {
                                    var table = $(that).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("del", row[options.pk], table, that);
                                    Layer.close(index);
                                }
                        );
                    }
                },
                withdraw: {
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        var options = $(this).closest('table').bootstrapTable('getOptions');
                        var status = $(this).data('status'); 
                        if(status == 'yes')
                    	{
                        	if(confirm('确定通过审核吗？'))
                        	{
                        		window.location.href = "/wechat/commissionwithdrawal/edit" + "/ids/" + row[options.pk] + "?status=" + status , __('Edit')
                        	}
                        	return false;
                    	}
                        else if(status == 'no')
                    	{

                        	if(confirm('确定拒绝通过审核吗？'))
                        	{
                        	window.location.href =  "/wechat/commissionwithdrawal/edit"+ "/ids/" + row[options.pk] + "?status=" + status , __('Edit')
                    	
                        	}
                        	return false;
                    	} 	
                        else
                        {
                        	if(confirm('确定支付吗？'))
                        	{
                        	window.location.href =  "/wechat/commissionwithdrawal/edit" + "/ids/" + row[options.pk] + "?status=" + status , __('Edit')
                        	}
                        
                        	return false;
                        }
               
                    },
                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var index = Layer.confirm(
                                __('Are you sure you want to delete this item?'),
                                {icon: 3, title: __('Warning'), offset: [top, left], shadeClose: true},
                                function () {
                                    var table = $(that).closest('table');
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi("del", row[options.pk], table, that);
                                    Layer.close(index);
                                }
                        );
                    }
                }, 
            },
            // 单元格数据格式化
            formatter: {
                icon: function (value, row, index) {
                    if (!value)
                        return '';
                    value = value.indexOf(" ") > -1 ? value : "fa fa-" + value;
                    //渲染fontawesome图标
                    return '<i class="' + value + '"></i> ' + value;
                },
                image: function (value, row, index, custom) {
                    var classname = typeof custom !== 'undefined' ? custom : 'img-sm img-center';
                    return '<img class="' + classname + '" src="' + Fast.api.cdnurl(value) + '" />';
                },
                images: function (value, row, index, custom) {
                    var classname = typeof custom !== 'undefined' ? custom : 'img-sm img-center';
                    var arr = value.split(',');
                    var html = [];
                    $.each(arr, function (i, value) {
                        html.push('<img class="' + classname + '" src="' + Fast.api.cdnurl(value) + '" />');
                    });
                    return html.join(' ');
                },
                status: function (value, row, index, custom) {
                    //颜色状态数组,可使用red/yellow/aqua/blue/navy/teal/olive/lime/fuchsia/purple/maroon
                    var colorArr = {normal: 'success', hidden: 'grey', deleted: 'danger', locked: 'info'};
                    //如果有自定义状态,可以按需传入
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    value = value.toString();
                    var color = value && typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'primary';
                    value = value.charAt(0).toUpperCase() + value.slice(1);
                    //渲染状态
                    var html = '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + __(value) + '</span>';
                    return html;
                },
                url: function (value, row, index) {
                    return '<div class="input-group input-group-sm" style="width:250px;"><input type="text" class="form-control input-sm" value="' + value + '"><span class="input-group-btn input-group-sm"><a href="' + value + '" target="_blank" class="btn btn-default btn-sm"><i class="fa fa-link"></i></a></span></div>';
                },

                url1: function (value, row, index) {
                    return '<div class="input-group" style="width:32px; text-align:center"><span class="input-group-btn input-group-sm"><a href="' + value + '" target="_blank" class="btn btn-default btn-sm" style="align:center"><i class="fa fa-link"></i></a></span></div>';
                },

                search: function (value, row, index) {
                    return '<a href="javascript:;" class="searchit" data-field="' + this.field + '" data-value="' + value + '">' + value + '</a>';
                },
                addtabs: function (value, row, index, url) {
                    return '<a href="' + url + '" class="addtabsit" title="' + __("Search %s", value) + '">' + value + '</a>';
                },
                flag: function (value, row, index, custom) {
                    var colorArr = {index: 'success', hot: 'warning', recommend: 'danger', 'new': 'info'};
                    //如果有自定义状态,可以按需传入
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    //渲染Flag
                    var html = [];
                    var arr = value.split(',');
                    $.each(arr, function (i, value) {
                        value = value.toString();
                        if (value == '')
                            return true;
                        var color = value && typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'primary';
                        value = value.charAt(0).toUpperCase() + value.slice(1);
                        html.push('<span class="label label-' + color + '">' + __(value) + '</span>');
                    });
                    return html.join(' ');
                },
                label: function (value, row, index, custom) {
                    var colorArr = ['success', 'warning', 'danger', 'info'];
                    //渲染Flag
                    var html = [];
                    var arr = value.split(',');
                    $.each(arr, function (i, value) {
                        value = value.toString();
                        var color = colorArr[i % colorArr.length];
                        html.push('<span class="label label-' + color + '">' + __(value) + '</span>');
                    });
                    return html.join(' ');
                },
                datetime: function (value, row, index) {
                	 // return value ? Moment(parseInt(value) * 1000).format("YYYY-MM-DD HH:mm:ss") : __('None');                	
                	return value ? value : __('None');//直接使用后台返回的date格式
                },
                operate: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var showrecovery = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.recovery_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                        if (options.extend.recovery_url == '')
                            showrecovery = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if (showedit)
                        html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil"></i></a>');
                    if(row['is_deleted'] == 0)
                    {
                        if (showdel)
                            html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash">删除</i></a>');
                    }else if(row['is_deleted']==1)
                    {
                        if (showrecovery)
                            html.push('<a href="javascript:;" class="btn btn-success btn-recoveryone btn-xs"><i class="fa fa-trash">恢复</i></a>');
                    }else{
                        if (showdel)
                            html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash">删除</i></a>');
                    }

                    return html.join(' ');
                },
                agentmanage: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var show = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if (show)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-details btn-xs">详情</a>');
                    if (showedit)
                        html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil"></i></a>');
                    if (showdel)
                        html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash"></i></a>');
                    return html.join(' ');
                },
                user: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var show = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if (showdel)
                        html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash"></i></a>');
                    return html.join(' ');
                },
                commiss: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var show = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (show)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-commiss btn-xs">详情</a>');
           
                    return html.join(' ');
                },
                operate2: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdetails = true;
                    var showdel = true;
                    var showrecovery = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                        if (options.extend.details_url == '')
                            showdetails = false;
                        if (options.extend.recovery_url == '')
                            showrecovery = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if (showdetails)
                        html.push('<a href="javascript:;" class="btn btn-success btn-detailsone btn-xs"><i class="fa fa-pencil">详情</i></a>');

                    if (showedit)
                        html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil">编辑</i></a>');
                    // if(row[''])
                        if(row['is_deleted'] == 0)
                        {
                            if (showdel)
                            html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash">删除</i></a>');
                        }else if(row['is_deleted']==1)
                        {
                            if (showrecovery)
                            html.push('<a href="javascript:;" class="btn btn-success btn-recoveryone btn-xs"><i class="fa fa-trash">恢复</i></a>');
                        }else{
                            if (showdel)
                                html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash">删除</i></a>');
                        }



                    return html.join(' ');
                },
                operate3: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;

                    var showdetails = true;
                    var showpriceedit = true;
                    var showdel = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                        if (options.extend.details_url == '')
                            showdetails = false;


                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');

                    if(row['status']==1){
                        if (showdetails)
                            html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil">佣金审核</i></a>');
                    }else if(row['status']==2){
                        html.push('<a href="javascript:;" class="btn btn-success btn-detailsone btn-xs"><i class="fa fa-pencil">查看详情</i></a>');
                    }else if(row['status']==3){
                        html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs"><i class="fa fa-pencil">重新审核</i></a>');
                    }

                    // if (showedit)
                    //     html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil">申请信息修改</i></a>');

                    // if (showdel)
                    //     html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash">佣金生成</i></a>');
                    return html.join(' ');
                },
                operate4: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdetails = true;
                    var showpriceedit = true;
                    var showdel = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;
                        if (options.extend.details_url == '')
                            showdetails = false;
                        if (options.extend.priceedit_url == '')
                            showpriceedit = false;
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');

                        if (row['status'] == 1) {
                            if (showdetails)
                                html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil">佣金审核</i></a>');
                        } else if (row['status'] == 2) {
                            html.push('<a href="javascript:;" class="btn btn-success btn-detailsone btn-xs"><i class="fa fa-pencil">查看详情</i></a>');
                        } else if (row['status'] == 3) {
                            html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs"><i class="fa fa-pencil">重新审核</i></a>');
                        }

                    // if (showedit)
                    //     html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil">申请信息修改</i></a>');


                    // if(row['loan_price']==0){
                    //     if (showpriceedit)
                    //         html.push('<a href="javascript:;" class="btn btn-danger btn-priceeditone btn-xs"><i class="fa fa-pencil">贷款金额修改</i></a>');
                    // }

                    return html.join(' ');
                },
                agentapply: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;                     
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if (showedit)
                    	if(row['status'] == '审核失败' || row['status'] == '审核通过')
                    	{
                    		html.push('<a href="javascript:;" class="btn  btn-primary btn-details btn-xs"   data-status="details" >详情</a>');
                    	}else
                    	{
                    		 html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"  data-status="yes">通过审核</a>');
                    	}
                       
                    if (showdel)
                    	if(row['status'] == '审核失败')
                		{
                    		
                		}
                    	else if( row['status'] == '审核通过')
                    	{
                      /*  html.push('<a href="javascript:;" class="btn btn-danger btn-edit btn-xs" data-status="edit">编辑</a>');*/
                    	}else
                    	{
                        html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs" data-status="no">不通过</a>');
                    	}
                    return html.join(' ');
                },
                strategyedit: function (value, row, index, table) {
                	 var showweigh = true;
                     var showedit = true;
                     var showdel = true;
                     if (typeof table != 'undefined') {
                         var options = table.bootstrapTable('getOptions');
                         if (options.extend.del_url == '')
                             showdel = false;
                         if (options.extend.edit_url == '')
                             showedit = false;                     
                     }
                     showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                     //行操作
                     var html = [];
                     if (showweigh)
                         html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                     if (showedit)
                     	html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil"></i></a>');
                     if (showdel)
                         html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash"></i></a>');
                     return html.join(' ');
                },
                theme: function (value, row, index, table) {
               	 var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var showcard = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;                     
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                    if(showcard)
                    	 html.push('<a href="javascript:;" class="btn btn-primary btn-addcard btn-xs">添加卡</a>');
                    if (showedit)
                    	html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs"><i class="fa fa-pencil"></i></a>');
                    if (showdel)
                        html.push('<a href="javascript:;" class="btn btn-danger btn-delone btn-xs"><i class="fa fa-trash"></i></a>');
                    return html.join(' ');
               },
                withdraw: function (value, row, index, table) {
                    var showweigh = true;
                    var showedit = true;
                    var showdel = true;
                    var pay = true;
                    if (typeof table != 'undefined') {
                        var options = table.bootstrapTable('getOptions');
                        if (options.extend.del_url == '')
                            showdel = false;
                        if (options.extend.edit_url == '')
                            showedit = false;                     
                    }
                    showweigh = typeof row[Table.config.dragsortfield] != 'undefined' ? true : false;
                    //行操作
                    var html = [];
                    if (showweigh)
                        html.push('<a href="javascript:;" class="btn btn-primary btn-dragsort btn-xs"><i class="fa fa-arrows"></i></a>');
                       if(row['status'] ==1){
                    	   html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs" data-status="yes"  >通过审核</a>');
                    	   html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs" data-status="no" >不通过</a>');
                       }
                       else if(row['status'] ==2){
                    	   if(row['pay_status'] ==3){
                    		   html.push('<div class="btn btn-success  btn-xs" >已支付</div>');
                    	   }
                    	   else{
                    		   html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs" data-status="pay" >支付</a>');
                    	   }
                       }
                       else if(row['status'] ==3){
                    	   html.push('<div class="btn btn-primary  btn-xs" >已拒绝</div>');
                       }
/*                    if (showedit)
                    	if(row['status'] == '审核通过' || row['status'] == '审核失败'){
                    		 
                    	}else{
                    		html.push('<a href="javascript:;" class="btn btn-success btn-editone btn-xs" data-status="yes"  >通过审核</a>');
                    	}
                       
                    
                    if (showdel)
                    	if(row['status'] == '审核通过' || row['status'] == '审核失败'){
                   		 
                    	}else{                    	
                        html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs" data-status="no" >不通过</a>');
                    	}
                    if (pay)
                    	if(row['status'] == '审核通过:等待支付' || row['status'] == '审核通过:支付失败' ){
                    		 html.push('<a href="javascript:;" class="btn btn-danger btn-editone btn-xs" data-status="pay" >支付</a>');
                    	}else{
                       
                    	}*/
                    return html.join(' ');
                }
            },
            // 获取选中的条目ID集合
            selectedids: function (table) {
                var options = table.bootstrapTable('getOptions');
                if (options.templateView) {
                    return $.map($("input[data-id][name='checkbox']:checked"), function (dom) {
                        return $(dom).data("id");
                    });
                } else {
                    return $.map(table.bootstrapTable('getSelections'), function (row) {
                        return row[options.pk];
                    });
                }
            },
            // 切换复选框状态
            toggleattr: function (table) {
                $("input[type='checkbox']", table).trigger('click');
            }
        },
    };
    return Table;
});
