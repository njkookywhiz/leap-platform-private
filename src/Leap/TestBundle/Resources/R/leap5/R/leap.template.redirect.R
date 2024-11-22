leap.template.redirect = function(url) {
    leap.template.show(
        html=paste0("<script>location.href='",url,"'</script>"),
        skipOnResume=T
    )
}