leap.server.listen = function(skipOnResume=F){
    repeat {
        leap.log("listening to server...")

        dbDisconnect(leap$connection)
        leap.log("connections closed")

        setTimeLimit(transient = TRUE)

        leap.log(paste0("waiting for submitter port..."))
        repeat {
            if(file.exists("submitter.port")) {
                fh = file("submitter.port", open="rt")
                leap$session$submitterPort <<- readLines(fh)
                close(fh)
                if(length(leap$session$submitterPort) == 0) {
                    Sys.sleep(0.1)
                    next
                }
                break
            }

            currentTime = as.numeric(Sys.time())
            if(leap$maxIdleTime > 0 && currentTime - leap$lastSubmitTime > leap$maxIdleTime) {
                leap.log("idle timeout")
                leap$connection <<- leap.db.connect(
                    leap$dbConnectionParams$driver,
                    leap$dbConnectionParams$username,
                    leap$dbConnectionParams$password,
                    leap$dbConnectionParams$dbname,
                    leap$dbConnectionParams$host,
                    leap$dbConnectionParams$unix_socket,
                    leap$dbConnectionParams$port
                )
                if(leap$sessionStorage == "redis") {
                    leap$redisConnection <<- leap.redis.connect(
                        host = leap$redisConnectionParams$host,
                        port = leap$redisConnectionParams$port,
                        password = leap$redisConnectionParams$password
                    )
                }
                leap$session <<- as.list(leap.session.get(leap$session$hash))
                leap5:::leap.session.stop(STATUS_STOPPED)
            }
            if(leap$keepAliveToleranceTime > 0 && currentTime - leap$lastKeepAliveTime > leap$keepAliveToleranceTime) {
                leap.log("keep alive timeout")
                leap$connection <<- leap.db.connect(
                    leap$dbConnectionParams$driver,
                    leap$dbConnectionParams$username,
                    leap$dbConnectionParams$password,
                    leap$dbConnectionParams$dbname,
                    leap$dbConnectionParams$host,
                    leap$dbConnectionParams$unix_socket,
                    leap$dbConnectionParams$port
                )
                if(leap$sessionStorage == "redis") {
                    leap$redisConnection <<- leap.redis.connect(
                        host = leap$redisConnectionParams$host,
                        port = leap$redisConnectionParams$port,
                        password = leap$redisConnectionParams$password
                    )
                }
                leap$session <<- as.list(leap.session.get(leap$session$hash))
                leap5:::leap.session.stop(STATUS_STOPPED)
            }
            Sys.sleep(0.1)
        }
        leap.log(paste0("waiting for submit (port: ",leap$session$submitterPort,")..."))
        con = socketConnection(host = "localhost", port = leap$session$submitterPort, blocking = TRUE, timeout = 60 * 60 * 24, open = "rt")
        response = readLines(con, warn = FALSE)
        response = fromJSON(response)
        leap$lastResponse <<- response
        response$values$.cookies = response$cookies
        close(con)
        if(leap$maxExecTime > 0) {
            setTimeLimit(elapsed = leap$maxExecTime, transient = TRUE)
        }

        leap.log(response, "received response")

        leap$connection <<- leap.db.connect(
            leap$dbConnectionParams$driver,
            leap$dbConnectionParams$username,
            leap$dbConnectionParams$password,
            leap$dbConnectionParams$dbname,
            leap$dbConnectionParams$host,
            leap$dbConnectionParams$unix_socket,
            leap$dbConnectionParams$port
        )
        if(leap$sessionStorage == "redis") {
            leap$redisConnection <<- leap.redis.connect(
                host = leap$redisConnectionParams$host,
                port = leap$redisConnectionParams$port,
                password = leap$redisConnectionParams$password
            )
        }
        leap$session <<- as.list(leap.session.get(leap$session$hash))

        leap.log("listened to server")
        unlink("submitter.port")

        if (response$code == RESPONSE_SUBMIT) {
            leap$lastKeepAliveTime <<- as.numeric(Sys.time())
            leap$lastSubmitTime <<- as.numeric(Sys.time())

            if(!is.null(leap$lastSubmitId) && leap$lastSubmitId == response$values$submitId) {
                leap5:::leap.server.respond(RESPONSE_VIEW_TEMPLATE, leap$lastSubmitResult)
                next
            }

            leap.event.fire("onTemplateSubmit", list(response=response$values))
            return(response$values)
        } else if (response$code == RESPONSE_RESUME) {
            leap$lastKeepAliveTime <<- as.numeric(Sys.time())
            response = NULL
            if(skipOnResume) {
                response = list()
            }
            return(response)
        } else if(response$code == RESPONSE_KEEPALIVE_CHECKIN) {
            leap.log("keep alive checkin")
            leap$lastKeepAliveTime <<- as.numeric(Sys.time())
        } else if(response$code == RESPONSE_STOP) {
            leap.log("stop request")
            leap5:::leap.session.stop(STATUS_STOPPED)
        } else if(response$code == RESPONSE_WORKER) {
            leap$lastKeepAliveTime <<- as.numeric(Sys.time())
            result = list()
            if(!is.null(response$values$bgWorker) && response$values$bgWorker %in% ls(leap$bgWorkers)) {
                leap.log(paste0("running worker: ", response$values$bgWorker))
                result = do.call(leap$bgWorkers[[response$values$bgWorker]], list(response=response$values))
            }
            leap5:::leap.server.respond(RESPONSE_WORKER, result)
        } else return(response)
    }
}