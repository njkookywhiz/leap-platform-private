leapPanel.factory('ApiClientsCollectionService', function ($http) {
    return {
        collectionPath: Paths.ADMINISTRATION_API_CLIENTS_COLLECTION,
        collection: [],
        fetchObjectCollection: function (callback) {
            var obj = this;
            $http({
                url: obj.collectionPath,
                method: "GET"
            }).then(function (httpResponse) {
                obj.collection = httpResponse.data;
                if (callback)
                    callback.call(this);
            });
        },
        get: function (id) {
            for (var i = 0; i < this.collection.length; i++) {
                var obj = this.collection[i];
                if (obj.id == id)
                    return obj;
            }
            return null;
        }
    }
});