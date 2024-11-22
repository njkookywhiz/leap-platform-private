leap.test.getVariables = function(testId){
  
  idField <- "test_id"
  testId <- dbEscapeStrings(leap$connection,toString(testId))
  result <- dbSendQuery(leap$connection,sprintf("SELECT id, name, value, type FROM TestVariable WHERE %s='%s'",idField,testId))
  response <- fetch(result,n=-1)

  return(response)
}
