module.exports = function (data) {

    let geojson;
    let numberScale = [];
    let grades = [];

    let new_data = data.map(function (item) {
        let coord = JSON.parse(item.coordinates);
        let item_new = {
            "type": "Feature",
            "properties": {
                "name": item.name,
                "ki": item.ki,
            },
            "geometry": {
                "type": "Polygon",
                "coordinates": coord.coordinates,
            },
        };

        return item_new;
    });

    setNumberScale(data);

    geojson = L.geoJson(new_data, {
        style: style,
        onEachFeature: onEachFeature,
    });

    /** Додаємо групу до карти    */
    regionsLayersGroup.addTo(mymap);

    /** Додаємо написи шарів групи    */
    regionsLayersGroup.eachLayer(function (layer) {
        layer.bindPopup("район - " + layer.feature.properties.name + "<br /> Iі - " + layer.feature.properties.ki);
    });

    $('#marker-regions').html('<i class="fas fa-check text-success"></i>');
    $('#regions').prop('disabled', false);

    function setNumberScale(data) {

        /**  Створюємо масив з значеннями Кi    */
        let regionsArray = data.map(function (item) {
            return +item.ki;
        });

        function compareNumeric(a, b) {
            if (a > b) return 1;
            if (a == b) return 0;
            if (a < b) return -1;
        }

        /**  Сортуємо масив    */
        regionsArray.sort(compareNumeric);
        let numbShift = (regionsArray[regionsArray.length - 1] - regionsArray[0]) / 5;

        numberScale[0] = +parseFloat(regionsArray[0] + numbShift).toFixed(2);
        let num;
        for (let i = 0; i < 3; i++) {
            num = parseFloat(numberScale[i] + numbShift).toFixed(2);
            numberScale.push(+num);
        }
        grades = numberScale.slice();
        grades.unshift(0);
    }

    /**
     * Налаштування кольорів, для відображення районів на карті
     * @param value
     * @returns {string}
     */

    function getColor(value) {

        return value > numberScale[3] ? '#045a8d' :
            value > numberScale[2] ? '#2b8cbe' :
                value > numberScale[1] ? '#74a9cf' :
                    value > numberScale[0] ? '#bdc9e1' :
                        '#f1eef6';

    }

    /**
     * Повертає об'єкт Style з кольором в залежності від значення Кi
     * @param feature
     * @returns {{fillColor: *, color: string, fillOpacity: number, weight: number, opacity: number, dashArray: string}}
     */

    function style(feature) {
        return {
            fillColor: getColor(+feature.properties.ki),
            weight: 1,
            opacity: 1,
            color: 'white',
            dashArray: '2',
            fillOpacity: 0.6
        };
    }

    function onEachFeature(feature, layer) {
        regionsLayersGroup.addLayer(layer);
        layer.nameLayer = "regionsGeoJSON";
    }

    return true;
};