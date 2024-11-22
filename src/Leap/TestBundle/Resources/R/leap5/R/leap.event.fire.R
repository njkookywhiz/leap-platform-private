leap.event.fire = function(name, args){
    leap.log(name, "event fire")
    for(fun in leap$events[[name]]) {
        do.call(fun, args, envir = .GlobalEnv)
    }
}