leap.template.loader <-
function(
    templateId=-1, 
    html="", 
    head="", 
    params=list()){
  if(!is.list(params)) stop("'params' must be a list!")
  if(templateId==-1 && html=="") stop("templateId or html must be declared")
  
  template <- leap.template.get(templateId)

  params = leap.template.makeParams(params)

  if(html!=""){
    leap$response$loaderHead <<- leap.template.insertParams(head,params)
    leap$response$loaderHtml <<- leap.template.insertParams(html,params)
    leap$response$loaderCss <<- ""
    leap$response$loaderJs <<- ""
  } else {
    if(is.null(template)) stop(paste("Template #",templateId," not found!",sep=''))
    leap$response$loaderHead <<- leap.template.insertParams(template$head,params)
    leap$response$loaderCss <<- leap.template.insertParams(template$css,params)
    leap$response$loaderJs <<- leap.template.insertParams(template$js,params)
    leap$response$loaderHtml <<- leap.template.insertParams(template$html,params)
  }

  leap$templateParams <<- params
}
