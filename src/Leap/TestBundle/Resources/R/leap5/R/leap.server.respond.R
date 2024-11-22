leap.server.respond = function(response, data=list()){
  leap.log("responding to server...")

  port = leap$initialPort
  if(!is.null(leap$session)) {
      port = leap$session$submitterPort
  }
  if(leap$runnerType == RUNNER_SERIALIZED && file.exists("submitter.port")) {
    while(T) {
        fh = file("submitter.port", open="rt")
        port = readLines(fh)
        if(!is.null(leap$session)) { leap$session$submitterPort <<- port }
        close(fh)
        if(length(port) == 0) {
           Sys.sleep(0.1)
           next
        }
        unlink("submitter.port")
        break
    }
  }
  con = socketConnection(host="localhost", port=port)
  response = list(
    source=SOURCE_PROCESS,
    code=response,
    data=data
  )

  writeLines(paste0(toJSON(response), "\n"), con)
  close(con)
  leap.log("responded to server")
}