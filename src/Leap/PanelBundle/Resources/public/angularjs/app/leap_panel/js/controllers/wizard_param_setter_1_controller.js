/**
 * Multi Line
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter1Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;

    $scope.onPrimitiveValueChange($scope.output);
}

leapPanel.controller('WizardParamSetter1Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter1Controller]);