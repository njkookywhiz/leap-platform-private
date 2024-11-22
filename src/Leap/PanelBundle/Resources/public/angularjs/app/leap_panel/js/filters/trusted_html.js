angular.module('leapPanel').filter('trustedHtml', ['$sce',
    function ($sce) {
        return function (text) {
            return $sce.trustAsHtml(text);
        };
    }
]);