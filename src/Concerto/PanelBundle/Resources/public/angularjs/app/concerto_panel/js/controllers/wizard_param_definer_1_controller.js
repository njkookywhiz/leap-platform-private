/**
 * Multi line
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner1Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
};

leapPanel.controller('WizardParamDefiner1Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner1Controller]);