

$(window).keydown(function(event){
  if(event.keyCode == 116) {
    $('#form-order').trigger("reset");
  }
});

//表單除了textarea, 禁用enter鍵
$(document).ready(function() {
  $(window).keydown(function(e){
    var keyCode = e.keyCode || e.which;
    if((keyCode == 13) && ($(e.target)[0]!=$("textarea")[0])) {
        e.preventDefault();
        return false;
    }
  });
});

//非數字轉成數字或0
String.prototype.toNum = function(){
  let parsedValue = parseFloat(this.replace(/,/g, ''));

  if (parsedValue % 1 === 0) {
    parsedValue = parseInt(parsedValue);
  }

  if(!$.isNumeric(parsedValue)){
    parsedValue = 0;
  }

  return parsedValue;
}

//0轉成空字串
String.prototype.zeroToEmpty = function(){
  var num = this;
  if(num == 0){
    num = ''
  }
  return num;
}



function isDateValid(dateString) {
    var date = new Date(dateString);

    if (isNaN(date.getTime())) {
        return false;
    }

    return true;
}

