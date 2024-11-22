leap.otp.init = function(username, console = "/app/leap/bin/console"){
  leap.log("OTP init...")

  output = system(paste0("php ",console," otp:init ", username), intern=T)
  fromJSON(output)
}