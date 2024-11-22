leap.init = function(dbConnectionParams, publicDir, platformUrl, appUrl, maxExecTime, maxIdleTime, keepAliveToleranceTime, sessionStorage, redisConnectionParams, sessionFilesExpiration){
    options(digits.secs = 6)
    if(Sys.info()['sysname'] != "Windows") {
        options(encoding='UTF-8')
        Sys.setlocale("LC_ALL","en_US.utf8")
    } else {
        Sys.setlocale("LC_ALL","English")
    }

    assign(
        "fromJSON",
        function(txt, simplifyVector = FALSE, ...) {
            result = jsonlite::fromJSON(txt, simplifyVector = simplifyVector, ...)
            return(result)
        },
        envir = .GlobalEnv
    )

    assign(
        "toJSON",
        function(x, auto_unbox = TRUE, ...) {
            result = jsonlite::toJSON(x, auto_unbox = auto_unbox, ...)
            result = as.character(result)
            return(result)
        },
        envir = .GlobalEnv
    )

    SOURCE_PROCESS <<- 1
    SOURCE_SERVER <<- 2

    RESPONSE_VIEW_TEMPLATE <<- 0
    RESPONSE_FINISHED <<- 1
    RESPONSE_SUBMIT <<- 2
    RESPONSE_STOP <<- 3
    RESPONSE_STOPPED <<- 4
    RESPONSE_VIEW_FINAL_TEMPLATE <<- 5
    RESPONSE_KEEPALIVE_CHECKIN <<- 10
    RESPONSE_SESSION_LOST <<- 14
    RESPONSE_WORKER <<- 15
    RESPONSE_RESUME <<- 16
    RESPONSE_ERROR <<- -1

    STATUS_RUNNING <<- 0
    STATUS_STOPPED <<- 1
    STATUS_FINALIZED <<- 2
    STATUS_ERROR <<- 3

    RUNNER_PERSISTENT <<- 0
    RUNNER_SERIALIZED <<- 1

    tempdir(T)

    leap <<- list()
    leap$cache <<- list(tests=list(), templates=list(), tables=list())
    leap$cacheEnabled <<- T
    leap$globals <<- list()
    leap$templateParams <<- list()
    leap$globalTemplateParams <<- list()
    leap$flow <<- list()
    leap$flowIndex <<- 0
    leap$bgWorkers <<- list()
    leap$queuedResponse <<- NULL
    leap$skipTemplateOnResume <<- F
    leap$response <<- list()

    leap$publicDir <<- publicDir
    leap$platformUrl <<- platformUrl
    leap$appUrl <<- appUrl
    leap$mediaUrl <<- paste0(platformUrl, "/bundles/leappanel/files")
    leap$maxExecTime <<- maxExecTime
    leap$maxIdleTime <<- maxIdleTime
    leap$keepAliveToleranceTime <<- keepAliveToleranceTime
    leap$lastSubmitTime <<- as.numeric(Sys.time())
    leap$lastKeepAliveTime <<- as.numeric(Sys.time())
    leap$dbConnectionParams <<- dbConnectionParams
    leap$sessionStorage <<- sessionStorage
    leap$redisConnectionParams <<- redisConnectionParams
    leap$sessionFilesExpiration <<- sessionFilesExpiration

    leap$events <<- list(
        onBeforeTemplateShow=NULL,
        onTemplateSubmit=NULL
    )
}