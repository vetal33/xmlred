$(document).ready(function () {
    const overlayControl = $('#buttons-card .overlay');

    mymap.addLayer(parcelGroup);

    $('#file-form-jsonFile').on('change', function (event) {
        let fileJson = $("#file-form-jsonFile")[0].files[0];
        let formData = new FormData();
        formData.append("jsonFile", fileJson);
        sendFile(formData);
        $("#file-form-jsonFile")[0].closest('.d-inline-block').reset();
    });

    $('#import-json').on('click', function (e) {
        e.preventDefault();
        $('#btn-import-json').click();
    });

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('importJson'),
            method: 'POST',
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function () {
                overlayControl[0].hidden = false;
            },
            success: function (data) {
                overlayControl[0].hidden = true;
                clearTableCalculate();

                let dataJson = JSON.parse(data);

                if (dataJson.errors.length > 0) {
                    toastr.options = {"closeButton": true,};
                    toastr.error(dataJson.errors[0]);
                } else {
                    let bounds = addFeatureToMap(dataJson);
                    setData(dataJson, bounds);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlayControl[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        })
    }

    function clearTableCalculate() {
        $('#calculate').remove();
    }

    function setData(data, bounds) {
        $('#geom-from-json').val(data.wkt);
        $('#geom-from-json').attr("data-bounds", bounds);

        let area = (Math.round(data.area) / 10000).toFixed(4);
        let areaStr = area + ' га';

        $('#feature-card-area').html(areaStr);

        if (typeof data.pub !== 'undefined') {
            $('#feature-card-cud-num').html(data.pub[0].cadnum);
            $('#feature-purpose').html(data.pub[0].purpose);
        } else {
            $('#feature-card-cud-num').html('не визначено');
        }


        if ($('#shp-card').attr('data-name') !== '') {
            $('#calculate-parcel').removeClass('disabled');
        }
        $('#feature-card').removeClass('d-none');
        $('#save-parcel').removeClass('disabled');
    }

    /**
     * Додаємо імпортовану ділянку до карти
     *
     * @param data
     * @returns {string}
     */

    function addFeatureToMap(data) {
        let objData = JSON.parse(data.json);
        let cudNum = (typeof data.pub !== 'undefined') ? data.pub[0].cadnum : '';
        let purpose = (typeof data.pub !== 'undefined') ? data.pub[0].purpose : '';
        let area = (Math.round(data.area) / 10000).toFixed(4);
        let areaStr = area + ' га';

        let feature = [{
            "type": "Feature",
            "properties": {
                "name": "Parcel",
                "cadNum": cudNum,
                "area": areaStr,
                "wkt": data.wkt,
                "newFileName": data.newFileName,
                "purpose": purpose,
            },
            "geometry": {
                "type": objData.type,
                "coordinates": objData.coordinates,
            }
        }];


        let polygon = parcelLayer.addData(feature);
        polygon.setStyle(addFeatureFromJsonStyle);

        parcelGroup.addLayer(polygon);

        let parcel = L.geoJSON(feature);
        mymap.fitBounds(parcel.getBounds());

        /** Додаємо групу до карти    */
        parcelGroup.addTo(mymap);

        return JSON.stringify(parcel.getBounds());
    }
});