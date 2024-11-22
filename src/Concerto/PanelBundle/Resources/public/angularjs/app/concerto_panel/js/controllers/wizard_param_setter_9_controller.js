/**
 * Group
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter9Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;
}

leapPanel.controller('WizardParamSetter9Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter9Controller]);