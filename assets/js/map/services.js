$(function () {
    const overlay = $('#shp-card .overlay');
    const textContent = $('#text-content');
    const btnDownloadShp = $('#btn-download-shp');

    let numberScale = [];

    overlay[0].hidden = true;
    $('.custom-file-input').on('change', function (event) {
        let inputFile = event.currentTarget;
        $(inputFile).parent()
            .find('.custom-file-label')
            .html(inputFile.files[0].name);
        let fileXML = $("#file_form_xmlFile")[0].files[0];

        let formData = new FormData();
        formData.append("xmlFile", fileXML);
        sendFile(formData);

    });

    let boundaryStyle = {
        "color": "#ff735b",
        "weight": 5,
        "opacity": 1,
        "fillOpacity": 0.05,
        "fillColor": "#5bff10",
    };

    function sendFile(data) {
        $.ajax({
            url: '/',
            method: 'POST',
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function () {
                overlay[0].hidden = false;
            },
            success: function (data) {
                $('#error').parent('div').remove();
                $(btnDownloadShp).removeClass('disabled');


                overlay[0].hidden = true;

                let dataJson = JSON.parse(data);

                if (dataJson.errors.length > 0) {
                    $(btnDownloadShp).addClass('disabled');
                    $('#shp-card').attr('data-name', "");
                    createBlockErrors(dataJson.errors);
                } else {
                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);
                    addMejaToMap(dataJson.boundary, boundaryStyle);
                    addZonyToMap(dataJson.zony, style);
                    visualizeXML(dataJson);
                    infoBox.onAdd();
                    infoBox.addTo(mymap);
                    legend.onAdd(mymap);
                    legend.addTo(mymap);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlay[0].hidden = true;
                console.log('fail');
            },
        })
    }

    function visualizeXML(data) {
        let wrapper = document.getElementById("wrapper");
        let tree = jsonTree.create(data.origXml, wrapper);
        $('#original_name_file').html(data.origXmlName);
        $('#shp-card').attr('data-name', data.newXmlName);

        tree.expand(function (node) {
            return node.childNodes.length < 2 || node.label === 'phoneNumbers';
        });
    }

    let normativeGroup;

    function addMejaToMap(data, style) {
        let geoJsonObj = JSON.parse(data);
        if (typeof (geoJsonObj) == "object") {
            let polygonMeja = L.geoJSON(geoJsonObj, {
                style: style,
            }).addTo(mymap);

            polygonMeja.nameLayer = "mejaGeoJSON";
            mymap.fitBounds(polygonMeja.getBounds());
            normativeGroup = L.layerGroup([polygonMeja]);
        }
    }

    let geojson;

    function addZonyToMap(data, style) {

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
        }).addTo(mymap);

        layersControl.addOverlay(geojson, 'zony');
    }


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

        numberScale[0] = zonyArray[0] + numbShift;
        for (let i = 0; i < 3; i++) {
            numberScale.push(numberScale[i] + numbShift);
        }
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

    function getColor(value, numberScale) {
        return value > numberScale[3] ? '#a63603' :
            value > numberScale[2] ? '#e6550d' :
                value > numberScale[1] ? '#fd8d3c' :
                    value > numberScale[0] ? '#fdbe85' :
                        '#feedde';

    }

    function style(feature) {
        return {
            fillColor: getColor(feature.properties.km2, numberScale),
            weight: 2,
            opacity: 1,
            color: 'white',
            dashArray: '3',
            fillOpacity: 0.6
        };
    }

    function highlightFeature(e) {
        let layer = e.target;

        layer.setStyle({
            weight: 4,
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

    function zoomToFeature(e) {
        mymap.fitBounds(e.target.getBounds());
    }

    function onEachFeature(feature, layer) {
        layer.nameLayer = "zonyGeoJSON",
            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                click: zoomToFeature
            });
    }


    let infoBox = L.control();

    infoBox.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
        this.update();
        return this._div;
    };

// method that we will use to update the control based on feature properties passed
    infoBox.update = function (props) {
        this._div.innerHTML = '<h5>Коефіцієнт км2</h5>' + (props ?
            'Зона - <b>' + props.name + '</b><br />' + 'Км2 - ' + props.km2
            : 'Наведіть для отримання інформації');
    };

    let legend = L.control({position: 'bottomright'});

    legend.onAdd = function (map) {

        let div = L.DomUtil.create('div', 'info legend list-unstyled'),
            grades = [0, 0.8, 0.9, 1, 1.2],
            labels = [];

        // loop through our density intervals and generate a label with a colored square for each interval
        div.innerHTML += '<h6>Умовні позначення</h6>';
        for (let i = 0; i < grades.length; i++) {
            div.innerHTML +=
                '<div><i style="background:' + getColor(grades[i] + 0.1) + '"></i> <p>' +
                grades[i] + (grades[i + 1] ? '&ndash;' + '&nbsp;' + grades[i + 1] : '+') + '</p></div>';
        }

        return div;
    };

    function createBlockErrors(data) {
        let div = document.createElement('div');
        let htmlDiv = '<div class="alert alert-warning alert-dismissible" id="error"> ' +
            '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
            '<h5><i class="icon fas fa-exclamation-triangle"></i> Виникла помилка!</h5></div>';

        $(div).prepend(htmlDiv);

        $.each(data, function (index, value) {
            $(div).find('#error').append(value + '<br>');
        });

        textContent.prepend(div);
    }

});