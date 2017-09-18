define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

	$('.push').click(function(){
		var comment =	$('.comment').val();
		var logo = 	$('.logo').val();
		var title =	$('.title').val();
		var type =	$('.type').val();
		var id =	$('.get_id').val();
		
		if(title =='')
		{
	       alert('标题不能为空');
	       return;
		}
		
		if(logo =='')
		{
	       alert('图片不能为空');
	       return;
		}
			
			 $.ajax({
		          url: 'strategy/addstrategy/add',
		          type: 'post',
		          dataType: 'json',
		          data:'comment='+ comment + '&logo=' + logo + '&title=' + title +'&type=' + type +'&id=' +id ,
		          success: function (ret) {
		        	if(ret.code == 1)
		        	{
		        		alert('发表成功');
		        		 top.location='/strategy/strategylist?ref=addtabs';
		        	}else{
		        		alert('发表失败');
		        	}
		             
		          }
		      });
	});
	
	$('.change').click(function(){
		var comment =	$('.comment').val();
		var logo = 	$('.logo').val();
		var title =	$('.title').val();
		var type =	$('.type').val();
		var id =	$('.get_id').val();
		
		if(title =='')
		{
	       alert('标题不能为空');
	       return;
		}
		
		if(logo =='')
		{
	       alert('图片不能为空');
	       return;
		}
			
			 $.ajax({
		          url: 'strategy/addstrategy/change',
		          type: 'post',
		          dataType: 'json',
		          data:'comment='+ comment + '&logo=' + logo + '&title=' + title +'&type=' + type +'&id=' +id ,
		          success: function (ret) {
		        	if(ret.code == 1)
		        	{
		        		alert('更新成功');
		        		 top.location='/strategy/strategylist?ref=addtabs';
		        	}else{
		        		alert('更新失败');
		        	}
		             
		          }
		      });
	});
	
	
	$('.draftbox').click(function(){
	var comment =	$('.comment').val();
	var logo = 	$('.logo').val();
	var title =	$('.title').val();
	var type =	$('.type').val();
	var id =	$('.get_id').val();
	
	if(title =='')
	{
       alert('标题不能为空');
       return;
	}
	
	if(logo =='')
	{
       alert('图片不能为空');
       return;
	}
		
		 $.ajax({
	          url: 'strategy/addstrategy/draftbox',
	          type: 'post',
	          dataType: 'json',
	          data:'comment='+ comment + '&logo=' + logo + '&title=' + title +'&type=' + type +'&id=' +id ,
	          success: function (ret) {
	        	if(ret.code == 1)
	        	{
	        		alert('保存成功');
	        		 top.location='/strategy/draftbox?ref=addtabs';
	        	}else if (ret.code == 2)
        		{
	        		 alert('发表成功');
	        		 top.location='/strategy/strategylist?ref=addtabs';
        		}
	        	else{
	        		alert('保存失败');
	        	}
	             
	          }
	      });
	});
	
	
	
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'strategy/addstrategy/index',
                    add_url: 'strategy/addstrategy/add',
                    edit_url: 'strategy/addstrategy/edit',
                    del_url: 'strategy/addstrategy/del',
                    multi_url: 'strategy/addstrategy/multi',
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
                        {field: 'create_time', title: __('添加时间'),formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker',data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
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