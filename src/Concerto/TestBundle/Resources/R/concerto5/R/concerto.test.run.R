leap.test.run <-
function(testId, params=list(), extraReturns=c()) {
    test <- leap.test.get(testId, includeSubObjects=T)
    if (is.null(test)) stop(paste("Test #", testId, " not found!", sep = ''))
    leap.log(paste0("running test #", test$id, ": ", test$name, " ..."))

    getParams = function(params) {
        if (dim(test$variables)[1] > 0) {
            for (i in 1 : dim(test$variables)[1]) {
                if (!is.null(test$variables[i, "value"]) &&
                    test$variables[i, "type"] == 0) {

                    if(!(test$variables[i, "name"] %in% names(params))) {
                        params[[test$variables[i, "name"]]] = test$variables[i, "value"]
                    }
                }
            }
        }

        if(!(".inputs" %in% ls(params, all.names=T))) {
            params[[".inputs"]] = ls(params, all.names=T)
        }
        if(!(".returns" %in% ls(params, all.names=T))) {
            params[[".returns"]] = c()
            if (dim(test$variables)[1] > 0) {
                for (i in 1 : dim(test$variables)[1]) {
                    if (test$variables[i, "type"] == 1) {
                        params[[".returns"]] = c(params[[".returns"]], test$variables[i, "name"])
                    }
                }
            }
        }
        if(!(".branches" %in% ls(params, all.names=T))) {
            params[[".branches"]] = c()
            if (dim(test$variables)[1] > 0) {
                for (i in 1 : dim(test$variables)[1]) {
                    if (test$variables[i, "type"] == 2) {
                        params[[".branches"]] = c(params[[".branches"]], test$variables[i, "name"])
                    }
                }
            }
        }
        if(!(".dynamicInputs" %in% ls(params, all.names=T))) { params[".dynamicInputs"] = list(NULL) }
        if(!(".dynamicReturns" %in% ls(params, all.names=T))) { params[".dynamicReturns"] = list(NULL) }
        if(!(".dynamicBranches" %in% ls(params, all.names=T))) { params[".dynamicBranches"] = list(NULL) }
        return(params)
    }

    r <- list()
    flowIndex = length(leap$flow)
    leap$flowIndex <<- flowIndex
    if(leap$resuming) {
        if (test$type != 1) {
            leap$resumeIndex <<- leap$resumeIndex + 1
        }
        flowIndex = leap$resumeIndex
        leap$flowIndex <<- flowIndex
        params = leap$flow[[flowIndex]]$params
    } else {
        params = getParams(params)
        if (test$type != 1) {
            flowIndex = flowIndex + 1
            leap$flowIndex <<- flowIndex
            globals = list()
            if(!is.null(leap$passedGlobals)) {
                globals = leap$passedGlobals
                leap$passedGlobals <<- NULL
            }
            leap$flow[[flowIndex]] <<- list(
                id = test$id,
                type = test$type,
                params = params,
                globals = globals
            )
            if (length(params) > 0) {
                for (param in ls(params, all.names=T)) {
                    c.set(param,  params[[param]])
                }
            }
        }
    }

    if (test$type == 1) {
        #wizard
        return(leap.test.run(test$sourceTest$id, params, extraReturns))
    } else if (test$type == 0) {
        #code

        getCodeTestEnv = function(params) {
            testenv = new.env()
            if (length(params) > 0) {
                for (param in ls(params, all.names=T)) {
                    assign(param, params[[param]], envir = testenv)
                }
            }
            return(testenv)
        }

        testenv = getCodeTestEnv(params)
        sanitizedCode = leap.test.sanitizeSource(test$code)
        eval(parse(text = sanitizedCode), envir = testenv)

        if (dim(test$variables)[1] > 0) {
            for (i in 1 : dim(test$variables)[1]) {
                if (test$variables[i, "type"] != 1) { next }
                if (exists(test$variables[i, "name"], envir = testenv)) {
                    r[[test$variables[i, "name"]]] <- get(test$variables[i, "name"], envir = testenv)
                } else if (!is.null(test$variables[i, "value"])) {
                    r[[test$variables[i, "name"]]] <- test$variables[i, "value"]
                }
            }
        }
        if(length(extraReturns) > 0) {
             for (i in 1 : length(extraReturns)) {
                if (exists(extraReturns[i], envir = testenv)) {
                    r[[extraReturns[i]]] <- get(extraReturns[i], envir = testenv)
                }
            }
        }
    } else {
        #flow
        evalPortValue = function(port, inserts = list()) {
            value = port$value
            if(port$pointer == 1) {
                pointerValue = c.get(port$pointerVariable)
                if(!is.null(pointerValue)) {
                    return(pointerValue)
                }
            } else {
                latestDataConnection = NULL
                latestDataConnectionExecIndex = 0
                for (connection_id in ls(leap$flow[[flowIndex]]$connections)) {
                    connection = leap$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                    if (!is.na(connection$destinationPort_id) && connection$destinationPort_id == port$id) {
                        sourceNode = leap$flow[[flowIndex]]$nodes[[as.character(connection$sourceNode_id)]]
                        if(!is.null(sourceNode$execIndex) && sourceNode$execIndex > latestDataConnectionExecIndex) {
                            latestDataConnection = connection
                            latestDataConnectionExecIndex = sourceNode$execIndex
                        }
                    }
                }

                if(!is.null(latestDataConnection)) {
                    srcPort = leap$flow[[flowIndex]]$ports[[as.character(latestDataConnection$sourcePort_id)]]
                    dstPort = leap$flow[[flowIndex]]$ports[[as.character(latestDataConnection$destinationPort_id)]]

                    func = paste0("retFunc = function(", srcPort$name, "){ ", latestDataConnection$returnFunction, " }")
                    sanitizedCode = leap.test.sanitizeSource(func)
                    eval(parse(text = sanitizedCode))
                    value = retFunc(srcPort$value)
                    leap$flow[[flowIndex]]$ports[[as.character(latestDataConnection$destinationPort_id)]]['value'] <<- list(value)

                    if (!is.null(value)) {
                        return(value)
                    }
                }
            }

            value = getPortDefaultValue(port, inserts)
            leap$flow[[flowIndex]]$ports[[as.character(port$id)]]['value'] <<- list(value)
            return(value)
        }

        getPortDefaultValue = function(port, inserts = list()) {
            value = port$defaultValue
            if (port$string == 0) {
                portEnv = new.env()
                for(insertName in ls(inserts)) {
                    assign(insertName, inserts[[insertName]], envir=portEnv)
                }
                sanitizedCode = leap.test.sanitizeSource(value)
                return(eval(parse(text = sanitizedCode), envir=portEnv))
            } else {
                value = leap.template.insertParams(value, inserts, removeMissing=F)
                return(value)
            }
        }

        runNode = function(node){
            currentExecIndex = leap$flow[[flowIndex]]$execIndex
            if(is.null(currentExecIndex)) {
                leap$flow[[flowIndex]]$execIndex <<- 1
            } else {
                leap$flow[[flowIndex]]$execIndex <<- leap$flow[[flowIndex]]$execIndex + 1
            }
            leap$flow[[flowIndex]]$nodes[[as.character(node$id)]]$execIndex <<- leap$flow[[flowIndex]]$execIndex

            r = list()

            #PARAMS
            node_params = list(
                .inputs=c(),
                .returns=c(),
                .branches=c(),
                .dynamicInputs=c(),
                .dynamicReturns=c(),
                .dynamicBranches=c()
            )
            dynamicInputs = list()
            dynamicReturns = c()
            dynamicBranches = c()

            for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]
                if (port$node_id != node$id) next

                if (port$type == 0 && port$dynamic == 1) {
                    portValue = evalPortValue(port)
                    node_params[port$name] = list(portValue)
                    dynamicInputs[port$name] = list(portValue)
                    node_params[[".inputs"]] = c(node_params[[".inputs"]], port$name)
                    node_params[[".dynamicInputs"]] = c(node_params[[".dynamicInputs"]], port$name)
                } else if (port$type == 1) {
                    if (port$dynamic == 1) {
                        dynamicReturns = c(dynamicReturns, port$name)
                        node_params[[".dynamicReturns"]] = c(node_params[[".dynamicReturns"]], port$name)
                    }
                    node_params[[".returns"]] = c(node_params[[".returns"]], port$name)
                } else if (port$type == 2) {
                    if (port$dynamic == 1) {
                        dynamicBranches = c(dynamicBranches, port$name)
                        node_params[[".dynamicBranches"]] = c(node_params[[".dynamicBranches"]], port$name)
                    }
                    node_params[[".branches"]] = c(node_params[[".branches"]], port$name)
                }
            }
            for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]
                if (port$node_id == node$id && port$type == 0 && port$dynamic == 0) {
                    portValue = evalPortValue(port, dynamicInputs)
                    node_params[port$name] = list(portValue)
                    node_params[[".inputs"]] = c(node_params[[".inputs"]], port$name)
                }
            }

            #EXECUTION, RETURNS
            if (node$type == 0) {
                node_returns = leap.test.run(node$sourceTest_id, params = node_params, extraReturns=dynamicReturns)

                for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                    port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == 1) {
                        portValue = node_returns[[port$name]]
                        leap$flow[[flowIndex]]$ports[[as.character(port$id)]]['value'] <<- list(portValue)
                        if(port$pointer == 1) {
                            c.set(port$pointerVariable, portValue)
                        }
                    }
                }
            } else if (node$type == 1) {
                for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                    port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]
                    if (port$node_id == node$id && port$type == 1) {
                        portValue = params[[port$name]]
                        leap$flow[[flowIndex]]$ports[[as.character(port$id)]]['value'] <<- list(portValue)
                        if(port$pointer == 1) {
                            c.set(port$pointerVariable, portValue)
                        }
                    }
                }
            } else if (node$type == 2) {
                r = node_params
            }

            #BRANCH
            if (node$type != 2) {
                branch_port = NULL
                branch_name = NA
                for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                    port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]

                    if (port$node_id == node$id &&
                        port$type == 1 &&
                        port$name == ".branch") {
                        branch_name = port$value
                        break
                    }
                }

                for (port_id in ls(leap$flow[[flowIndex]]$ports)) {
                    port = leap$flow[[flowIndex]]$ports[[as.character(port_id)]]

                    if (port$node_id == node$id && port$type == 2) {
                        if (is.null(branch_name) || is.na(branch_name)) {
                            branch_port = port
                            break
                        } else {
                            if (branch_name == port$name) {
                                branch_port = port
                                break
                            }
                        }
                    }
                }

                for (connection_id in ls(leap$flow[[flowIndex]]$connections)) {
                    connection = leap$flow[[flowIndex]]$connections[[as.character(connection_id)]]
                    if (!is.null(branch_port) && !is.na(connection$sourcePort_id) && connection$sourcePort_id == branch_port$id) {
                        leap$flow[[flowIndex]]$nextNode <<- leap$flow[[flowIndex]]$nodes[[as.character(connection$destinationNode_id)]]
                        break
                    } else if(node$type == 1 && connection$sourceNode_id == node$id && is.na(connection$sourcePort_id)) {
                        leap$flow[[flowIndex]]$nextNode <<- leap$flow[[flowIndex]]$nodes[[as.character(connection$destinationNode_id)]]
                        break
                    }
                }
            }

            return(r)
        }

        #persist flow
        if(!leap$resuming) {
            leap$flow[[flowIndex]]$nodes <<- list()
            leap$flow[[flowIndex]]$connections <<- list()
            leap$flow[[flowIndex]]$ports <<- list()

            if (dim(test$nodes)[1] > 0) {
                for (i in 1 : (dim(test$nodes)[1])) {
                    leap$flow[[flowIndex]]$nodes[[as.character(test$nodes[i, "id"])]] <<- as.list(test$nodes[i,])
                }
            }
            if (dim(test$connections)[1] > 0) {
                for (i in 1 : (dim(test$connections)[1])) {
                    leap$flow[[flowIndex]]$connections[[as.character(test$connections[i, "id"])]] <<- as.list(test$connections[i,])
                }
            }
            if (dim(test$ports)[1] > 0) {
                for (i in 1 : (dim(test$ports)[1])) {
                    leap$flow[[flowIndex]]$ports[[as.character(test$ports[i, "id"])]] <<- as.list(test$ports[i,])
                }
            }

            #find begin and finish nodes
            beginNode = NULL
            if (dim(test$nodes)[1] > 0) {
                for (i in 1 : (dim(test$nodes)[1])) {
                    if (test$nodes[i, "type"] == 1) {
                      beginNode = as.list(test$nodes[i,])
                      break
                    }
                }
            }

            leap$flow[[flowIndex]]$currentNode <<- NULL
            leap$flow[[flowIndex]]$nextNode <<- beginNode
        } else {
            leap$flow[[flowIndex]]$nextNode <<- leap$flow[[flowIndex]]$currentNode
            if(length(leap$flow) == flowIndex + 1) {
                leap$resuming <<- F
                leap$passedGlobals <<- leap$flow[[flowIndex + 1]]$globals
                leap$flow[[flowIndex + 1]] <<- NULL
            }
        }

        finishNodeExecuted = F
        while (!is.null(leap$flow[[flowIndex]]$nextNode)) {
            node = leap$flow[[flowIndex]]$nextNode

            leap$flow[[flowIndex]]$currentNode <<- node
            leap$flow[[flowIndex]]$nextNode <<- NULL
            r = runNode(node)
            if(node$type == 2) { finishNodeExecuted = T }
        }
        if(!finishNodeExecuted) {
            finishNode = NULL
            if (dim(test$nodes)[1] > 0) {
                for (i in 1 : (dim(test$nodes)[1])) {
                    if (test$nodes[i, "type"] == 2) {
                      finishNode = as.list(test$nodes[i,])
                      break
                    }
                }
            }
            r = runNode(finishNode)
        }

        if(length(extraReturns) > 0) {
            for (i in 1 : length(extraReturns)) {
                name = extraReturns[i]
                val = c.get(name)
                r[name] = list(val)
            }
        }
    }

    leap$flow[[flowIndex]] <<- NULL
    leap$flowIndex <<- length(leap$flow)

    leap.log(paste0("test #", test$id, ": ", test$name, " finished"))
    return(r)
}