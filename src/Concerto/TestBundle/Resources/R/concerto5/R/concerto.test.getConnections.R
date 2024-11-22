leap.test.getConnections = function(testId){
  
  idField <- "flowTest_id"
  testId <- dbEscapeStrings(leap$connection,toString(testId))
  result <- dbSendQuery(leap$connection,sprintf("
    SELECT id, sourceNode_id, sourcePort_id, destinationNode_id, destinationPort_id, returnFunction 
    FROM TestNodeConnection 
    WHERE %s='%s'",idField,testId))
  response <- fetch(result,n=-1)

  return(response)
}
