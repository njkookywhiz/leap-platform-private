/**
 * Single Line
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter0Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

leapPanel.controller('WizardParamSetter0Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter0Controller]);