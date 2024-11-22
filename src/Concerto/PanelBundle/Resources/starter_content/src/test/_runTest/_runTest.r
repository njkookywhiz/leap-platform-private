if(!is.list(params)) {
  params = list()
}
for(name in .dynamicInputs) {
  value = get(name)
  if(is.null(value)) {
    params[name] = list(NULL)
  } else {
    params[[name]] = value
  }
}

results = leap5:::leap.test.run(test, params=params)
for(name in .dynamicReturns) {
  assign(name, results[[name]])
}
