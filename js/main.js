(function(document,window,$){


     function _get(loc, success_callback,error_callback){          

      $.ajax({
        type  :   'get',
        url   :   loc,        
        success :   function(response){
          if (typeof success_callback === 'function'){
            success_callback(response);
          }
        },
        error :   function(response){
          if (typeof error_callback === 'function'){
            error_callback(response);
          }else{
            console.log(response);
          }
        }
      });
    }


    function makeList(list, id){    	
    	$.each(list,function(index,data){
    		if(index!== 0 && index!== 1 && data !== 'index.html'){
    			var li = $('<li class = "folder"></li>'),
    			a = $('<a href = "/../projects/'+data+'"><img src = "img/icon.png"/><span>'+data+'</span></a>');    			    			
    			id.append(li.append(a));
    		}    		
    	});
    }


    $(document).ready(function(){
    	_get('api/index.php/get/file/list',
    		function(response){
    			makeList(JSON.parse(response),$("#filelist"));
    		},
	    	function(response){
	    		console.log(response);
	    	}
    	);
    });

}(document, this, jQuery));	
