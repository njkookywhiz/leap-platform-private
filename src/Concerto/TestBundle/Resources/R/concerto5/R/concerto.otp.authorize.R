leap.otp.authorize = function(username, secret, code, console = "/app/leap/bin/console") {
  leap.log("OTP authorize...")

  output = system(paste0("php ", console, " otp:authorize ", username, " ", secret, " ", code), intern = T)
  fromJSON(output)
}