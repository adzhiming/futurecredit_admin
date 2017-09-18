define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {
	$(function(){
		$('body').on('blur','.datetimePicker1,.datetimePicker2',function(){
			
	          if($(".datetimePicker2").val()!="" && $(".datetimePicker2").val()<$(".datetimePicker1").val()){
	              $(".datetimePicker1").val($(".datetimePicker2").val())
	          }
		});
		})


	$('#getCode').click(function(event){
		
		event.preventDefault();

		var userid = $('.userid').val();

		$(this).addClass('ft-gray').prop('disabled', 'disabled').html('60秒后，重新获取');
		
		$.ajax({
			url:"/example/agentmanage/getverificationcode",
			type:'post',
			asysn:false,
			dataType:'json',
			data:{
				userid: userid
			},
			success:function(res) {

				if(0 == res.code) {
					$('.errorcode').html('验证码已成功发送');//.removeClass('ft-orange').addClass('ui-tiptext-success');
					countDown(60);
				}
				else {
					alert(res.msg);
					$('#getCode').removeClass('ft-gray').addClass('ft-orange').prop('disabled', '').html( '重新获取验证码');
					return;
				}
			},
			error:function(res) {
				alert('网络错误，请刷新页面后重试！');
				return ;
			},
		});
		
		
		

		function countDown(count) {
			if ('undifined' == typeof(count)) {
				count = 60;
			}
			--count;
			if(0 < count) {
				$('#getCode').removeClass('ft-orange').addClass('ft-gray').prop('disabled', 'disabled').html( count + '秒后，重新获取');
				setTimeout(function() { 
					countDown(count) 
					},1000) 
			}
			else if (0 === count) {
				$('#getCode').removeClass('ft-gray').addClass('ft-orange').prop('disabled', '').html( '重新获取验证码');
			}
		}
		
	});
	
	
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'example/agentmanage/index',
                    add_url: 'example/agentmanage/add',
                    edit_url: 'example/agentmanage/edit',
                    del_url: 'example/agentmanage/del',
                    multi_url: 'example/agentmanage/multi',
                    table: 'agentmanage'
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
                      //  {field: 'id', title: __('Id')},
                       // {field: 'phone', title: __('登录账号')},
                        {field: 'name', title: __('姓名')},
                        {field: 'phone', title: __('手机号码')},
                        {field: 'level', title: __('级别')},
                        {field: 'create_time', title: __('开户时间') ,formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'superior', title: __('所属上级')},
                        {field: 'subordinate', title: __('下级代理数量')},
                     //   {field: 'operation', title: __('')},
                      //  {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), events: Table.api.events.operate, formatter: Table.api.formatter.agentmanage}
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