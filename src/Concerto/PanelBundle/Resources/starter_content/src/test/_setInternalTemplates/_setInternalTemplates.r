if(!is.list(params)) {
  params = list()
}
for(.name in .dynamicInputs) {
  params[[.name]] = get(.name)
}

if(is.na(loaderTemplate) || loaderTemplate == "") { loaderTemplate = -1 }

leap.template.loader(
  templateId=loaderTemplate, 
  html=loaderTemplateHtml,
  params=params
)