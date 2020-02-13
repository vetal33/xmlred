$(document).ready(function () {

    $('#file-form-jsonFile').on('change', function (event) {
        let inputFile = event.currentTarget;
        /*        $(inputFile).parent()
                    .find('.custom-file-label')
                    .html(inputFile.files[0].name);*/
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
                /*                overlayShp[0].hidden = false;
                                overlayControl[0].hidden = false;*/
            },
            success: function (data) {

                /*                overlayShp[0].hidden = true;
                                overlayControl[0].hidden = true;*/
                /*                btnValidateXml.removeClass('disabled');
                                btnValidateXml.find('i').addClass('text-success');
                                btnDownloadShp.removeClass('disabled');
                                btnDownloadShp.find('i').addClass('text-success');*/

                let dataJson = JSON.parse(data);
                console.log(dataJson);
                $('#geom-from-json').val(dataJson.wkt);

                addFeature(dataJson.json);

                /*                if (dataJson.errors.length > 0) {
                                    $(btnDownloadShp).addClass('disabled');
                                    $('#shp-card').attr('data-name', "");
                                    createBlockErrors(dataJson.errors);
                                } else {
                                    setStartPosition();
                                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);

                                    addLayers(dataJson);
                                    visualizeXML(dataJson);
                                }*/
            },
            error: function (jqXHR, textStatus, errorThrown) {
                /*                overlayShp[0].hidden = true;
                                overlayControl[0].hidden = true;*/
                console.log(jqXHR);

                servicesThrowErrors(jqXHR);
            },
        })
    }

    function addFeature(data) {
        let objData = JSON.parse(data);
        let feature = [{
            "type": "Feature",
            "properties": {"party": "Republican"},
            "geometry": {
                "type": objData.type,
                "coordinates": objData.coordinates,
            }
        }];

        let polygon = L.geoJSON(feature, {
            style: addFeatureFromJsonStyle
        }).addTo(mymap);

        mymap.fitBounds(polygon.getBounds());
        createFeatureCard();
    }

    /**
     * Створює таблицю ділянки імпотра
     *
     *
     */

    function createFeatureCard() {
        $('#feature-card').removeClass('d-none');
    }

});