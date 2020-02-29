let legend;
let infoBox;
let numberScale = [];
let grades = [];

module.exports = function (data) {

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
    window.zoneLayer = L.geoJson(new_data, {
        style: style,
        onEachFeature: onEachFeature,
    });

    /** Додаємо групу до карти    */
    zonyLayersGroup.addTo(mymap);

    $('#marker-zony').html('<i class="fas fa-check text-success"></i>');
    $('#zony').prop('disabled', false);

    function setNumberScale(data) {
        grades = [];
        numberScale = [];

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

        let lengthArray = (zonyArray.length < 5) ? zonyArray.length : 5;
        let numbShift = (zonyArray[zonyArray.length - 1] - zonyArray[0]) / lengthArray;

        numberScale[0] = +parseFloat(zonyArray[0] + numbShift).toFixed(2);
        let num;
        let countElement = (lengthArray - 2 < 1) ? 0 : lengthArray - 2;

        for (let i = 0; i < countElement; i++) {
            num = parseFloat(numberScale[i] + numbShift).toFixed(2);
            numberScale.push(+num);
        }
        grades = numberScale.slice();
        grades.unshift(0);
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
            click: highlightFeature
        });
    }

    function highlightFeature(e) {
        let layer = e.target;
        zoneLayer.resetStyle();
        layer.setStyle(selectZoneStyle);

        if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
            layer.bringToFront();
        }
        infoBox.update(layer.feature.properties);
        parcelFromBaseLayer.bringToFront();
    }


    /**
     * Створюємо легенду "Значення коєфіцієнтів Км2"
     */
    if (infoBox instanceof L.Control) {
        mymap.removeControl(infoBox);
    }
    infoBox = L.control();

    infoBox.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info panel'); // create a div with a class "info"
        this.update();
        return this._div;
    };

    // method that we will use to update the control based on feature properties passed
    infoBox.update = function (props) {
        this._div.innerHTML = '<h6>Коефіцієнт км2</h6>' + (props ?
            'Зона - <b>' + props.name + '</b><br />' + 'Км2 - ' + props.km2
            : 'Натисніть для отримання інформації');
    };


    /**
     * Створюємо легенду "Умовні позначення"
     */
    if (legend instanceof L.Control) {
        mymap.removeControl(legend);
    }
    legend = L.control({position: 'bottomright'});

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