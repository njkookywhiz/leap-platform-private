if(!is.na(settings$itemsExclusionModule) && settings$itemsExclusionModule != "") {
  excludedItems = leap.test.run(settings$itemsExclusionModule, params=list(
    excludedItems=excludedItems,
    items=items,
    itemsAdministered=itemsAdministered,
    responses=responses,
    scores=scores,
    sem=sem,
    session=session,
    settings = settings,
    theta = theta,
    traitScores = traitScores,
    traitSem = traitSem,
    traitTheta = traitTheta
  ))$excludedItems
}
leap.log(excludedItems, "excluded items indices")