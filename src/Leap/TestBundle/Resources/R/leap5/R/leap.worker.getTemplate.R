leap.worker.getTemplate = function(response) {
    template <- leap.template.get(response$templateId)
    if (is.null(template)) return(NA)

    leap.log(template)

    content = list(
        head=leap.template.insertParams(template$head, response$params),
        css=leap.template.insertParams(template$css, response$params),
        js=leap.template.insertParams(template$js, response$params),
        html=leap.template.insertParams(template$html, response$params)
    )
    return(content)
}