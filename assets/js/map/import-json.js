$(document).ready(function () {
    const overlayControl = $('#buttons-card .overlay');

    $('#file-form-jsonFile').on('change', function (event) {
        let fileJson = $("#file-form-jsonFile")[0].files[0];
        let formData = new FormData();
        formData.append("jsonFile", fileJson);
        sendFile(formData);
    });

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('impontJson'),
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
                let bounds = addFeatureToMap(dataJson.json);

                setData(dataJson, bounds);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlayControl[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        })
    }

    function clearTableCalculate() {
        $('#calculate').remove();
        $('#feature-card-area').html('');
        $('#feature-card-cud-num').html('');
    }

    function setData(data, bounds) {
        $('#geom-from-json').val(data.wkt);
        $('#geom-from-json').attr("data-bounds", bounds);
        if($('#shp-card').attr('data-name')!== '') {
            $('#feature-from-json').removeClass('disabled');
        }
        $('#feature-card').removeClass('d-none');
    }

    /**
     * Додаємо імпортовану ділянку до карти
     *
     * @param data
     * @returns {string}
     */

    function addFeatureToMap(data) {
        let objData = JSON.parse(data);
        let feature = [{
            "type": "Feature",
            "properties": {"name": "Parcel"},
            "geometry": {
                "type": objData.type,
                "coordinates": objData.coordinates,
            }
        }];
        let polygon = L.geoJSON(feature, {
            style: addFeatureFromJsonStyle
        });

        parcelGroup.addLayer(polygon);

        /** Додаємо групу до карти    */
        parcelGroup.addTo(mymap);

        mymap.fitBounds(polygon.getBounds());

        return JSON.stringify(polygon.getBounds());
    }
});