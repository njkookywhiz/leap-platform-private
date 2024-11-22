leap.var.getDynamicInputs = c.getDynamicInputs = function(){
    result = list()
    flowIndex = leap$flowIndex
    dynamicInputs = leap$flow[[flowIndex]]$globals$.dynamicInputs
    if(length(dynamicInputs) > 0) {
        for(i in 1:length(dynamicInputs)) {
            name = dynamicInputs[i]
            result[[name]] = leap$flow[[flowIndex]]$globals[[name]]
        }
    }
    return(result)
}