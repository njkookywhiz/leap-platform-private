leap.table.get <-
function(tableId, cache=NULL){

  if(is.null(cache)) {
    cache = leap$cacheEnabled
  }
  if(!is.null(leap$cache$tables[[as.character(tableId)]])) {
    return(leap$cache$tables[[as.character(tableId)]])
  }

  objField <- "id"
  if(is.character(tableId)){
    objField <- "name"
  }

  tableId <- dbEscapeStrings(leap$connection,toString(tableId))
  result <- dbSendQuery(leap$connection,sprintf("SELECT id,name FROM Table WHERE %s='%s'",objField,tableId))
  response <- fetch(result,n=-1)

  if(dim(response)[1] > 0){
    table = as.list(response)
    if(cache) {
        leap$cache$tables[[as.character(response$id)]] <<- table
        leap$cache$tables[[response$name]] <<- table
    }
    return(table)
  }

  return(NULL)
}
