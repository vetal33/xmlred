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

    $('#import-json, #btn-import-json-alt').on('click', function (e) {
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
                $('#calculate').remove();

                let dataJson = JSON.parse(data);
                if (dataJson.errors.length) {
                    errorsHandler(dataJson.errors);

                    return false;
                }

                let bounds = addFeatureToMap(dataJson);
                setDataToParcelTable(dataJson, bounds);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlayControl[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        })
    }
});