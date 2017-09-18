define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts','../../libs/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min', 'echarts-theme'], function ($, undefined, Backend, Datatable, Table, Echarts,datetimepicker) {
$('.datetimepicker').datetimepicker({
    icons: {
        time: 'fa fa-clock-o',
        date: 'fa fa-calendar',
        up: 'fa fa-chevron-up',
        down: 'fa fa-chevron-down',
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-history',
        clear: 'fa fa-trash',
        close: 'fa fa-remove'
    },
    showTodayButton: true,
    showClose: true
});

$(function(){
	$('body').on('blur','.datetimePicker1,.datetimePicker2',function(){
		
          if($(".datetimePicker2").val()!="" && $(".datetimePicker2").val()<$(".datetimePicker1").val()){
              $(".datetimePicker1").val($(".datetimePicker2").val())
          }
	});
	})


$('.btn-success').click(function(){
	var start = $('.start').val();
	var end = $('.end').val();
	
	
	  $.ajax({
          url: 'dashboard/index',
          type: 'post',
          dataType: 'json',
          data:'start='+ start + '&end=' + end,
          success: function (ret) {
        	  var ajax_card = ret.data.creditCard;
        	  var ajax_loan = ret.data.loan;
console.log(ajax_loan)
              var card_html='';
              var loan_html='';
              
              if(ajax_card != '')
        	  {
            	  for(var i in ajax_card)
        		  {
            		  card_html += '<b style=" float: left;margin-left: 50px;">' + ajax_card[i]['confirm_time'] + '</b><b   style="  margin-left: -100px;">' + ajax_card[i]['bank_name'] + '</b> <b style=" float: right;margin-right: 50px;">' +  ajax_card[i]['total'] +'</b></br>'
        		  }
        	  }
        	
        	  
              if(ajax_loan != '')
        	  {
            	  for(var i in ajax_loan)
        		  {
            		  loan_html += '<b style=" float: left;margin-left: 50px;">'+ ajax_loan[i]['confirm_time'] + '</b><b   style="  margin-left: -100px;">'+ ajax_loan[i]['name']  + '</b> <b style=" float: right;margin-right: 50px;">'+  ajax_loan[i]['totalmoney'] +'</b></br>'
        		  }
        	  }
        	
        	  
        	 
		    	  if (ajax_card == ''){
		 			 $('.insert').html('<b style="text-align:center;">暂无数据</b>');
		 		 }else
		 		 {
		 			  $('.insert').html(card_html);
		 	        	 
		 		 }
        	
        		 if(ajax_loan == '' ){
        			 $('.loan_insert').html('<b style="text-align:center;">暂无数据</b>');
        			// return;
        		 }else
        		 {
        			  $('.loan_insert').html(loan_html); 
        		 }
        		 
        		 

             
          }
      });
});

	var Controller = {
        index: function () {
        	  Table.api.init({
                  extend: {
                      index_url: 'Dashboard/index',                    
                      table: 'Dashboard'
                  }
              });

      	  
        	
        	
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');

            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['下单', '成交']
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: {show: true, type: ['stack', 'tiled']},
                        saveAsImage: {show: true}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Orderdata.column
                },
                yAxis: {

                },
                grid: [{
                        left: 'left',
                        top: 'top',
                        right: '10',
                        bottom: 30
                    }],
                series: [{
                        name: '成交',
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {
                            }
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: Orderdata.paydata
                    },
                    {
                        name: '下单',
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {
                            }
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: Orderdata.createdata
                    }]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);

            //动态添加数据，可以通过Ajax获取数据然后填充
            setInterval(function () {
                Orderdata.column.push((new Date()).toLocaleTimeString().replace(/^\D*/, ''));
                var amount = Math.floor(Math.random() * 200) + 20;
                Orderdata.createdata.push(amount);
                Orderdata.paydata.push(Math.floor(Math.random() * amount) + 1);

                //按自己需求可以取消这个限制
                if (Orderdata.column.length >= 20) {
                    //移除最开始的一条数据
                    Orderdata.column.shift();
                    Orderdata.paydata.shift();
                    Orderdata.createdata.shift();
                }
                myChart.setOption({
                    xAxis: {
                        data: Orderdata.column
                    },
                    series: [{
                            name: '成交',
                            data: Orderdata.paydata
                        },
                        {
                            name: '下单',
                            data: Orderdata.createdata
                        }]
                });
            }, 2000);
            $(window).resize(function () {
                myChart.resize();
            });
        }
    };

    return Controller;
});