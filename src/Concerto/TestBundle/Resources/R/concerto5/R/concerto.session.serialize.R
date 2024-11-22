leap.session.serialize <- function(){
    leap.log("serializing session...")

    if(leap$sessionStorage == "redis") {
        #TODO add comppression
        serialized = serialize(leap, NULL)
        expSeconds = as.numeric(leap$sessionFilesExpiration) * 24 * 60 * 60
        leap$redisConnection$SETEX(leap$session$hash, expSeconds, serialized)
    } else {
        save(leap, file=leap$sessionFile)
    }

    leap.log("session serialized")
}