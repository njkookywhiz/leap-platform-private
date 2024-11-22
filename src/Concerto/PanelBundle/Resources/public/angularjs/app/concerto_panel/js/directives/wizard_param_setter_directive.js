angular.module('leapPanel').directive('wizardParamSetter', ["$compile", "$templateCache", "$uibModal", "TestWizardParam", function ($compile, $templateCache, $uibModal, TestWizardParam) {
    return {
        restrict: 'E',
        scope: {
            param: "=",
            output: "=",
            values: "=",
            parent: "=",
            grandParent: "=",
            wizardObject: "=",
            underList: "=",
            editable: "="
        },
        link: function (scope, element, attrs, controllers) {
            scope.testWizardParamService = TestWizardParam;
            scope.mode = "dialog";
            scope.wizardMode = "prod";
            scope.complexSetters = [1, 2, 7, 9, 10, 11, 12, 13];
            scope.isSetterComplex = false;
            scope.title = "";
            scope.summary = "";
            if ("mode" in attrs) {
                scope.mode = attrs.mode;
            }
            if ("wizardMode" in attrs) {
                scope.wizardMode = attrs.wizardMode;
            }

            scope.onPrimitiveValueChange = function (value) {
                scope.output = value;
                if (scope.wizardMode == "dev" && value != null && !scope.underList && scope.param.definition != undefined) {
                    scope.param.definition.defvalue = value;
                }
                if (scope.parent === null)
                    scope.values[scope.param.name] = value;
            };

            scope.updateSeterComplexity = function () {
                scope.isSetterComplex = scope.complexSetters.indexOf(parseInt(scope.param.type)) !== -1;
            };
            scope.updateTitle = function () {
                scope.title = scope.testWizardParamService.getSetterTitle(scope.param);
            };
            scope.updateSummary = function () {
                scope.summary = scope.testWizardParamService.getSetterSummary(scope.param, scope.output);
            };

            scope.getParamSetterCellTemplate = function (param, parent, grandParent, output) {
                let cell = '<wizard-param-setter param="' + param + '" parent="' + parent + '" grand-parent="'+ grandParent +'" output="' + output + '" mode="grid" wizard-mode="' + scope.wizardMode + '" under-list="true" values="grid.appScope.values" wizard-object="grid.appScope.wizardObject" editable="grid.appScope.editable"></wizard-param-setter>';
                return cell;
            };

            scope.launchSetterDialog = function () {
                let modalInstance = $uibModal.open({
                    templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "param_setter_dialog.html",
                    scope: scope,
                    controller: TestWizardParamSetterController,
                    size: "prc-lg"
                });
                modalInstance.result.then(function (result) {
                    scope.output = result;
                }, function () {
                });
            };

            scope.$watch('param.type', function (newValue) {
                if (!scope.param)
                    return;
                if (newValue === null || newValue === undefined)
                    return;

                scope.updateSeterComplexity();
                scope.updateTitle();
                scope.updateSummary();
                element.html($templateCache.get("type_" + newValue + "_setter.html"));
                $compile(element.contents())(scope);
            });

            scope.$watch('output', function () {
                scope.updateSummary();
            }, true);
            scope.$watch("param.definition.defvalue", function (newValue) {
                if (scope.output === null || (scope.wizardMode == "dev" && newValue != null && newValue != undefined && !scope.underList)) {
                    scope.output = newValue;
                }
            });
        }
    };
}]);