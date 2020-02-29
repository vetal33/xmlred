$(document).ready(function () {
    const overlayShp = $('#shp-card .overlay');
    const overlayInfo = $('#info-card .overlay');
    const overlayControl = $('#buttons-card .overlay');
    const textContent = $('#text-content');
    const btnDownloadShp = $('#btn-download-shp');
    const btnDownloadShpMenu = $('#download-shp-normative');

    const btnValidateXml = $('#btn-validate-xml');
    const VAL_TRUE = 1;
    const VAL_FALSE = 0;

    /**  Створюєм глобальний об'єкт Map   */
    window.mymap = L.map('map').setView([48.5, 31], 6);

    /**  Створюєм глобальні групи шарів   */
    window.mejaLayersGroup = L.layerGroup();
    window.zonyLayersGroup = L.layerGroup();
    window.localLayersGroup = L.layerGroup();
    window.landsLayersGroup = L.layerGroup();
    window.regionsLayersGroup = L.layerGroup();
    window.intersectLocalLayersGroup = L.layerGroup();
    window.parcelFromBaseGroup = L.layerGroup();
    window.parcelGroup = L.layerGroup();
    window.parcelLayer = L.geoJSON();
    window.parcelFromBaseLayer = L.geoJSON();
    window.pointLayer = L.geoJSON();
    window.markerLayer = L.marker();

    let normativeGroupArray = [mejaLayersGroup, zonyLayersGroup, localLayersGroup, landsLayersGroup, regionsLayersGroup];

    addOverlay(VAL_TRUE);
    addBaseLayars();

    $.ajax({
        url: Routing.generate('loadParcels'),
        method: 'POST',
        dataType: 'json',
        beforeSend: function () {
            addOverlay(VAL_FALSE);
        },
        success: function (data) {
            addOverlay(VAL_TRUE);
            if (data) {
                let dataJson = JSON.parse(data);
                if (dataJson.length) {
                    addParcelsToMap(dataJson);
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            addOverlay(VAL_TRUE);
            servicesThrowErrors(jqXHR);
        },
    });

    function addOverlay(valBool) {
        overlayShp[0].hidden = Boolean(valBool);
        overlayControl[0].hidden = Boolean(valBool);
        overlayInfo[0].hidden = Boolean(valBool);
    }

    function calcWidth() {
        let screenWidth = window.matchMedia('all and (max-width: 1199px)');
        return screenWidth.matches;
    }

    $('#open-xml-normative').on('click', function (e) {
        e.preventDefault();
        $('#btn-check-xml').click();
    });

    $('#open-xml-normative-test').on('click', function (e) {
        e.preventDefault();
        let formData = new FormData();
        formData.append('xmlFile', 'test');
        sendFile(formData);
    });

    $('#file_form_xmlFile').on('change', function (event) {
        let inputFile = event.currentTarget;
        $(inputFile).parent()
            .find('.custom-file-label')
            .html(inputFile.files[0].name);
        let fileXML = $("#file_form_xmlFile")[0].files[0];
        let formData = new FormData();
        formData.append("xmlFile", fileXML);
        sendFile(formData);
        $("#file_form_xmlFile")[0].closest('.d-inline-block').reset();
    });

    $(btnDownloadShpMenu).on('click', function (e) {
        e.preventDefault();
        $(btnDownloadShp).click();
    });

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('homepage'),
            method: 'POST',
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function () {
                addOverlay(VAL_FALSE);
            },
            success: function (data) {
                addOverlay(VAL_TRUE);
                btnValidateXml.removeClass('disabled');
                btnValidateXml.find('i').addClass('text-success');
                btnDownloadShp.removeClass('disabled');
                btnDownloadShpMenu.removeClass('disabled');
                btnDownloadShp.find('i').addClass('text-success');

                let dataJson = JSON.parse(data);

                if (dataJson.errors.length > 0) {
                    $(btnDownloadShp).addClass('disabled');
                    $('#shp-card').attr('data-name', "");
                    createBlockErrors(dataJson.errors);
                } else {
                    setStartPosition();
                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);

                    addLayers(dataJson);
                    visualizeXML(dataJson);
                    addGeneralData(dataJson.boundary);
                    parcelFromBaseLayer.bringToFront();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                addOverlay(VAL_TRUE);
                servicesThrowErrors(jqXHR);
            },
        })
    }

    function addGeneralData(data) {
        let region, district, city;

        if (calcWidth() === true) {
            $('#info-card').removeClass('d-none');
            $('.general-info').addClass('d-none');
        } else {
            $('#info-card').addClass('d-none');
            $('.general-info').removeClass('d-none');
        }

        if (data.ValuationYear !== 'undefined') {
            $('#general-year').html('<i class="far fa-calendar-alt mr-1"></i>' + data.ValuationYear + ' р.');
            $('#card-general-year').html(data.ValuationYear + ' р.');
        }
        if (data.Cnm !== 'Cnm') {
            $('#general-base-price').html('<i class="far fa-money-bill-alt mr-1"></i>' + data.Cnm + ' грн./м<sup>2</sup>');
            $('#general-base-price').attr('data-base-price', data.Cnm);
            $('#card-general-base-price').html(data.Cnm + ' грн./м<sup>2</sup>');
        }
        if (data.Population !== 'undefined') {
            $('#general-population').html('<i class="fas fa-users mr-1"></i>' + data.Population + ' чол.');
            $('#card-general-population').html(data.Population + ' чол.');
        }
        if (data.Size !== 'undefined') {
            $('#general-area').html('<i class="fas fa-vector-square mr-1"></i>' + data.Size + ' ' + data.MeasurementUnit);
            $('#card-general-area').html(data.Size + ' ' + data.MeasurementUnit);
        }
        if (data.Region !== 'undefined') {
            region = data.Region;
        }
        if (data.District !== 'undefined') {
            district = data.District;
        }
        if (data.MunicipalUnitName !== 'undefined') {
            city = data.MunicipalUnitName;
        }

        $('#general-address').html('<i class="fas fa-map-marked-alt mr-1"></i>' + region + ' ' + district + ' ' + city);
        $('#card-general-address').html(region + ' ' + district + ' ' + city);
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

    function addMejaToMap(data) {
        let geoJsonObj = JSON.parse(data);
        if (typeof (geoJsonObj) == "object") {
            let polygonMeja = L.geoJSON(geoJsonObj, {
                style: boundaryStyle,
            });

            polygonMeja.nameLayer = "mejaGeoJSON";
            polygonMeja.addTo(mejaLayersGroup);
            mejaLayersGroup.addTo(mymap);
            mymap.fitBounds(polygonMeja.getBounds());

            $('#marker-boundary').html('<i class="fas fa-check text-success"></i>')
        }
    }

    function createBlockErrors(data) {
        let divElement = document.createElement('div');
        divElement.classList.add("col-12");
        divElement.classList.add("mt-2");
        let htmlDiv = '<div class="alert alert-warning alert-dismissible" id="error"> ' +
            '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
            '<h5><i class="icon fas fa-exclamation-triangle"></i> Виникла помилка!</h5></div>';

        $(divElement).prepend(htmlDiv);

        $.each(data, function (index, value) {
            $(divElement).find('#error').append(value + '<br>');
        });

        textContent.prepend(divElement);
    }

    function setStartPosition() {
        $('#errors-card').remove();

        $('#local').prop('checked', true);
        $('#zony').prop('checked', true);
        $('#lands').prop('checked', true);
        $('#regions').prop('checked', true);

        $('#xml-card').removeClass('card-outline card-danger');
        $('#xml-card').removeClass('card-outline card-success');
        $('#feature-card').addClass('d-none');
        $('#xml-card #wrapper').html('');
        $('.legends').remove();

        removeNormativeLayers();
    }

    function addLayers(dataJson) {

        mymap.addLayer(parcelGroup);
        addMejaToMap(dataJson.boundary.coordinates);
        if (dataJson.zones) {
            addZonyToMap(dataJson.zones);
        }
        if (dataJson.regions) {
            addRegionsToMap(dataJson.regions);
        }
        if (dataJson.localFactor) {
            addLocalToMap(dataJson.localFactor);
        }
        if (dataJson.lands) {
            addLandsToMap(dataJson.lands);
        }
    }

    /**
     * Видаляє базові шари Нормавної оцінки
     */

    function removeNormativeLayers() {
        $.each(normativeGroupArray, function (index, group) {
            if (mymap.hasLayer(group)) {
                mymap.removeLayer(group);
            }
            group.clearLayers()
        });
    }
});