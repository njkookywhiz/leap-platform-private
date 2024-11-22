/**
 * HTML
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner2Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
  $scope.htmlEditorOptions = Defaults.ckeditorPanelContentOptions;
};

leapPanel.controller('WizardParamDefiner2Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner2Controller]);