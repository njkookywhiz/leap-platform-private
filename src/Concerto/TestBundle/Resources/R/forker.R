require(leap5)
require(parallel)

ENV_LEAP_R_APP_URL = Sys.getenv("LEAP_R_APP_URL")
ENV_LEAP_R_DB_CONNECTION = Sys.getenv("LEAP_R_DB_CONNECTION")
ENV_LEAP_R_FIFO_PATH = Sys.getenv("LEAP_R_FIFO_PATH")
ENV_LEAP_R_MAX_EXEC_TIME = Sys.getenv("LEAP_R_MAX_EXEC_TIME")
ENV_LEAP_R_MAX_IDLE_TIME = Sys.getenv("LEAP_R_MAX_IDLE_TIME")
ENV_LEAP_R_KEEP_ALIVE_TOLERANCE_TIME = Sys.getenv("LEAP_R_KEEP_ALIVE_TOLERANCE_TIME")
ENV_LEAP_R_PLATFORM_URL = Sys.getenv("LEAP_R_PLATFORM_URL")
ENV_LEAP_R_PUBLIC_DIR = Sys.getenv("LEAP_R_PUBLIC_DIR")
ENV_LEAP_R_REDIS_CONNECTION = Sys.getenv("LEAP_R_REDIS_CONNECTION")
ENV_LEAP_R_SESSION_STORAGE = Sys.getenv("LEAP_R_SESSION_STORAGE")
ENV_LEAP_R_SESSION_FILES_EXPIRATION = Sys.getenv("LEAP_R_SESSION_FILES_EXPIRATION")
ENV_LEAP_R_SESSION_LOG_LEVEL = as.numeric(Sys.getenv("LEAP_R_SESSION_LOG_LEVEL"))
ENV_LEAP_R_FORCED_GC_INTERVAL = as.numeric(Sys.getenv("LEAP_R_FORCED_GC_INTERVAL"))

leap5:::leap.init(
    dbConnectionParams = fromJSON(ENV_LEAP_R_DB_CONNECTION),
    publicDir = ENV_LEAP_R_PUBLIC_DIR,
    platformUrl = ENV_LEAP_R_PLATFORM_URL,
    appUrl = ENV_LEAP_R_APP_URL,
    maxExecTime = as.numeric(ENV_LEAP_R_MAX_EXEC_TIME),
    maxIdleTime = as.numeric(ENV_LEAP_R_MAX_IDLE_TIME),
    keepAliveToleranceTime = as.numeric(ENV_LEAP_R_KEEP_ALIVE_TOLERANCE_TIME),
    sessionStorage = ENV_LEAP_R_SESSION_STORAGE,
    redisConnectionParams = fromJSON(ENV_LEAP_R_REDIS_CONNECTION),
    sessionFilesExpiration = ENV_LEAP_R_SESSION_FILES_EXPIRATION
)

switch(leap$dbConnectionParams$driver,
    pdo_mysql = require("RMySQL"),
    pdo_sqlsrv = require("RSQLServer")
)

switch(ENV_LEAP_R_SESSION_STORAGE,
    redis = require("redux")
)

leap.log("starting forker listener")
queue = c()
unlink(paste0(ENV_LEAP_R_FIFO_PATH,"*.fifo"))
lastForcedGcTime = as.numeric(Sys.time())
while (T) {
    if(ENV_LEAP_R_FORCED_GC_INTERVAL >= 0) {
        currentTime = as.numeric(Sys.time())
        if(currentTime - lastForcedGcTime > ENV_LEAP_R_FORCED_GC_INTERVAL) {
            gcOutput = gc(F)
            lastForcedGcTime = currentTime
        }
    }

    fpath = ""
    if(length(queue) == 0) {
        queue = list.files(ENV_LEAP_R_FIFO_PATH, full.names=TRUE)
    }
    if(length(queue) > 0) {
        fpath = queue[1]
        queue = queue[-1]
    } else {
        Sys.sleep(0.25)
        next
    }
    con = fifo(fpath, blocking=TRUE, open="rt")
    response = readLines(con, warn = FALSE, n = 1, ok = TRUE)
    close(con)
    rm(con)
    unlink(fpath)
    rm(fpath)

    if(length(response) == 0) {
        leap.log(response, "invalid request")
        next
    }

    response = tryCatch({
        fromJSON(response)
    }, error = function(e) {
        message(e)
        message(response)
        q("no", 1)
    })

    if(is.null(response$rLogPath)) response$rLogPath = "/dev/null"

    mcparallel({
        if(ENV_LEAP_R_SESSION_LOG_LEVEL > 0) {
            sinkFile <- file(response$rLogPath, open = "at")
            #needs both types declared separately
            sink(file = sinkFile, append = TRUE, type = "output", split = FALSE)
            sink(file = sinkFile, append = TRUE, type = "message", split = FALSE)
            rm(sinkFile)
        } else {
            nullFile <- file("/dev/null", open = "at") #UNIX only
            #needs both types declared separately
            sink(file = nullFile, append = TRUE, type = "output", split = FALSE)
            sink(file = nullFile, append = TRUE, type = "message", split = FALSE)
        }
        rm(queue)

        leap$lastSubmitTime <- as.numeric(Sys.time())
        leap$lastKeepAliveTime <- as.numeric(Sys.time())
        leap5:::leap.run(
            workingDir = response$workingDir,
            client = response$client,
            sessionHash = response$sessionId,
            maxIdleTime = response$maxIdleTime,
            maxExecTime = response$maxExecTime,
            response = response$response,
            initialPort = response$initialPort,
            runnerType = response$runnerType
        )
    }, detached = TRUE)
}
leap.log("listener closing")