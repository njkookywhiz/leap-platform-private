leap.event.remove = function(name, fun){
    indicesToRemove = c()
    if(length(leap$events[[name]]) > 0) {
        i = 0
        for(currentFun in leap$events[[name]]) {
            i = i + 1
            if(identical(currentFun, fun)) {
                indicesToRemove = c(indicesToRemove, i)
            }
        }
    }

    if(length(indicesToRemove) > 0) {
        leap$events[[name]] <<- leap$events[[name]][-indicesToRemove]
    }
}
