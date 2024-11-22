/**
 * Checkbox
 *
 * @param $scope
 * @constructor
 */
function WizardParamDefiner4Controller($scope, AdministrationSettingsService) {
  $scope.administrationSettingsService = AdministrationSettingsService;
};

leapPanel.controller('WizardParamDefiner4Controller', ["$scope", "AdministrationSettingsService", WizardParamDefiner4Controller]);