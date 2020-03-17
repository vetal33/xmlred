/**
 * Додаємо імпортовану ділянку до карти
 *
 * @param data
 * @returns {string}
 */
module.exports = function (data) {

    let objData = JSON.parse(data.json);
    let cudNum = (typeof data.pub !== 'undefined') ? data.pub[0].cadnum : '';
    let purpose = (typeof data.pub !== 'undefined') ? data.pub[0].purpose : '';
    let area = (Math.round(data.area) / 10000).toFixed(4);
    let areaStr = area + ' га';

    let feature = [{
        "type": "Feature",
        "properties": {
            "name": "Parcel",
            "cadNum": cudNum,
            "area": areaStr,
            "wkt": data.wkt,
            "newFileName": data.newFileName,
            "purpose": purpose,
        },
        "geometry": {
            "type": objData.type,
            "coordinates": objData.coordinates,
        }
    }];

    let polygon = parcelLayer.addData(feature);
    polygon.setStyle(addFeatureFromJsonStyle);

    parcelGroup.addLayer(polygon);

    let parcel = L.geoJSON(feature);
    mymap.fitBounds(parcel.getBounds());

    /** Додаємо групу до карти    */
    parcelGroup.addTo(mymap);

    return JSON.stringify(parcel.getBounds());
};