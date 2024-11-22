/**
 * HTML
 *
 * @param $scope
 * @constructor
 */
function WizardParamSetter2Controller($scope, AdministrationSettingsService) {
    $scope.administrationSettingsService = AdministrationSettingsService;
    $scope.htmlEditorOptions = Defaults.ckeditorTestContentOptions;

    $scope.onPrimitiveValueChange($scope.output);
}

leapPanel.controller('WizardParamSetter2Controller', ["$scope", "AdministrationSettingsService", WizardParamSetter2Controller]);