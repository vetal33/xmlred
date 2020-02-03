$(document).ready(function () {
    const overlayShp = $('#shp-card .overlay');
    const overlayControl = $('#buttons-card .overlay');
    const textContent = $('#text-content');
    const btnDownloadShp = $('#btn-download-shp');
    const btnValidateXml = $('#btn-validate-xml');

    window.zonyLayersGroup = L.layerGroup();
    window.localLayersGroup = L.layerGroup();
    window.landsLayersGroup = L.layerGroup();


    overlayShp[0].hidden = true;
    overlayControl[0].hidden = true;

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

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('homepage'),
            method: 'POST',
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function () {
                overlayShp[0].hidden = false;
                overlayControl[0].hidden = false;
            },
            success: function (data) {

                overlayShp[0].hidden = true;
                overlayControl[0].hidden = true;
                btnValidateXml.removeClass('disabled');
                btnValidateXml.find('i').addClass('text-success');
                btnDownloadShp.removeClass('disabled');
                btnDownloadShp.find('i').addClass('text-success');

                let dataJson = JSON.parse(data);
                console.log(dataJson);

                if (dataJson.errors.length > 0) {
                    $(btnDownloadShp).addClass('disabled');
                    $('#shp-card').attr('data-name', "");
                    createBlockErrors(dataJson.errors);
                } else {
                    setStartPosition();
                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);

                    addLayers(dataJson);
                    visualizeXML(dataJson);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlayShp[0].hidden = true;
                overlayControl[0].hidden = true;
                toastr.options = {"closeButton": true, };
                toastr.error('Вибачте виникла помилка!');
                console.log(jqXHR);
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

        landsLayersGroup.remove();
        zonyLayersGroup.remove();
        localLayersGroup.remove();

        $('#local').prop('checked', true);
        $('#zony').prop('checked', true);
        $('#lands').prop('checked', true);

        $('#xml-card').removeClass('card-outline card-danger');
        $('#xml-card').removeClass('card-outline card-success');
    }

    function addLayers(dataJson) {
        addMejaToMap(dataJson.boundary, boundaryStyle);
        if (dataJson.zony) {
            addZonyToMap(dataJson.zony);
        }
        if (dataJson.localFactor) {
            addLocalToMap(dataJson.localFactor);
        }
        if (dataJson.lands) {
            addLandsToMap(dataJson.lands);
        }
/*        mymap.eachLayer(function (layer) {
            console.log(layer);
        });*/
    }
});