leap.session.unserialize <- function(response = NULL, hash = NULL){
    leap.log("unserializing session...")

    prevEnv = new.env()
    if(leap$sessionStorage == "redis") {
        #TODO decompress
        redisBinarySession = leap$redisConnection$GET(leap$session$hash)
        if(!is.null(redisBinarySession)) {
            prevEnv$leap = unserialize(redisBinarySession)
        } else {
            return(F)
        }
    } else {
        sessionFileName = leap$sessionFile
        if(!is.null(hash)) {
            sessionFileName = gsub(leap$session$hash, hash, sessionFileName)
        }
        if(!file.exists(sessionFileName)) {
            leap.log(sessionFileName, "session file not found")
            return(F)
        }
        leap.log(sessionFileName)

        load(sessionFileName, envir=prevEnv)
    }

    leap$cache <<- prevEnv$leap$cache
    leap$globals <<- prevEnv$leap$globals
    leap$templateParams <<- prevEnv$leap$templateParams
    leap$globalTemplateParams <<- prevEnv$leap$globalTemplateParams
    leap$flow <<- prevEnv$leap$flow
    leap$lastSubmitTime <<- prevEnv$leap$lastSubmitTime
    leap$lastSubmitResult <<- prevEnv$lastSubmitResult
    leap$lastSubmitId <<- prevEnv$lastSubmitId
    leap$lastKeepAliveTime <<- prevEnv$leap$lastKeepAliveTime
    leap$bgWorkers <<- prevEnv$leap$bgWorkers
    leap$headers <<- prevEnv$leap$headers
    if(!is.null(response)) {
        leap$lastResponse <<- response
    } else {
        leap$lastResponse <<- prevEnv$leap$lastResponse
    }
    leap$skipTemplateOnResume <<- prevEnv$leap$skipTemplateOnResume
    leap$events <<- prevEnv$leap$events
    rm(prevEnv)

    leap.log("session unserialized")

    #non submit resume
    if(is.null(response) && leap$runnerType == RUNNER_SERIALIZED) {
        leap$resuming <<- T
        leap$resumeIndex <<- 0
        leap.test.run(leap$flow[[1]]$id, params=leap$flow[[1]]$params)
        leap5:::leap.session.stop(STATUS_FINALIZED, RESPONSE_FINISHED)
    }

    if (!is.null(response$code) && response$code == RESPONSE_SUBMIT) {
        leap$lastKeepAliveTime <<- as.numeric(Sys.time())
        leap$lastSubmitTime <<- as.numeric(Sys.time())

        if(!is.null(leap$lastSubmitId) && leap$lastSubmitId == response$values$submitId) {
            leap5:::leap.server.respond(RESPONSE_VIEW_TEMPLATE, leap$lastSubmitResult)
            leap5:::leap.session.stop(STATUS_RUNNING)
        }

        leap.event.fire("onTemplateSubmit", list(response=response$values))
        leap$queuedResponse <<- response$values
    } else if(!is.null(response$code) && response$code == RESPONSE_WORKER) {
        leap$lastKeepAliveTime <<- as.numeric(Sys.time())
        result = list()
        if(!is.null(response$values$bgWorker) && response$values$bgWorker %in% ls(leap$bgWorkers)) {
            leap.log(paste0("running worker: ", response$values$bgWorker))
            result = do.call(leap$bgWorkers[[response$values$bgWorker]], list(response=response$values))
        }
        leap5:::leap.session.serialize()
        leap5:::leap.server.respond(RESPONSE_WORKER, result)
        leap5:::leap.session.stop(STATUS_RUNNING)
    } else if(!is.null(response$code) && response$code == RESPONSE_RESUME) {
        leap$lastKeepAliveTime <<- as.numeric(Sys.time())
        if(leap$skipTemplateOnResume) {
            leap$queuedResponse <<- list()
        }
    }

    return(T)
}