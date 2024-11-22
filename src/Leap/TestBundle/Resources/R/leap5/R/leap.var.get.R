leap.var.get = c.get = function(name, global=F, all=F, flowIndexOffset = 0, posOffset = 0, flowIndex = NULL){
    if(posOffset != 0) {
        flowIndexOffset = posOffset
        leap.log("c.get : posOffset argument is deprecated. Use flowIndexOffset argument instead")
    }

    if(global || (leap$flowIndex == 0 && is.null(flowIndex))) {
        if(all) { return(leap$globals) }
        else return(leap$globals[[name]])
    } else {
        if(is.null(flowIndex)) {
            flowIndex = leap$flowIndex
        }
        if(all) { return(leap$flow[[flowIndex + flowIndexOffset]]$globals) }
        else return(leap$flow[[flowIndex + flowIndexOffset]]$globals[[name]])
    }
}