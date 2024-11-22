leap.redis.connect = function(host, port, password){
    leap.log("connecting with redis...")

    redisPass = NULL
    if(password != "") { redisPass = password }

    hiredis(
        host = host,
        port = port,
        password = redisPass
    )
}