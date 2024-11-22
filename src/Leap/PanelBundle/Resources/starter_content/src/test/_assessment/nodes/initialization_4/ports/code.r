getIndicedColumnsNum = function(tableName, firstColumnName) {
  columnPrefix = substring(firstColumnName, 1, nchar(firstColumnName) - 1)

  columns = leap.table.query("SHOW COLUMNS FROM {{tableName}} LIKE '{{columnPrefix}}%'", params=list(
    tableName=tableName,
    columnPrefix=columnPrefix
  ))[,"Field"]

  i=1;
  while(paste0(columnPrefix,i) %in% columns) {
    i=i+1
  }
  return(i-1)
}

getExtraFieldsSql = function(table, extraFields) {
  columns = leap.table.query("SHOW COLUMNS FROM {{table}}", params=list(
    table=table
  ))[,"Field"]
  extraFields = fromJSON(extraFields)
  if(length(extraFields) > 0) {
    for(i in length(extraFields):1) {
      if(!(extraFields[[i]]$name %in% columns)) {
        extraFields[[i]] = NULL
      }
    }
  }
  extraFieldsNames = lapply(extraFields, function(extraField) { return(extraField$name) })
  extraFieldsSql = paste(extraFieldsNames, collapse=", ")
  if(extraFieldsSql != "") { extraFieldsSql = paste0(", ", extraFieldsSql) }
  return(extraFieldsSql)
}

getIndicedColumnsSql = function(firstColumnName, num, aliasPrefix) {
  if(is.na(firstColumnName) || is.null(firstColumnName) || firstColumnName == "") {
    return("NULL")
  }

  columnNamePrefix = substring(firstColumnName, 1, nchar(firstColumnName) - 1)
  columns = c()
  for(i in 1:num) {
    columnName = paste0(columnNamePrefix, i)
    alias = paste0(aliasPrefix, i)
    columns = c(columns, paste0(columnName, " AS ", alias))
  }
  return(paste(columns, collapse=", "))
}

convertFromFlat = function(items, responseColumnsNum) {
  itemsNum = dim(items)[1]
  if(itemsNum == 0) { return(items) }

  defaultScore = 0
  defaultPainMannequinGender = "male"
  defaultPainMannequinAreaMultiMarks = 1
  defaultGracelyScaleShow = "both"
  defaultOptionsRandomOrder = 0
  defaultOptionsColumnsNum = 0

  for(i in 1:itemsNum) {
    item = items[i,]

    options = list()
    for(j in 1:responseColumnsNum) {
      label = item[[paste0("responseLabel",j)]]
      value = item[[paste0("responseValue",j)]]
      fixedIndex = item[[paste0("responseFixedIndex",j)]]

      if(is.na(label) || is.na(value)) { next }
      options[[j]] = list(
        label=label,
        value=value,
        fixedIndex=fixedIndex
      )
    }

    scoreMap = list()
    for(j in 1:responseColumnsNum) {
      score = item[[paste0("responseScore",j)]]
      value = item[[paste0("responseValue",j)]]
      trait = item[[paste0("responseTrait",j)]]

      if(is.na(score) || is.na(value)) { next }
      scoreMap[[j]] = list(
        score=score,
        value=value,
        trait=trait
      )
    }

    responseOptions = list(
      type=item$type,
      optionsRandomOrder=item$optionsRandomOrder,
      optionsColumnsNum=item$optionsColumnsNum,
      painMannequinGender=item$painMannequinGender,
      painMannequinAreaMultiMarks=item$painMannequinAreaMultiMarks,
      gracelyScaleShow=item$gracelyScaleShow,
      options=options,
      scoreMap=scoreMap,
      defaultScore=defaultScore
    )

    if(is.null(responseOptions$painMannequinGender) || is.na(responseOptions$painMannequinGender)) { 
      responseOptions$painMannequinGender = defaultPainMannequinGender
    }
    if(is.null(responseOptions$painMannequinAreaMultiMarks) || is.na(responseOptions$painMannequinAreaMultiMarks)) { 
      responseOptions$painMannequinAreaMultiMarks = defaultPainMannequinAreaMultiMarks
    }
    if(is.null(responseOptions$gracelyScaleShow) || is.na(responseOptions$gracelyScaleShow)) { 
      responseOptions$gracelyScaleShow = defaultGracelyScaleShow
    }
    if(is.null(responseOptions$optionsRandomOrder) || is.na(responseOptions$optionsRandomOrder)) { 
      responseOptions$optionsRandomOrder = defaultOptionsRandomOrder
    }
    if(is.null(responseOptions$optionsColumnsNum) || is.na(responseOptions$optionsColumnsNum)) { 
      responseOptions$optionsColumnsNum = defaultOptionsColumnsNum
    }

    items[i, "responseOptions"] = toJSON(responseOptions)
  }

  return(items)
}

getItems = function(itemBankType, itemBankItems, itemBankTable, itemBankFlatTable, extraFields, paramsNum){
  items = NULL
  itemSetFilterEnabled = !is.na(settings$itemSet) && !is.null(settings$itemSet) && settings$itemSet != ""

  if(itemBankType == "table") {
    tableMap = fromJSON(itemBankTable)

    table = tableMap$table
    questionColumn = tableMap$columns$question
    responseOptionsColumn = tableMap$columns$responseOptions
    p1Column = tableMap$columns$p1
    traitColumn = tableMap$columns$trait
    fixedIndexColumn = tableMap$columns$fixedIndex
    itemSetColumn = tableMap$columns$itemSet

    instructionsColumn = tableMap$columns$instructions
    if(is.null(instructionsColumn) || is.na(instructionsColumn) || instructionsColumn == "") {
      instructionsColumn = "NULL"
    }

    skippableColumn = tableMap$columns$skippable
    if(is.null(skippableColumn) || is.na(skippableColumn) || skippableColumn == "") {
      skippableColumn = "NULL"
    }

    extraFieldsSql = getExtraFieldsSql(table, extraFields)
    parametersSql = getIndicedColumnsSql(p1Column, paramsNum, "p")

    sql = "
SELECT 
id, 
{{questionColumn}} AS question, 
{{responseOptionsColumn}} AS responseOptions,
{{parametersSql}},
{{traitColumn}} AS trait,
{{fixedIndexColumn}} AS fixedIndex,
{{instructionsColumn}} AS instructions,
{{skippableColumn}} AS skippable
{{extraFieldsSql}}
FROM {{table}}
"

    #item set filter
    itemSetFilterEnabled = itemSetFilterEnabled && !is.null(itemSetColumn) && !is.na(itemSetColumn) && itemSetColumn != ""
    if(itemSetFilterEnabled) {
      sql = paste0(sql, "WHERE {{itemSetColumn}}='{{itemSet}}'")
    }

    items = leap.table.query(sql, list(
      questionColumn=questionColumn,
      responseOptionsColumn=responseOptionsColumn,
      parametersSql=parametersSql,
      traitColumn=traitColumn,
      fixedIndexColumn=fixedIndexColumn,
      skippableColumn=skippableColumn,
      instructionsColumn=instructionsColumn,
      extraFieldsSql=extraFieldsSql,
      itemSetColumn=itemSetColumn,
      itemSet=settings$itemSet,
      table=table
    ))
  }

  if(itemBankType == "flatTable") {
    tableMap = fromJSON(itemBankFlatTable)

    table = tableMap$table
    questionColumn = tableMap$columns$question
    p1Column = tableMap$columns$p1
    traitColumn = tableMap$columns$trait
    fixedIndexColumn = tableMap$columns$fixedIndex
    itemSetColumn = tableMap$columns$itemSet

    instructionsColumn = tableMap$columns$instructions
    if(is.null(instructionsColumn) || is.na(instructionsColumn) || instructionsColumn == "") {
      instructionsColumn = "NULL"
    }

    skippableColumn = tableMap$columns$skippable
    if(is.null(skippableColumn) || is.na(skippableColumn) || skippableColumn == "") {
      skippableColumn = "NULL"
    }

    responseLabel1Column = tableMap$columns$responseLabel1
    responseValue1Column = tableMap$columns$responseValue1
    responseScore1Column = tableMap$columns$responseScore1
    responseTrait1Column = tableMap$columns$responseTrait1
    responseFixedIndex1Column = tableMap$columns$responseFixedIndex1
    typeColumn = tableMap$columns$type

    gracelyScaleShowColumn = tableMap$columns$gracelyScaleShow
    if(is.null(gracelyScaleShowColumn) || is.na(gracelyScaleShowColumn) || gracelyScaleShowColumn == "") {
      gracelyScaleShowColumn = "NULL"
    }

    painMannequinGenderColumn = tableMap$columns$painMannequinGender
    if(is.null(painMannequinGenderColumn) || is.na(painMannequinGenderColumn) || painMannequinGenderColumn == "") {
      painMannequinGenderColumn = "NULL"
    }

    painMannequinAreaMultiMarksColumn = tableMap$columns$painMannequinAreaMultiMarks
    if(is.null(painMannequinAreaMultiMarksColumn) || is.na(painMannequinAreaMultiMarksColumn) || painMannequinAreaMultiMarksColumn == "") {
      painMannequinAreaMultiMarksColumn = "NULL"
    }

    optionsRandomOrderColumn = tableMap$columns$optionsRandomOrder
    if(is.null(optionsRandomOrderColumn) || is.na(optionsRandomOrderColumn) || optionsRandomOrderColumn == "") {
      optionsRandomOrderColumn = "NULL"
    }

    optionsColumnsNumColumn = tableMap$columns$optionsColumnsNum
    if(is.null(optionsColumnsNumColumn) || is.na(optionsColumnsNumColumn) || optionsColumnsNumColumn == "") {
      optionsColumnsNumColumn = "NULL"
    }

    extraFieldsSql = getExtraFieldsSql(table, extraFields)
    parametersSql = getIndicedColumnsSql(p1Column, paramsNum, "p")
    responseColumnsNum = getIndicedColumnsNum(table, responseValue1Column)
    responseLabelSql = getIndicedColumnsSql(responseLabel1Column, responseColumnsNum, "responseLabel")
    responseValueSql = getIndicedColumnsSql(responseValue1Column, responseColumnsNum, "responseValue")
    responseScoreSql = getIndicedColumnsSql(responseScore1Column, responseColumnsNum, "responseScore")
    responseTraitSql = getIndicedColumnsSql(responseTrait1Column, responseColumnsNum, "responseTrait")
    responseFixedIndexSql = getIndicedColumnsSql(responseFixedIndex1Column, responseColumnsNum, "responseFixedIndex")

    sql = "
SELECT 
id, 
{{questionColumn}} AS question,
{{parametersSql}},
{{traitColumn}} AS trait,
{{fixedIndexColumn}} AS fixedIndex,
{{instructionsColumn}} AS instructions,
{{skippableColumn}} AS skippable,
{{responseLabelSql}},
{{responseValueSql}},
{{responseScoreSql}},
{{responseFixedIndexSql}},
{{responseTraitSql}},
{{gracelyScaleShowColumn}} AS gracelyScaleShow,
{{painMannequinGenderColumn}} AS painMannequinGender,
{{painMannequinAreaMultiMarksColumn}} AS painMannequinAreaMultiMarks,
{{optionsRandomOrderColumn}} AS optionsRandomOrder,
{{optionsColumnsNumColumn}} AS optionsColumnsNum,
{{typeColumn}} AS type
{{extraFieldsSql}}
FROM {{table}}
"

    itemSetFilterEnabled = itemSetFilterEnabled && !is.null(itemSetColumn) && !is.na(itemSetColumn) && itemSetColumn != ""
    if(itemSetFilterEnabled) {
      sql = paste0(sql, "WHERE {{itemSetColumn}}='{{itemSet}}'")
    }

    items = leap.table.query(sql, list(
      questionColumn=questionColumn,
      parametersSql=parametersSql,
      traitColumn=traitColumn,
      fixedIndexColumn=fixedIndexColumn,
      skippableColumn=skippableColumn,
      instructionsColumn=instructionsColumn,
      extraFieldsSql=extraFieldsSql,
      responseLabelSql=responseLabelSql,
      responseValueSql=responseValueSql,
      responseScoreSql=responseScoreSql,
      responseTraitSql=responseTraitSql,
      responseFixedIndexSql=responseFixedIndexSql,
      typeColumn=typeColumn,
      gracelyScaleShowColumn=gracelyScaleShowColumn,
      painMannequinGenderColumn=painMannequinGenderColumn,
      painMannequinAreaMultiMarksColumn=painMannequinAreaMultiMarksColumn,
      optionsRandomOrderColumn=optionsRandomOrderColumn,
      optionsColumnsNumColumn=optionsColumnsNumColumn,
      itemSetColumn=itemSetColumn,
      itemSet=settings$itemSet,
      table=table
    ))
    items = convertFromFlat(items, responseColumnsNum)
  }

  if(itemBankType == "direct") {
    itemBankItems = fromJSON(itemBankItems)
    if(length(itemBankItems) > 0) {
      for(i in 1:length(itemBankItems)) {
        itemBankItems[[i]]$responseOptions = as.character(toJSON(itemBankItems[[i]]$responseOptions)) #response options don't fit into flat table, so turn them back to JSON.
        itemBankItems[[i]][sapply(itemBankItems[[i]], is.null)] <- NA
        items = rbind(items, data.frame(itemBankItems[[i]], stringsAsFactors=F))
      }
      if(itemSetFilterEnabled) {
        items = items[!is.na(items$itemSet) & items$itemSet == settings$itemSet,]
      }
    }
  }

  if(!is.na(settings$itemBankFilterModule) && settings$itemBankFilterModule != "") {
    items = leap.test.run(settings$itemBankFilterModule, params=list(
      settings = settings,
      session=session,
      items=items
    ))$items
  }

  if(dim(items)[1] == 0) { stop("Item bank must not be empty!") }

  if(settings$order == "random") {
    items = items[sample(1:dim(items)[1]),]
  }
  
  #fixed index sort
  items = items[order(items$fixedIndex),]

  if("skippable" %in% colnames(items)) {
    items[is.null(items$skippable) | is.na(items$skippable), "skippable"] = settings$canSkipItems
  } else {
    items$skippable = settings$canSkipItems
  }
  if("instructions" %in% colnames(items)) {
    items[is.null(items$instructions) | is.na(items$instructions) | items$instructions == "", "instructions"] = settings$instructions
  } else {
    items$instructions = settings$instructions
  }

  return(items)
}

getParamsNum = function(itemBankType, itemBankTable, itemBankFlatTable) {
  paramsNum = 9

  if(itemBankType == "table") {
    tableMap = fromJSON(itemBankTable)
    paramsNum = getIndicedColumnsNum(tableMap$table, tableMap$columns$p1)
  }

  if(itemBankType == "flatTable") {
    tableMap = fromJSON(itemBankFlatTable)
    paramsNum = getIndicedColumnsNum(tableMap$table, tableMap$columns$p1)
  }

  return(paramsNum)
}

theta = as.numeric(settings$startingTheta)
itemsAdministered = NULL
testTimeStarted = as.numeric(Sys.time())
totalTimeTaken = 0
resumedItemsIds = NULL
direction = 1
page = 0
scores = NULL
responses = NULL

paramsNum = getParamsNum(settings$itemBankType, settings$itemBankTable, settings$itemBankFlatTable)
items = getItems(settings$itemBankType, settings$itemBankItems, settings$itemBankTable, settings$itemBankFlatTable, settings$itemBankTableExtraFields, paramsNum)
itemsNum = dim(items)[1]

state = list(
  testTimeStarted = testTimeStarted,
  nextItemsIds = NULL,
  itemsIds = items[,"id"],
  page = 0
)

if(settings$sessionResuming == 1) {
  #get response data
  sessionTable = fromJSON(settings$sessionTable)
  resumedState = session[[sessionTable$columns$state]]
  if(!is.na(resumedState)) {
    state = fromJSON(resumedState)
    resumedItemsIds = state$nextItemsIds

    if(length(resumedItemsIds) > 0) {
      direction = 0
      page = state$page

      if(!is.null(state$testTimeStarted)) {
        sessionTestTimeStarted = as.numeric(state$testTimeStarted)
        if(sessionTestTimeStarted != 0) {
          testTimeStarted = sessionTestTimeStarted
        }
      }

      responseTable = fromJSON(settings$responseBank)
      responsesRecords = leap.table.query("
SELECT id, 
{{scoreCol}} AS score, 
{{timeTakenCol}} AS timeTaken,
{{itemIdCol}} AS item_id,
{{responseCol}} AS response,
{{skippedCol}} AS skipped
FROM {{table}} 
WHERE {{sessionIdCol}}={{sessionId}}", params=list(
  scoreCol = responseTable$columns$score,
  timeTakenCol = responseTable$columns$timeTaken,
  itemIdCol = responseTable$columns$item_id,
  responseCol = responseTable$columns$response,
  skippedCol = responseTable$columns$skipped,
  table = responseTable$table,
  sessionIdCol = responseTable$columns$session_id,
  sessionId = session$id
))

      restoredItemBank = NULL
      for(id in state$itemsIds) {
        item = items[items$id == id,]
        if(nrow(item) > 0) {
          restoredItemBank = rbind(restoredItemBank, item)
        }
      }
      items = restoredItemBank

      totalTimeTaken = sum(responsesRecords[,"timeTaken"])
      itemsAdministered = which(items[,"id"] %in% responsesRecords[,"item_id"])
      if(length(itemsAdministered) == 0) {
        itemsAdministered = NULL
      }
      scores = responsesRecords[,"score"]
      responses = responsesRecords[,"response"]
    }
  }
}

paramBank = items[, paste0("p", 1:paramsNum), drop=F]
paramBank = apply(paramBank, 2, as.numeric)
if(is.vector(paramBank)) { 
  paramBank = rbind(paramBank)
}