leap.session.getResumeUrl = function(){
    url = paste0(leap$appUrl, "/test/session/", leap$session$hash)
    return(url)
}