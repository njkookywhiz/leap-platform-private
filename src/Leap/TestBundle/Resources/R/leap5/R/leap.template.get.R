leap.template.get = function(templateId, cache=NULL){

  if(is.null(cache)) {
    cache = leap$cacheEnabled
  }
  if(!is.null(leap$cache$templates[[as.character(templateId)]])) {
    return(leap$cache$templates[[as.character(templateId)]])
  }

  idField <- "id"
  if(is.character(templateId)){
    idField <- "name"
  }
  templateId <- dbEscapeStrings(leap$connection,toString(templateId))
    
  result <- dbSendQuery(leap$connection,sprintf("SELECT id,name,head,html,css,js FROM ViewTemplate WHERE %s='%s'",idField,templateId))
  response <- fetch(result,n=-1)

  if(dim(response)[1] > 0){
    template = as.list(response)
    if(cache) {
        leap$cache$templates[[as.character(response$id)]] <<- template
        leap$cache$templates[[response$name]] <<- template
    }
    return(template)
  }

  return(NULL)
}
