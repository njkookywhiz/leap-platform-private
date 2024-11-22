leap.run = function(workingDir, client, sessionHash, maxIdleTime = NULL, maxExecTime = NULL, response = NULL, initialPort = NULL, runnerType = 0) {
    leap$workingDir <<- workingDir
    leap$client <<- client
    leap$sessionHash <<- sessionHash
    leap$sessionFile <<- paste0(leap$workingDir,"session.Rs")
    leap$initialPort <<- initialPort
    leap$runnerType <<- runnerType
    leap$lastResponse <<- response
    if(!is.null(response) && !is.null(response$headers)) {
        leap$headers <<- response$headers
    }

    if(!is.null(maxIdleTime)) {
        leap$maxIdleTime <<- maxIdleTime
    }
    if(!is.null(maxExecTime)) {
        leap$maxExecTime <<- maxExecTime
    }

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

    leap["session"] <<- list(NULL)
    if(!is.null(leap$sessionHash)) {
        leap$session <<- as.list(leap5:::leap.session.get(leap$sessionHash))
        leap$session$previousStatus <<- leap$session$status
        leap$session$status <<- STATUS_RUNNING
        leap$session$params <<- fromJSON(leap$session$params)
        leap$mainTest <<- list(id=leap$session$test_id)
    }
    leap$resuming <<- F

    tryCatch({
        setwd(leap$workingDir)
        if(leap$maxExecTime > 0) {
            setTimeLimit(elapsed=leap$maxExecTime, transient=TRUE)
        }

        if(!is.null(leap$session)) {
            testId = leap$session["test_id"]
            params = leap$session$params
            if(leap$runnerType == RUNNER_SERIALIZED && leap.session.unserialize(response)) {
                leap$resuming <<- T
                leap$resumeIndex <<- 0
                testId = leap$flow[[1]]$id
                params = leap$flow[[1]]$params
            }
            leap.test.run(testId, params)
        }

        leap5:::leap.session.stop(STATUS_FINALIZED, RESPONSE_FINISHED)
    }, error = function(e) {
        leap.log(e)
        if(!is.null(leap$session)) { leap$session$error <<- e }
        leap5:::leap.session.stop(STATUS_ERROR, RESPONSE_ERROR)
    })
}