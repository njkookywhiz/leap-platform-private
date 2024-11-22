leap.table.query <-
function(sql, params=list(), n=-1, connection = NULL){
  if(is.null(connection)) { connection = leap$connection }
  sql <- gsub("^\\s+|\\s+$", "", sql)
  sql <- leap.table.insertParams(sql, params, connection)

  leap.log(sql)

  result <- NULL
  output <- NULL
  if(startsWith(toupper(sql), "SELECT") || startsWith(toupper(sql), "SHOW")) {
    result <- dbSendQuery(connection, sql)
    output <- fetch(result, n=n)
  } else if(startsWith(toupper(sql), "INSERT")) {
    if(leap$dbConnectionParams$driver == "pdo_sqlsrv") {
         result <- dbSendQuery(connection, paste0(sql,"; SELECT SCOPE_IDENTITY();"))
         output <- fetch(result, n=1)[1,1]
         leap$sqlsrv_last_insert_id <<- output
    } else {
        result <- dbSendStatement(connection, sql)
        output <- dbGetRowsAffected(result)
    }
  } else {
    result <- dbSendStatement(connection, sql)
    output <- dbGetRowsAffected(result)
  }

  dbClearResult(result)

  return(output)
}
