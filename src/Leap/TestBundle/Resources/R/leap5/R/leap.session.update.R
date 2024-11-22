leap.session.update = function(){
  leap.log("updating session...")

  sql = sprintf("UPDATE TestSession SET
    status = '%s',
    timeLimit = '%s',
    error = '%s',
    updated = CURRENT_TIMESTAMP
    WHERE id='%s'",
  dbEscapeStrings(leap$connection, toString(leap$session$status)),
  dbEscapeStrings(leap$connection, toString(leap$session$timeLimit)),
  dbEscapeStrings(leap$connection, toString(leap$session$error)),
  dbEscapeStrings(leap$connection, toString(leap$session$id)))

  res = dbSendStatement(leap$connection, statement = sql)
  dbClearResult(res)
}
