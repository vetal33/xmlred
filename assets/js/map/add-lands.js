module.exports = function (data) {

    let geojson;

    let new_data = data.map(function (item) {
        let coord = JSON.parse(item.coordinates);
        let item_new = {
            "type": "Feature",
            "properties": {
                "code": item.code,
                "popupContent": item.code,
            },
            "geometry": {
                "type": "Polygon",
                "coordinates": coord.coordinates,
            },
        };

        return item_new;
    });

    geojson = L.geoJson(new_data, {
        style: style,
        onEachFeature: onEachFeature,
    });

    /** Додаємо групу до карти    */
    landsLayersGroup.addTo(mymap);

    /** Додаємо написи шарів групи    */
    landsLayersGroup.eachLayer(function (layer) {
        layer.bindPopup("шифр - " + layer.feature.properties.popupContent);
    });

    $('#marker-lands').html('<i class="fas fa-check text-success"></i>');
    $('#lands').prop('disabled', false);

    /**
     * Налаштування кольорів, для відображення зон на карті
     * @param value
     * @returns {string}
     */

    function getColor(value) {

        return (value === '8в' || value === '178в') ? '#a63603' :
            (value === '6в' || value === '55д') ? '#e6550d' :
                (value === '5б' || value === '121е') ? '#fd8d3c' :
                    (value === '103д' || value === '29г') ? '#fdbe85' :
                        generateColor();

    }

    function generateColor() {
        let r = 255;
        let g = Math.floor(Math.random() * (256));
        let b = Math.floor(Math.random() * (256));

        return '#' + r.toString(16) + g.toString(16) + b.toString(16);
    }

    /**
     * Повертає об'єкт Style з кольором в залежності від угіддя
     * @param feature
     * @returns {{fillColor: *, color: string, fillOpacity: number, weight: number, opacity: number, dashArray: string}}
     */

    function style(feature) {

        return {
            fillColor: getColor(feature.properties.code),
            weight: 0,
            opacity: 1,
            color: 'white',
            dashArray: '1',
            fillOpacity: 0.6
        };
    }

    function onEachFeature(feature, layer) {
        layer.nameLayer = "landsGeoJSON";
        landsLayersGroup.addLayer(layer);
        layer.on({
            click: selectFeature
        });
    }

    function selectFeature(e) {
        let layer = e.target;
        resetHighlight();

        let coords = layer.feature.geometry.coordinates;
        let selected = getCoords(coords);

        let selectedLayer = L.geoJSON(selected).addTo(mymap);
        selectedLayer.nameLayer = "Selected";
        selectedLayer.setStyle(selectlandsStyle);

        if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
            selectedLayer.bringToFront();
        }
    }

    function getCoords(coords) {
        return coords.map(function (item) {
            return {
                "type": "Feature",
                "nameLayer": "Selected",
                "geometry": {
                    "type": "LineString",
                    "coordinates": item,
                }
            }
        });
    }

    function resetHighlight() {
        mymap.eachLayer(function (layer) {
            if (typeof layer.nameLayer !== "undefined" && layer.nameLayer === "Selected") {
                mymap.removeLayer(layer);
            }
        });
    }

    return true;
};