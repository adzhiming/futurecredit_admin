define(['jquery', 'bootstrap', 'backend', 'form', 'table'], function ($, undefined, Backend, Form, Table) {
/*	$('.click').click(function(){

		window.parent.document.getElementById("layui-layer"+ index).style.display="none";

	});*/
	$(function(){
	$('body').on('blur','.datetimePicker1,.datetimePicker2',function(){
		
          if($(".datetimePicker2").val()!="" && $(".datetimePicker2").val()<$(".datetimePicker1").val()){
              $(".datetimePicker1").val($(".datetimePicker2").val())
          }
	});
	})
$('.submit').click(function(){
	var card =[];
	var loan =[];
	var id = $('.get_id').val();
	var applymsg = $('.applymsg').val();
	var check = $('.check_status').val();

	$('.card ul li').each(function(){

       	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');console.log(obj)
		 if($(this).find("input[type='checkbox']").is(':checked'))
		 { 
			 card.push( $(this).find("input[type='checkbox']").val());
		 }

	 });
	
	$('.loan ul li').each(function(){

      	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');console.log(obj)
		 if($(this).find("input[type='checkbox']").is(':checked'))
		 { 
			 loan.push( $(this).find("input[type='checkbox']").val());
		 }

	 });
	
	var card = card.join();
	var loan = loan.join();


	 $.ajax({
          url: 'example/agentapply/edit',
          type: 'post',
          dataType: 'json',
          data:'card=' + card + '&loan='+ loan + '&id=' +id +'&applymsg='+ applymsg +'&check=' +check, 
          success: function (ret) {
        	if(ret.code == 1)
        	{
        		alert('添加成功');
        		 top.location='/example/agentapply?ref=addtabs';
        	}
        	else{
        		alert('添加失败');
        	}
             
          }
      });
});
	

	$('#change').click(function(){
		var card =[];
		var loan =[];
		var id = $('.get_id').val();
		var level1_num = $('#level1_num').val();
		var level2_num = $('#level2_num').val();
		var level3_num = $('#level3_num').val();

		$('.card ul li').each(function(){

	       	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');console.log(obj)
			 if($(this).find("input[type='checkbox']").is(':checked'))
			 { 
				 card.push( $(this).find("input[type='checkbox']").val());
			 }

		 });
		
		$('.loan ul li').each(function(){

	      	 var obj =	 $(this).find("input[type='checkbox']").is(':checked');console.log(obj)
			 if($(this).find("input[type='checkbox']").is(':checked'))
			 { 
				 loan.push( $(this).find("input[type='checkbox']").val());
			 }

		 });
		
		
		
		var card = card.join(); 
		var loan = loan.join();


		 $.ajax({
	          url: 'example/agentapply/details',
	          type: 'post',
	          dataType: 'json',
	          data:'card=' + card + '&loan='+ loan + '&id=' +id+'&level1_num='+level1_num+'&level2_num='+level2_num+'&level3_num='+level3_num , 
	          success: function (ret) {
	        	if(ret.code == 1)
	        	{
	        		alert('更新成功');
	        		 top.location='/example/agentapply?ref=addtabs';
	        	}
	        	else{
	        		alert('更新失败');
	        	}
	             
	          }
	      });
	});

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'example/agentapply/index',
                    add_url: 'example/agentapply/add',
                    edit_url: 'example/agentapply/edit',
                    del_url: 'example/agentapply/del',
                    multi_url: 'example/agentapply/multi',
                    table: 'agentapply'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                columns: [
                    [
                      //  {field: 'state', checkbox: true, },
                        {field: 'phone', title: __('手机号码')},
                        {field: 'name', title: __('名称')},
                        {field: 'apply_time', title: __('申请时间')},
                        {field: 'status', title: __('审核状态')},
                        {field: 'confirm_time', title: __('确认时间'), formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker', data: 'data-date-format="YYYY-MM-DD HH:mm:ss"'},
                        {field: 'superior', title: __('所属上级')},                    
                        {field: 'operate', title: __('Operate'), events: Table.api.events.apply, formatter: Table.api.formatter.agentapply}
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