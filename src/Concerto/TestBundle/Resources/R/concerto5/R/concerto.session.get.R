leap.session.get = function(sessionHash){
  sessionHash <- dbEscapeStrings(leap$connection,toString(sessionHash))
  result <- dbSendQuery(leap$connection,sprintf("SELECT
                                                    id, 
                                                    test_id,
                                                    timeLimit,
                                                    status,
                                                    params,
                                                    error,
                                                    clientIp,
                                                    clientBrowser,
                                                    submitterPort,
                                                    hash
                                                    FROM TestSession WHERE hash='%s'",sessionHash))
  response <- fetch(result,n=-1)
  return(response)
}
