leap.file.getUrl = function(filename, noCache=F){
    url = paste0(leap$mediaUrl, "/", filename)
    if(noCache) {
        url = paste0(url, "?ts=",as.numeric(Sys.time()))
    }
    return(url)
}