module.exports = function (data) {

    let geojson;

    let new_data = data.map(function (item) {
        let coord = JSON.parse(item.coordinates);
        let item_new = {
            "type": "Feature",
            "properties": {
                "name": item.name,
                "code": item.code,
            },
            "geometry": {
                "type": "Polygon",
                "coordinates": coord.coordinates,
            },
        };

        return item_new;
    });

    //clearLayersLocal();

    geojson = L.geoJson(new_data, {
        style: style,
        onEachFeature: onEachFeature,
    });

    /** Додаємо групу до карти    */
    localLayersGroup.addTo(mymap);

    /** Додаємо написи шарів групи    */
    localLayersGroup.eachLayer(function (layer) {
        layer.bindPopup("шифр - " + layer.feature.properties.name);
    });

    /** Додаємо групу до панелі управління    */
/*    layersControl.addOverlay(localLayersGroup, 'Локальні фактори');*/

    $('#marker-local').html('<i class="fas fa-check text-success"></i>');
    $('#local').prop('disabled', false);

    /**
     * Remove localLayers from map
     */
    function clearLayersLocal() {
        mymap.eachLayer(function (layer) {
            if (layer.nameLayer && layer.nameLayer === "localGeoJSON") {
                mymap.removeLayer(layer)
                layersControl.removeLayer(geojson)
            }
        });
    }

    /**
     * Повертає об'єкт Style з кольором в залежності від значення локального фактору
     * @param feature
     * @returns {{fillColor: *, color: string, fillOpacity: number, weight: number, opacity: number, dashArray: string}}
     */

    function style(feature) {
        let code = +feature.properties.code;
        switch (code) {
            case 1:
                return {
                    fillColor: '#a63603',
                    weight: 3,
                    opacity: 1,
                    color: '#a63603',
                    dashArray: '5',
                    fillOpacity: 0
                };
            case 2:
                return {
                    fillColor: '#5c3512',
                    weight: 3,
                    opacity: 1,
                    color: '#5c3512',
                    dashArray: '5',
                    fillOpacity: 0
                };
            case 3:
                return {
                    fillColor: '#0e7fa6',
                    weight: 2,
                    opacity: 1,
                    color: '#0e7fa6',
                    dashArray: '4',
                    fillOpacity: 0
                };
            case 4:
                return {
                    fillColor: '#1da60a',
                    weight: 2,
                    opacity: 1,
                    color: '#1da60a',
                    dashArray: '4',
                    fillOpacity: 0
                };
            case 5:
                return {
                    fillColor: '#09080a',
                    weight: 2,
                    opacity: 1,
                    color: '#09080a',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 7:
                return {
                    fillColor: '#250e03',
                    weight: 2,
                    opacity: 1,
                    color: '#250e03',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 9:
                return {
                    fillColor: '#384ca6',
                    weight: 2,
                    opacity: 1,
                    color: '#384ca6',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 11:
                return {
                    fillColor: '#575d46',
                    weight: 2,
                    opacity: 1,
                    color: '#575d46',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 12:
                return {
                    fillColor: '#e15e24',
                    weight: 2,
                    opacity: 1,
                    color: '#e15e24',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 15:
                return {
                    fillColor: '#e1d21f',
                    weight: 2,
                    opacity: 1,
                    color: '#e1d21f',
                    dashArray: '2',
                    fillOpacity: 0
                };
            case 18:
                return {
                    fillColor: '#b3e1df',
                    weight: 2,
                    opacity: 1,
                    color: '#b3e1df',
                    dashArray: '2',
                    fillOpacity: 0.3
                };
            case 19:
                return {
                    fillColor: '#21d0e1',
                    weight: 2,
                    opacity: 1,
                    color: '#e15da4',
                    dashArray: '2',
                    fillOpacity: 0.3
                };
            case 23:
                return {
                    fillColor: '#21d0e1',
                    weight: 3,
                    opacity: 1,
                    color: '#0d9c58',
                    dashArray: '6',
                    fillOpacity: 0
                };
            case 24:
                return {
                    fillColor: '#e1a380',
                    weight: 2,
                    opacity: 1,
                    color: '#9c1001',
                    dashArray: '1',
                    fillOpacity: 0.2
                };
            case 29:
                return {
                    fillColor: '#35e114',
                    weight: 2,
                    opacity: 1,
                    color: '#1a6808',
                    dashArray: '4',
                    fillOpacity: 0.1
                };
            case 30:
                return {
                    fillColor: '#e12787',
                    weight: 2,
                    opacity: 1,
                    color: '#a22451',
                    dashArray: '2',
                    fillOpacity: 0.1
                };
            case 31:
                return {
                    fillColor: '#4e95e1',
                    weight: 2,
                    opacity: 1,
                    color: '#151ca2',
                    dashArray: '1',
                    fillOpacity: 0.2
                };
            case 34:
                return {
                    fillColor: '#180120',
                    weight: 2,
                    opacity: 1,
                    color: '#290a30',
                    dashArray: '2',
                    fillOpacity: 0.2
                };
            default:
                return {
                    fillColor: '#59a631',
                    weight: 2,
                    opacity: 0.3,
                    color: 'white',
                    dashArray: '3',
                    fillOpacity: 0
                }
        }
    }

    function onEachFeature(feature, layer) {
        layer.nameLayer = "localGeoJSON";
        localLayersGroup.addLayer(layer);
    }

    return true;
};