$(document).ready(function () {

    /**
     * Створюємо кнопку Full extent
     */
    let fullzoomButton = L.Control.extend({
        options: {
            position: 'topleft',
        },
        onAdd: function (map) {
            let container = L.DomUtil.create('a', 'btn btn-default');
            container.innerHTML += '<i class="fas fa-crosshairs"></i>';
            container.type = "button";
            container.title = "Загальний вигляд";
            container.setAttribute("data-toggle", "tooltip");
            container.onclick = function () {
                mymap.eachLayer(function (layer) {
                    if (layer.nameLayer && layer.nameLayer === "mejaGeoJSON") {
                        mymap.fitBounds(layer.getBounds());
                    }
                });
            };
            return container;
        }
    });

    /**
     * Створюємо кнопку Clear
     */
    let clearButton = L.Control.extend({
        options: {
            position: 'topleft',
        },
        onAdd: function (map) {
            let container = L.DomUtil.create('a', 'btn btn-default');
            container.innerHTML += '<i class="fas fa-eraser"></i>';
            container.type = "button";
            container.setAttribute("data-toggle", "tooltip");
            container.title = "Очистити виділене";
            container.onclick = function () {
                clearSelected();
            };
            return container;
        }
    });

    mymap.addControl(new fullzoomButton());
    mymap.addControl(new clearButton());

    /**
     * Перемикає групу шарів, використовуючи checkbox в таблиці
     */

    $(".check-map").change(function () {
        let layerClicked = $(this).attr("id");
        switch (layerClicked) {
            case "zony":
                toggleLegend();
                toggleLayer(zonyLayersGroup);
                break;
            case "local":
                toggleLayer(localLayersGroup);
                break;
            case "lands":
                toggleLayer(landsLayersGroup);
                clearSelected();
                break;
            case "regions":
                toggleLayer(regionsLayersGroup);
                break;
        }
    });

    /**
     *  Знімає виділення із шарів з ім'я "Selected"
     */
    function clearSelected() {
        mymap.eachLayer(function (layer) {
            if (typeof layer.nameLayer !== "undefined" && layer.nameLayer === "Selected") {
                mymap.removeLayer(layer);
            }
        });
    }

    /**
     * Перемикає групу шарів, використовуючи checkbox в таблиці
     *
     * @param layersGroupName
     */
    function toggleLayer(layersGroupName) {
        if (mymap.hasLayer(layersGroupName)) {
            mymap.removeLayer(layersGroupName);
        } else {
            mymap.addLayer(layersGroupName);
        }
    }

    /**
     * Вимикає легенду для шару "Економіко-планувальні зони"
     */
    function toggleLegend() {
        $('.info').toggleClass('d-none');
    }

    /**
     * Зумує на імпортовану ділянку
     */

    $('#zoom-to-parcel').on('click', function (e) {
        e.preventDefault();
        let boundsStr = $('#geom-from-json').attr("data-bounds");
        if (boundsStr.trim() !== '') {
            let bounds = JSON.parse(boundsStr);
            let arrayBounds = [];
            arrayBounds.push([bounds._southWest.lat, bounds._southWest.lng], [bounds._northEast.lat, bounds._northEast.lng]);

            mymap.fitBounds(arrayBounds);
            parcelGroup.eachLayer(function (layer) {
                layer.bringToFront();
            });
        }
    });

    /**
     * Підсвічує локальні фактори на карті при наведенні в таблиці
     */

    $('body').on('mouseover', '#calculate table tr', function (e) {
        setStyleIn($(this).attr("data-id"));
    });

    $('body').on('mouseout', '#calculate table tr', function (e) {
        setStyleOut($(this).attr("data-id"));
    });

    function setStyleIn(id) {
        intersectLocalLayersGroup.eachLayer(function (layer) {
            if (Number(layer.feature.properties.id) === Number(id)) {
                layer.setStyle(intersectLocalsSelectedStyle);
                layer.bringToFront();
            }
        });
    }

    function setStyleOut(id) {
        intersectLocalLayersGroup.eachLayer(function (layer) {
            if (Number(layer.feature.properties.id) === Number(id)) {
                layer.setStyle(intersectLocalsStyle);
            }
        });
    }

    $('[data-toggle="tooltip"]').tooltip({
        placement: 'bottom',
        trigger: 'hover',
    });

    let lat, lng;

    /**
     * Створюємо паньль виведення координат на карті
     */
    let coordinates = L.Control.extend({
        options: {
            position: 'bottomleft',
        },
        onAdd: function (map) {
            let container = L.DomUtil.create('div', 'pr-1 pl-1');
            container.setAttribute("id", "coordinates-map");

            return container;
        }
    });

    mymap.addControl(new coordinates());

    mymap.addEventListener('mousemove', function(ev) {
        lat = ((Math.round(ev.latlng.lat *1000000))/1000000).toFixed(6);
        lng = ((Math.round(ev.latlng.lng *1000000))/1000000).toFixed(6);
        $('#coordinates-map').html('<i class="fas fa-location-arrow text-gray"></i>' + ' ' + lat + ' ' + lng);
    });

    mymap.addEventListener('mouseout', function(ev) {
        $('#coordinates-map').html('');
    });
});