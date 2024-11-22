leap.saml.login = function(redirectTo=NULL){
    if(is.null(redirectTo)) redirectTo = leap.session.getResumeUrl()
    url = paste0(leap$appUrl, "/api/saml/login?redirectTo=", redirectTo)
    leap.template.redirect(url)
}