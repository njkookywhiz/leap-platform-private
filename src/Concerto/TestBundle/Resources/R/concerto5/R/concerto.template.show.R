leap.template.show = function(
    templateId=-1,
    html="",
    head="",
    params=list(),
    timeLimit=0,
    finalize=F,
    removeMissingParams=T,
    bgWorkers=list(),
    skipOnResume=F,
    cookies=list(),
    protectedFilesAccess=F,
    sessionFilesAccess=F
) {
    leap$skipTemplateOnResume <<- skipOnResume
    if (! is.null(leap$queuedResponse)) {
        response = leap$queuedResponse
        leap$queuedResponse <<- NULL
        return(response)
    }

    if (! is.list(params)) stop("'params' must be a list!")
    if (templateId == -1 && html == "") stop("templateId or html must be declared")

    params = leap.template.makeParams(params)

    leap$response$protectedFilesAccess <<- protectedFilesAccess
    leap$response$sessionFilesAccess <<- sessionFilesAccess
    if (html != "") {
        leap$response$templateHead <<- leap.template.insertParams(head, params, removeMissing = removeMissingParams)
        leap$response$templateHtml <<- leap.template.insertParams(html, params, removeMissing = removeMissingParams)
        leap$response$templateCss <<- ""
        leap$response$templateJs <<- ""
    } else {
        template <- leap.template.get(templateId)
        if (is.null(template)) stop(paste("Template #", templateId, " not found!", sep = ''))
        leap$response$templateHead <<- leap.template.insertParams(template$head, params, removeMissing = removeMissingParams)
        leap$response$templateCss <<- leap.template.insertParams(template$css, params, removeMissing = removeMissingParams)
        leap$response$templateJs <<- leap.template.insertParams(template$js, params, removeMissing = removeMissingParams)
        leap$response$templateHtml <<- leap.template.insertParams(template$html, params, removeMissing = removeMissingParams)
    }
    leap$session$timeLimit <<- timeLimit

    workers = list(
        getTemplate = leap.worker.getTemplate
    )
    for(name in ls(bgWorkers)) {
        workers[[name]] = bgWorkers[[name]]
    }
    leap$bgWorkers <<- workers

    leap$templateParams <<- params

    leap.event.fire("onBeforeTemplateShow", list(params = leap$templateParams))

    data = leap$response
    data$templateParams = leap$templateParams
    data$cookies = cookies
    if(!is.null(leap$lastResponse$values$submitId)) {
        data$lastSubmitId = as.numeric(leap$lastResponse$values$submitId)
    }
    if (finalize) {
        leap5:::leap.session.stop(STATUS_FINALIZED, RESPONSE_VIEW_FINAL_TEMPLATE, data)
    } else {
        repeat {
            leap5:::leap.session.update()
            leap$templateParams <<- list()

            leap$lastSubmitResult <<- data
            leap$lastSubmitId <<- data$lastSubmitId

            if (leap$runnerType == RUNNER_SERIALIZED) {
                leap5:::leap.session.serialize()
            }

            leap5:::leap.server.respond(RESPONSE_VIEW_TEMPLATE, data)
            leap$response <<- list()

            if (leap$runnerType == RUNNER_SERIALIZED) {
                leap5:::leap.session.stop(STATUS_RUNNING)
            }

            response = leap5:::leap.server.listen(skipOnResume)
            if(!is.null(response)) return(response)
        }
    }
}
