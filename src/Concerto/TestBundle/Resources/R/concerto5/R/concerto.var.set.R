leap.var.set = c.set = function(name, value, global=F, flowIndexOffset = 0, posOffset = 0, flowIndex = NULL){
    if(posOffset != 0) {
        flowIndexOffset = posOffset
        leap.log("c.set : posOffset argument is deprecated. Use flowIndexOffset argument instead")
    }

    if(global || (leap$flowIndex == 0 && is.null(flowIndex))) {
        leap$globals[name] <<- list(value)
    } else {
        if(is.null(flowIndex)) {
            flowIndex = leap$flowIndex
        }
        leap$flow[[flowIndex + flowIndexOffset]]$globals[name] <<- list(value)
    }
    return(value)
}
