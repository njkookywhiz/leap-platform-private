leap.template.isResponseQueued = function(){
     return(leap$runnerType == RUNNER_SERIALIZED && !is.null(leap$queuedResponse))
}
