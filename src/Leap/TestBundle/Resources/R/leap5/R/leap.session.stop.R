leap.session.stop <- function(status = STATUS_STOPPED, response = NULL, data=list()){
    leap.log("stopping session...")
    leap.log(paste0("status: ", status))

    if(!is.null(leap$session)) {
        leap$session$status <<- status
        leap5:::leap.session.update()
    }
    dbDisconnect(leap$connection)

    if (!is.null(response)) {
        leap5:::leap.server.respond(response, data)
    }
    q("no", if(status == STATUS_ERROR) 1 else 0)
}
