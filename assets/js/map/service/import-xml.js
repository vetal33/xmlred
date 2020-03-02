$(document).ready(function () {
    const overlayControl = $('#buttons-card .overlay');
    const $importXml = $('#file-form-xmlFileImport');

    //mymap.addLayer(parcelGroup);

    $($importXml).on('change', function (event) {
        console.log('dfd');
        let fileXml = $($importXml)[0].files[0];
        let formData = new FormData();
        formData.append("xmlFile", fileXml);
        sendFile(formData);
        $($importXml)[0].closest('.d-inline-block').reset();
    });

/*    $('#import-json').on('click', function (e) {
        e.preventDefault();
        $('#btn-import-json').click();
    });*/

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
                //clearTableCalculate();

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