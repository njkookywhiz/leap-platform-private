leap.template.makeParams = function(params=list()){
  finalParams = leap$globalTemplateParams
  for(name in ls(params)) {
    if(is.null(params[[name]])) {
      finalParams[[name]] = list(NULL)
    } else {
      finalParams[[name]] = params[[name]]
    }
  }
  return(finalParams)
}
