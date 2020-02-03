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
                "km2": item.km2,
            },
            "geometry": {
                "type": "Polygon",
                "coordinates": coord.coordinates,
            },
        };

        return item_new;
    });

    setNumberScale(data);

    clearLayersZony();

    geojson = L.geoJson(new_data, {
        style: style,
        onEachFeature: onEachFeature,
    });

    /** Додаємо групу до карти    */
    zonyLayersGroup.addTo(mymap);

    /** Додаємо групу до панелі управління    */
    layersControl.addOverlay(zonyLayersGroup, 'Економіко-пл. зони');

    $('#marker-zony').html('<i class="fas fa-check text-success"></i>');
    $('#zony').prop('disabled', false);

    function setNumberScale(data) {

        /**  Створюємо масив з значеннями Км2    */

        let zonyArray = data.map(function (item) {
            return +item.km2;
        });

        function compareNumeric(a, b) {
            if (a > b) return 1;
            if (a == b) return 0;
            if (a < b) return -1;
        }

        /**  Сортуємо масив    */
        zonyArray.sort(compareNumeric);
        let numbShift = (zonyArray[zonyArray.length - 1] - zonyArray[0]) / 5;

        numberScale[0] = +parseFloat(zonyArray[0] + numbShift).toFixed(2);
        let num;
        for (let i = 0; i < 3; i++) {
            num = parseFloat(numberScale[i] + numbShift).toFixed(2);
            numberScale.push(+num);
        }
        grades = numberScale.slice();
        grades.unshift(0);
    }

    /**
     * Remove zonyLayers from map
     */
    function clearLayersZony() {
        mymap.eachLayer(function (layer) {
            if (layer.nameLayer && layer.nameLayer === "zonyGeoJSON") {
                mymap.removeLayer(layer)
                layersControl.removeLayer(geojson)
            }
        });
    }

    /**
     * Налаштування кольорів, для відображення зон на карті
     * @param value
     * @returns {string}
     */

    function getColor(value) {

        return value > numberScale[3] ? '#a63603' :
            value > numberScale[2] ? '#e6550d' :
                value > numberScale[1] ? '#fd8d3c' :
                    value > numberScale[0] ? '#fdbe85' :
                        '#feedde';

    }

    /**
     * Повертає об'єкт Style з кольором в залежності від значення Км2
     * @param feature
     * @returns {{fillColor: *, color: string, fillOpacity: number, weight: number, opacity: number, dashArray: string}}
     */

    function style(feature) {
        return {
            fillColor: getColor(+feature.properties.km2),
            weight: 2,
            opacity: 1,
            color: 'white',
            dashArray: '3',
            fillOpacity: 0.6
        };
    }

    function onEachFeature(feature, layer) {
        zonyLayersGroup.addLayer(layer);
        layer.nameLayer = "zonyGeoJSON";
        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight,
            click: zoomToFeature
        });
    }


    function highlightFeature(e) {
        let layer = e.target;

        layer.setStyle({
            weight: 3,
            color: '#666',
            dashArray: '',
            fillOpacity: 0.7
        });

        if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
            layer.bringToFront();
        }
        infoBox.update(layer.feature.properties);
    }

    function resetHighlight(e) {
        geojson.resetStyle(e.target);
        infoBox.update();
    }

    /**
     * Зумує на екстент межі населеного пункту
     * @param e
     */

    function zoomToFeature(e) {
        mymap.fitBounds(e.target.getBounds());
    }

    /**
     * Створюємо легенду "Значення коєфіцієнтів Км2"
     */
    let infoBox = L.control();

    infoBox.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
        this.update();
        return this._div;
    };

    // method that we will use to update the control based on feature properties passed
    infoBox.update = function (props) {
        this._div.innerHTML = '<h6>Коефіцієнт км2</h6>' + (props ?
            'Зона - <b>' + props.name + '</b><br />' + 'Км2 - ' + props.km2
            : 'Наведіть для отримання інформації');
    };


    /**
     * Створюємо легенду "Умовні позначення"
     */
    let legend = L.control({position: 'bottomright'});

    legend.onAdd = function (map) {
        let div = L.DomUtil.create('div', 'info legend list-unstyled'),
            labels = [];

        div.innerHTML += '<p class="text-uppercase">Умовні позначення</p>';
        for (let i = 0; i < grades.length; i++) {
            div.innerHTML +=
                '<div><i style="background:' + getColor(grades[i] + 0.1) + '"></i> <p>' +
                grades[i] + ' ' + (grades[i + 1] ? '&ndash;' + '&nbsp;' + grades[i + 1] : '+') + '</p></div>';
        }

        return div;
    };

    infoBox.onAdd();
    infoBox.addTo(mymap);
    legend.onAdd(mymap);
    legend.addTo(mymap);

    return true;
};