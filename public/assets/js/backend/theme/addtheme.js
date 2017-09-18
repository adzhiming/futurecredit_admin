define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

	$('.push').click(function(){
		var theme =	$('.theme').val();
		var order = 	$('.order').val();
		var remark = 	$('.remark').val();
		var logo = 	$('.logo').val();
		var status = 	$("input[type='radio']:checked").val();
		var id =	$('.get_id').val();
	
		if(theme =='')
		{
	       alert('主题不能为空');
	       return;
		}

		if(logo =='')
		{
	       alert('图片不能为空');
	       return;
		}
	
			 $.ajax({
		          url: 'theme/addtheme/add',
		          type: 'post',
		          dataType: 'json',
		          data:'theme='+ theme + '&order='+ order +'&remark='+ remark +'&logo='+ logo  +'&status='+ status ,
		          success: function (ret) {
		        	if(ret.code == 1)
		        	{
		        		alert('添加成功');
		        		 top.location='/theme/themelist?ref=addtabs';
		        	}
		        	else if(ret.code == 2)
		        	{
		        		alert(ret.msg);
		        		return;
		        	}
		        	else{
		        		alert('添加失败');
		        	}
		             
		          }
		      });
	});
	
	$('.addcard').click(function(){
		var val =[];
		var id = $('.get_id').val();

		$('ul li').each(function(){
	
	       	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');
			 if($(this).find("input[type='checkbox']").is(':checked'))
			 { 
				 val.push( $(this).find("input[type='checkbox']").val());
			 }
	
		 });
		var str = val.join();

		 $.ajax({
	          url: '/theme/addtheme/addcard',
	          type: 'post',
	          dataType: 'json',
	          data:'str=' + str + '&id=' +id ,
	          success: function (ret) {
	        	if(ret.code == 1)
	        	{
	        		alert('添加成功');
	        		 top.location='/theme/themelist?ref=addtabs';
	        	}
	        	else{
	        		alert('添加失败');
	        	}
	             
	          }
	      });
		
	});
	
	$('.changecard').click(function(){
		var val =[];
		var id = $('.get_id').val();

		$('ul li').each(function(){
	
	       	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');console.log(obj)
			 if($(this).find("input[type='checkbox']").is(':checked'))
			 { 
				 val.push( $(this).find("input[type='checkbox']").val());
			 }
	
		 });
		var str = val.join();

		 $.ajax({
	          url: '/theme/addtheme/changecard',
	          type: 'post',
	          dataType: 'json',
	          data:'str=' + str + '&id=' +id ,
	          success: function (ret) {
	        	if(ret.code == 1)
	        	{
	        		alert('更新成功');
	        		 top.location='/theme/themelist?ref=addtabs';
	        	}
	        	else{
	        		alert('更新失败');
	        	}
	             
	          }
	      });
		
	});
	
	
	$('.change').click(function(){
		var theme =	$('.theme').val();
		var order = 	$('.order').val();
		var remark = 	$('.remark').val();
		var logo = 	$('.logo').val();
		var status = 	$("input[type='radio']:checked").val();
		var id =	$('.get_id').val();
	
		if(theme =='')
		{
	       alert('主题不能为空');
	       return;
		}


		if(logo =='')
		{
	       alert('图片不能为空');
	       return;
		}
	
			 $.ajax({
		          url: 'theme/addtheme/change',
		          type: 'post',
		          dataType: 'json',
		          data:'theme='+ theme + '&order='+ order +'&remark='+ remark +'&logo='+ logo  +'&status='+ status +'&id=' +id ,
		          success: function (ret) {
		        	if(ret.code == 1)
		        	{
		        		alert('更新成功');
		        		 top.location='/theme/themelist?ref=addtabs';
		        	}
		        	else if(ret.code == 2)
		        	{
		        		alert(ret.msg);
		        		return;
		        	}
		        	else{
		        		alert('更新失败');
		        		return;
		        	}
		             
		          }
		      });
	});
	
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'theme/addtheme/index',
                    add_url: 'theme/addtheme/add',
                    edit_url: 'theme/addtheme/edit',
                    del_url: 'theme/addtheme/del',
                    multi_url: 'theme/addtheme/multi',
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