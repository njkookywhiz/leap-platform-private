leap.event.add = function(name, fun){
    leap$events[[name]] <<- c(leap$events[[name]], fun)
}
