leap.table.lastInsertId <-
function(connection = NULL){
  id = NULL
  if(is.null(connection)) { connection = leap$connection }
  if(leap$dbConnectionParams$driver == "pdo_sqlsrv") {
    id = leap$sqlsrv_last_insert_id
  } else {
    id = dbGetQuery(connection, "SELECT LAST_INSERT_ID();")[1,1]
  }
  return(id)
}
