$(document).ready(function () {
    const overlayShp = $('#shp-card .overlay');
    const overlayInfo = $('#info-card .overlay');
    const overlayControl = $('#buttons-card .overlay');
    const textContent = $('#text-content');
    const btnDownloadShp = $('#btn-download-shp');
    const btnDownloadShpMenu = $('#download-shp-normative');
    const featureCart = $('#feature-card .overlay');

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
                    addParcelsToTable(dataJson);
                    $('#feature-card').removeClass('d-none');
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            addOverlay(VAL_TRUE);
            servicesThrowErrors(jqXHR);
        },
    });

    $('#parcel-search').on('keyup', function (e) {
        let searchData = $(this).val();
        searchByVal(searchData);
    });


    $('#clear-search').on('click', function (e) {
        searchByVal('');
        $('#parcel-search').val('');
    });

    function searchByVal(searchData) {
        $.ajax({
            url: Routing.generate('parcel_search'),
            method: 'POST',
            dataType: 'json',
            data: {'search': searchData},
            success: function (data) {
                if (data) {
                    let dataJson = JSON.parse(data);
                    if (dataJson.length) {
                        addParcelsToTable(dataJson);
                    } else {
                        let $table = $('#parcels-list');
                        $($table).find('tbody').empty();
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                servicesThrowErrors(jqXHR);
            },
        });
    }

    function addOverlay(valBool) {
        overlayShp[0].hidden = Boolean(valBool);
        overlayControl[0].hidden = Boolean(valBool);
        overlayInfo[0].hidden = Boolean(valBool);
    }

    function calcWidth576() {
        let screenWidth = window.matchMedia('all and (max-width: 576px)');
        return screenWidth.matches;
    }

    function calcWidth() {
        let screenWidth = window.matchMedia('all and (max-width: 1199px)');
        return screenWidth.matches;
    }

    if (calcWidth576()) {
        $('.main-footer h6 small:eq(1)').removeClass('ml-3');
        $('.main-footer h6 small:eq(1)').html('<a href="mailto:xmlred.xyz@gmail.com" class="text-nowrap text-gray">' +
            '<i class="far fa-envelope align-middle"></i> xmlred.xyz@gmail.com</a>');
    }


    $('#open-xml-normative, #btn-open-xml-alt').on('click', function (e) {
        e.preventDefault();
        $('#btn-open-xml').click();
        hideTooltip();
    });

    $('#open-xml-normative-test').on('click', function (e) {
        e.preventDefault();
        let formData = new FormData();
        formData.append('xmlFile', 'test');
        sendFile(formData);
    });
    let $openXmlFile = $('#open_xmlFile_form');

    $($openXmlFile).on('change', function (event) {
        let inputFile = event.currentTarget;
        $(inputFile).parent()
            .find('.custom-file-label')
            .html(inputFile.files[0].name);
        let fileXML = $($openXmlFile)[0].files[0];
        let formData = new FormData();
        formData.append("xmlFile", fileXML);
        sendFile(formData);
        $($openXmlFile)[0].closest('.d-inline-block').reset();
    });

    $(btnDownloadShpMenu).on('click', function (e) {
        e.preventDefault();
        $(btnDownloadShp).click();
        hideTooltip();
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
                btnValidateXml.prop('disabled', false);
                btnValidateXml.find('i').addClass('text-success');
                btnDownloadShp.prop('disabled', false);
                btnDownloadShpMenu.removeClass('disabled');
                btnDownloadShp.find('i').addClass('text-success');

                let dataJson = JSON.parse(data);

                if (dataJson.errors.length) {
                    $(btnDownloadShp).prop('disabled', true);
                    $('#shp-card').attr('data-name', "");
                    createBlockErrors(dataJson.errors);
                } else {
                    setStartPosition();
                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);

                    addLayers(dataJson);
                    calcPoints(dataJson);
                    visualizeXML(dataJson);
                    addGeneralData(dataJson.boundary);
                    $('#xml-card').removeClass('d-none');
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

    $('body').on('click', '.table-delete', function (e) {

        let cadNum = getCadNumFromRow(this);

        $('#parcel-cadNum').attr('data-cadNum', cadNum);
        $('#modal-sm .modal-body').html('<p>Ви дійсно бажаєте видалити ділянку<span class="text-bold"> ' + cadNum + '?</span></p>');
    });


    $('body').on('click', '.table-zoom', function (e) {
        e.preventDefault();
        $('#calculate-parcel').removeClass('disabled');

        let boundsStr = $(this).attr('data-bounds');
        let bound = setBounds(boundsStr);
        if (bound.length) mymap.fitBounds(bound);

        let cadNum = getCadNumFromRow(this);

        parcelFromBaseLayer.setStyle(parcelFromBaseStyle);
        let layer = getParcelLayerByCadNum(cadNum);
        if (typeof layer !== 'undefined') layer.setStyle(addFeatureFromJsonSelectedStyle);

        setParcelValueInTable(layer);
    });

    function getCadNumFromRow(element) {
        let elementCadNum = $(element).closest('tr')[0];
        return $(elementCadNum).attr('data-cadNum');
    }

    function getParcelLayerByCadNum(cadNum) {
        let parcelLayer;
        parcelFromBaseLayer.eachLayer(function (layer) {
            if (layer.feature.properties.cadnum === cadNum) {
                parcelLayer = layer;
                return false;
            }
        });

        return parcelLayer;
    }


    /**
     * @param {string} $boundsStr
     * @example BOX(30.2097959809193 50.6221500249898,30.210626152964 50.6231538589455)
     *
     * @returns {[]}
     * @example [30.2097959809193 50.6221500249898,30.210626152964 50.6231538589455]
     */
    function setBounds($boundsStr) {
        let arrayBounds = [];
        if ($boundsStr.trim() !== '') {
            $boundsStr = $boundsStr.replace('BOX(', '');
            $boundsStr = $boundsStr.replace(')', '');
            let arraySplited = $boundsStr.split(',');
            arrayBounds.push([arraySplited[0].split(' ')[1], arraySplited[0].split(' ')[0]], [arraySplited[1].split(' ')[1], arraySplited[1].split(' ')[0]]);
        }
        return arrayBounds;
    }


    function setStyleIn(id) {
        intersectLocalLayersGroup.eachLayer(function (layer) {
            if (Number(layer.feature.properties.id) === Number(id)) {
                layer.setStyle(intersectLocalsSelectedStyle);
                layer.bringToFront();
            }
        });
    }


    $('body').on('click', '#parcel-delete', function (e) {
        let cadNum = $('#parcel-cadNum').attr('data-cadNum');
        $('#modal-sm').modal('hide');

        $.ajax({
            url: Routing.generate('parcel_delete'),
            method: 'POST',
            dataType: 'json',
            data: {'cadNum': cadNum},
            beforeSend: function () {
                featureCart[0].hidden = false;
            },
            success: function (data) {
                featureCart[0].hidden = true;
                let dataJson = JSON.parse(data);

                if (dataJson.errors.length) {
                    createPopupError(dataJson.errors);
                    return true;
                }

                toastr.options = {"closeButton": true,};
                toastr.success(dataJson.msg);

                if (mymap.hasLayer(parcelFromBaseGroup)) {
                    mymap.removeLayer(parcelFromBaseGroup);
                }
                parcelFromBaseGroup.clearLayers();

                if (mymap.hasLayer(parcelGroup)) {
                    mymap.removeLayer(parcelGroup);
                }
                parcelGroup.clearLayers();
                addParcelsToMap(dataJson.parcelsJson);
                addParcelsToTable(dataJson.parcelsJson);
                setParcelValueInTable();
                $('#calculate').remove();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                featureCart[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        });
    });

    function createPopupError(errorArr) {
        toastr.options = {"closeButton": true,};
        toastr.error(errorArr[0]);
    }

    function visualizeXML(data) {
        let wrapper = document.getElementById("wrapper");
        let tree = jsonTree.create(data.origXml, wrapper);
        if (calcWidth576()) {
            $('#original_name_file').html(cattingLongName(data.origXmlName));
        } else {
            $('#original_name_file').html(data.origXmlName);
        }

        $('#shp-card').attr('data-name', data.newXmlName);

        tree.expand(function (node) {
            return node.childNodes.length < 2 || node.label === 'phoneNumbers';
        });
    }

    function cattingLongName(name, val = 30) {
        let arr = name.split('.');

        if (arr[0].length > val) {
            arr[0] = arr[0].slice(0, val) + '..';
            return arr.join('.');
        }

        return name;
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

    function calcPoints(dataJson) {
        if (dataJson.zones) {
            let zonePoints = sumPointsInGroup(dataJson.zones);
        }
        if (dataJson.regions) {
            let regionsPoints = sumPointsInGroup(dataJson.regions);
        }
        if (dataJson.localFactor) {
            let localFactorsPoints = sumPointsInGroup(dataJson.localFactor);
        }
        if (dataJson.lands) {
            let landsPoints = sumPointsInGroup(dataJson.lands);
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

    $('#local-toggle').on('click', function () {
        $('#row-locals-list').toggleClass('d-none');
    });
});