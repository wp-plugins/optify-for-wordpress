function validation(){
      
      if(document.getElementById("optify_token") != null){
        if(document.getElementById("optify_token").value != ""){
          if(document.getElementById("optify_token").value.length == 8){
            return true;
          }else{
            document.getElementById('error-msz-token').innerHTML = 'Token should be 8 characters';
            document.getElementById("error-msz-token").style.display = "block";
            return false;
          }
        }
      }
      
      var fname = document.getElementById('fname').value;
      if(trim(fname) == null || trim(fname) == ''){
          document.getElementById("error-msz-fname").style.display = "block";
          return false;
      }
      else{
          document.getElementById("error-msz-fname").style.display = "none";
      }

      var lname = document.getElementById('lname').value;
      if(trim(lname) == null || trim(lname) == ''){
          document.getElementById("error-msz-lname").style.display = "block";
          return false;
      }
      else{
          document.getElementById("error-msz-lname").style.display = "none";
      }

      var phone = document.getElementById('phone').value;
      if(trim(phone) == null || trim(phone) == ''){
          document.getElementById("error-msz-phone").style.display = "block";
          return false;
      } else if(isNaN(phone.replace(/[^0-9]+/g, ""))){
          document.getElementById('error-msz-phone').innerHTML = 'Phone Number must be Numeric';
          document.getElementById("error-msz-phone").style.display = "block";
          return false;
      }
      else{
          document.getElementById("error-msz-phone").style.display = "none";
      }

      var email = document.getElementById('email').value;
      var emailRegEx = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i;
      if(trim(email) == null || trim(email) == ''){
          document.getElementById("error-msz-email").style.display = "block";
          return false;
      } else if(!emailRegEx.test(email)){
          document.getElementById('error-msz-email').innerHTML = 'Email is Required';
          document.getElementById("error-msz-email").style.display = "block";
          return false;
      }
      else{
          document.getElementById("error-msz-email").style.display = "none";
      }

      var setpwd = document.getElementById('set-pwd').value;
      if(trim(setpwd) == null || trim(setpwd) == ''){
          document.getElementById("error-msz-pwd").style.display = "block";
          return false;
      } else if(setpwd.length <= 6){
          document.getElementById('error-msz-pwd').innerHTML = 'Password minimum 6 character';
          document.getElementById("error-msz-pwd").style.display = "block";
          return false;
      }
      else{
          document.getElementById("error-msz-pwd").style.display = "none";
      }
  }

  function trim(s)
{
	var l=0; var r=s.length -1;
	while(l < s.length && s[l] == ' ')
	{	l++; }
	while(r > l && s[r] == ' ')
	{	r-=1;	}
	return s.substring(l, r+1);
}