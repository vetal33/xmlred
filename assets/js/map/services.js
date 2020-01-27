$(function () {
    const overlay = $('#shp-card .overlay');
    const textContent = $('#text-content');
    const btnDownloadShp = $('#btn-download-shp');


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

    function sendFile(data) {
        $.ajax({
            url: Routing.generate('homepage'),
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
                console.log(dataJson);

                if (dataJson.errors.length > 0) {
                    $(btnDownloadShp).addClass('disabled');
                    $('#shp-card').attr('data-name', "");
                    createBlockErrors(dataJson.errors);
                } else {
                    $(btnDownloadShp).attr('href', '/load?name=' + dataJson.newXmlName);
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
                    visualizeXML(dataJson);
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

            $('#marker-boundary').html('<i class="fas fa-check text-success"></i>')
        }
    }

    function createBlockErrors(data) {
        let divElement = document.createElement('div');
        let htmlDiv = '<div class="alert alert-warning alert-dismissible" id="error"> ' +
            '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
            '<h5><i class="icon fas fa-exclamation-triangle"></i> Виникла помилка!</h5></div>';

        $(divElement).prepend(htmlDiv);

        $.each(data, function (index, value) {
            $(divElement).find('#error').append(value + '<br>');
        });

        textContent.prepend(divElement);
    }

    function createTableShp(data) {
        const tableShp = document.createElement('');

        let htmlDiv = '<tr><td>$13 USD</td><td>$13 USD</td>' +
            '<td><a href="#" class="text-muted"><i class="fas fa-search"></i></a></td></tr>';

        $.each(data, function (index, value) {
            $(divElement).find('#error').append(value + '<br>');
        });
    }
});