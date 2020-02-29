module.exports = function (data) {
    parcelFromBaseLayer.clearLayers();

    let feature = data.map(function (item) {
        let coord = JSON.parse(item.json);
        let item_new = {
            "type": "Feature",
            "properties": {
                "area": item.area,
                "cadnum": item.cadNum,
                "name": "parcelFromBase",
                "fromBase": true,
                "purpose": item.purpose,
            },
            "geometry": {
                "type": coord.type,
                "coordinates": coord.coordinates,
            },
        };

        return item_new;
    });

    parcelFromBaseLayer.addData(feature);
    parcelFromBaseLayer.setStyle(parcelFromBaseStyle);
    parcelFromBaseLayer.addTo(parcelFromBaseGroup);

    /** Додаємо групу до карти    */
    parcelFromBaseGroup.addTo(mymap);

    $('#marker-parcels').html('<i class="fas fa-check text-success"></i>');
    $('#parcels').prop('disabled', false);

    mymap.fitBounds(parcelFromBaseLayer.getBounds());

    return true;
};