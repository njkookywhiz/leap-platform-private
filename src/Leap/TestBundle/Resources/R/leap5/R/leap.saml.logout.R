leap.saml.logout = function(redirectTo=NULL){
    if(is.null(redirectTo)) redirectTo = leap.session.getResumeUrl()
    url = paste0(leap$appUrl, "/api/saml/logout?redirectTo=", redirectTo)
    leap.template.redirect(url)
}