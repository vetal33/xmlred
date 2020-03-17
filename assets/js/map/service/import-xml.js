$(document).ready(function () {
    const overlayControl = $('#buttons-card .overlay');
    const $importXml = $('#file-form-xmlFileImport');

    $($importXml).on('change', function (event) {
        let fileXml = $($importXml)[0].files[0];
        let formData = new FormData();
        formData.append("xmlFile", fileXml);
        sendFile(formData);
        $($importXml)[0].closest('.d-inline-block').reset();
    });

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('import_xmlFile'),
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