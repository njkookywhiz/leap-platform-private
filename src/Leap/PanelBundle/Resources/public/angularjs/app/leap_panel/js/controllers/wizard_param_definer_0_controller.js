/**
 * Single line
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner0Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
};

leapPanel.controller('WizardParamDefiner0Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner0Controller]);