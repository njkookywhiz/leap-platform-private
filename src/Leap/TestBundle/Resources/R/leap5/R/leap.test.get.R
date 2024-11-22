leap.test.get = function(testId, cache=NULL, includeSubObjects=F){
  if(is.null(cache)) {
    cache = leap$cacheEnabled
  }

  test = leap$cache$tests[[as.character(testId)]]
  if(!is.null(test)) {
    if(includeSubObjects && is.null(test$variables)) {
      test$variables = leap5:::leap.test.getVariables(test$id)
      if(test$type == 2) {
        test$nodes <- leap5:::leap.test.getNodes(test$id)
        test$connections <- leap5:::leap.test.getConnections(test$id)
        test$ports <- leap5:::leap.test.getPorts(test$id)
      }
      leap$cache$tests[[as.character(test$id)]] <<- test
      leap$cache$tests[[test$name]] <<- test
    }
    return(test)
  }

  idField <- "id"
  if(is.character(testId)){
    idField <- "name"
  }

  testID <- dbEscapeStrings(leap$connection,toString(testId))
  result <- dbSendQuery(leap$connection,sprintf("
  SELECT
  id,
  name,
  code,
  type,
  sourceWizard_id
  FROM Test
  WHERE %s='%s'
  ",idField,testId))
  response <- fetch(result,n=-1)

  if(dim(response)[1] > 0) {
    test = as.list(response)
    if(includeSubObjects && is.null(test$variables)) {
      test$variables <- leap5:::leap.test.getVariables(test$id)
      if(test$type == 1) {
        result = dbSendQuery(leap$connection, paste0("
        SELECT test_id FROM TestWizard WHERE id=",dbEscapeStrings(leap$connection,toString(test$sourceWizard_id)),"
        "))
        sourceTestId = fetch(result,n=-1)
        test$sourceTest <- leap.test.get(sourceTestId, cache, includeSubObjects)
      }
      if(test$type == 2) {
        test$nodes <- leap5:::leap.test.getNodes(test$id)
        test$connections <- leap5:::leap.test.getConnections(test$id)
        test$ports <- leap5:::leap.test.getPorts(test$id)
      }
    }

    if(cache) {
        leap$cache$tests[[as.character(test$id)]] <<- test
        leap$cache$tests[[test$name]] <<- test
    }
  }

  return(test)
}
