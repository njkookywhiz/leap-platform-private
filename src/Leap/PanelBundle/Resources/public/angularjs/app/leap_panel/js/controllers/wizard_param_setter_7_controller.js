/**
 * Data Table Column
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter7Controller($scope, DataTableCollectionService, AdministrationSettingsService) {
    $scope.dataTableCollectionService = DataTableCollectionService;
    $scope.administrationSettingsService = AdministrationSettingsService;
}

leapPanel.controller('WizardParamSetter7Controller', ["$scope", "DataTableCollectionService", "AdministrationSettingsService", WizardParamSetter7Controller]);