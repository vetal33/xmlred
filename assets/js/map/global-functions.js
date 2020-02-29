$(document).ready(function () {
    window.removeLayersGlob = function (name) {
        mymap.eachLayer(function (layer) {
            if (typeof layer.nameLayer !== "undefined" && layer.nameLayer === name) {
                mymap.removeLayer(layer);
            }
        });
    }
});