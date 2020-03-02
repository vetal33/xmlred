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
                removeLayersGlob('Selected');
                if (typeof zoneLayer !== 'undefined') {
                    zoneLayer.resetStyle();
                    $('.panel').html('<h6>Коефіцієнт км2</h6> Натисніть для отримання інформації');
                }
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
                zoneLayer.resetStyle();
                toggleLegend('zony');
                toggleLayer(zonyLayersGroup);
                parcelFromBaseLayer.bringToFront();
                break;
            case "local":
                toggleLegend('local');
                toggleLayer(localLayersGroup);
                parcelFromBaseLayer.bringToFront();
                break;
            case "lands":
                toggleLayer(landsLayersGroup);
                removeLayersGlob('Selected');
                parcelFromBaseLayer.bringToFront();
                break;
            case "regions":
                toggleLayer(regionsLayersGroup);
                parcelFromBaseLayer.bringToFront();
                break;
            case "parcels":
                toggleLayer(parcelFromBaseGroup);
                parcelFromBaseLayer.bringToFront();
                break;
        }
    });

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
    function toggleLegend(key) {
        if (key === 'zony') {
            $('.panel').toggleClass('d-none');
            $('.legend').toggleClass('d-none');
            $('.panel').html('<h6>Коефіцієнт км2</h6> Натисніть для отримання інформації');

            if(!($('#zony').prop('checked')) && $('#local').prop('checked')){
                $('.local').removeClass('d-none');
            } else if (($('#zony').prop('checked')) && $('#local').prop('checked')){
                $('.local').addClass('d-none');
                mymap.removeLayer(markerLayer);
            }
        }
        if (key === 'local' && !($('.panel').hasClass('d-none'))) {
            $('.local').addClass('d-none');
            mymap.removeLayer(markerLayer);
        }

        if (key === 'local' && ($('.panel').hasClass('d-none'))) {
            if ($('#local').prop('checked')) {
                $('.local').removeClass('d-none');
            } else {
                $('.local').addClass('d-none');
                mymap.removeLayer(markerLayer);
                $('#map-info-local').html('');
            }
        }
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
                layer.bringToBack();
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

    mymap.addEventListener('mousemove', function (ev) {
        lat = ((Math.round(ev.latlng.lat * 1000000)) / 1000000).toFixed(6);
        lng = ((Math.round(ev.latlng.lng * 1000000)) / 1000000).toFixed(6);
        $('#coordinates-map').html('<i class="fas fa-location-arrow text-gray"></i>' + ' ' + lat + '  ' + lng);
    });

    mymap.addEventListener('mouseout', function (ev) {
        $('#coordinates-map').html('');
    });

    parcelLayer.on('click', function (e) {
        parcelLayer.setStyle(addFeatureFromJsonStyle);
        e.layer.setStyle(addFeatureFromJsonSelectedStyle);
        $('#feature-card-area').html(e.layer.feature.properties.area);
        $('#feature-card-cud-num').html(e.layer.feature.properties.cadNum);
        $('#feature-purpose').html(e.layer.feature.properties.purpose);

        let bounds = JSON.stringify(e.layer.getBounds());
        $('#geom-from-json').attr("data-bounds", bounds);
        $('#geom-from-json').val(e.layer.feature.properties.wkt);
        $('#save-parcel').removeClass('disabled');
        $('#calculate').remove();
    });

    let currentLayerId = 0;

    parcelFromBaseLayer.on('click', function (e) {
        parcelFromBaseLayer.setStyle(parcelFromBaseStyle);
        e.layer.setStyle(addFeatureFromJsonSelectedStyle);
        $('#feature-card').removeClass('d-none');
        $('#feature-card-area').html(e.layer.feature.properties.area);
        $('#feature-card-cud-num').html(e.layer.feature.properties.cadnum);
        $('#feature-purpose').html(e.layer.feature.properties.purpose);
        $('#save-parcel').addClass('disabled');
        $('#calculate-parcel').removeClass('disabled');
        $('#geom-from-json').val('');

        let bounds = JSON.stringify(e.layer.getBounds());
        $('#geom-from-json').attr("data-bounds", bounds);

        if (currentLayerId !== e.layer._leaflet_id) {
            currentLayerId = e.layer._leaflet_id;
            $('#calculate').remove();
            removeLayersGlob('IntersectGeoJSON');
            intersectLocalLayersGroup.clearLayers();
        }
    });

    mymap.on('click', clickHandlerParcel);

    /**
     * Вираховує перетин з шаром ділянок і знімає виділення якщо "клік" відбувся поза межами ділянки
     * @param e
     */
    function clickHandlerParcel(e) {
        let clickBounds = L.latLngBounds(e.latlng, e.latlng);

        function intersectGroup(groupName) {
            let intersectingFeatures = [];
            if (mymap.hasLayer(groupName)) {
                groupName.eachLayer(function (layer) {
                    for (let l in layer._layers) {
                        let overlay = mymap._layers[l];
                        if (clickBounds.intersects(overlay.getBounds())) {
                            intersectingFeatures.push(overlay);
                        }
                    }
                });
            }
            return intersectingFeatures;
        }

        let parcelsBase = intersectGroup(parcelFromBaseGroup);
        if (!parcelsBase.length) {
            parcelFromBaseLayer.setStyle(parcelFromBaseStyle);
        }

        let parcels = intersectGroup(parcelGroup);
        if (!parcels.length) {
            parcelLayer.setStyle(addFeatureFromJsonStyle);
        }

        let lands = intersectGroup(landsLayersGroup);
        if (!lands.length) {
            removeLayersGlob('Selected');
        }

        if (typeof zoneLayer !== 'undefined') {
            if (!clickBounds.intersects(zoneLayer.getBounds())) {
                zoneLayer.resetStyle();
                $('.panel').html('<h6>Коефіцієнт км2</h6> Натисніть для отримання інформації');
            }
        }
    }

    function createPoints(data) {
        if (data.length > 0) {
            let geojson;

            let new_data = data[0].map(function (item) {
                let item_new = {
                    "type": "Feature",
                    "geometry": {
                        "type": "Point",
                        "coordinates": item,
                    },
                };

                return item_new;
            });

            geojson = L.geoJson(new_data, {
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, pointsSelectedStyle);
                },
                onEachFeature: onEachFeature,
            });

            /** Додаємо групу до карти    */
            geojson.addTo(mymap);

            function onEachFeature(feature, layer) {
                layer.nameLayer = "pointsGeoJSON";
                landsLayersGroup.addLayer(layer);
            }
        }
    }
});