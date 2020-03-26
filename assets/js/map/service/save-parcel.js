$(document).ready(function () {
    const overlayControl = $('#feature-card .overlay');

    $('#save-parcel').on('click', function (e) {
        e.preventDefault();

        let cadNum = $("#feature-card-cud-num").html();
        let layerName = findLayerByCadNum(cadNum);
        if (typeof layerName === 'undefined') {
            toastr.options = {"closeButton": true,};
            toastr.error('Ділянки з кадастровим номером ' + cadNum + ' не знайдено');
        } else {
            save(layerName);
        }
        hideTooltip();
    });

    function save(layerName) {
        $.ajax({
            url: Routing.generate('parcel_save'),
            method: 'POST',
            data: {
                'cadNum': layerName.feature.properties.cadNum,
                'newFileName': layerName.feature.properties.newFileName,
                'purpose': layerName.feature.properties.purpose,
            },
            dataType: 'json',

            beforeSend: function () {
                overlayControl[0].hidden = false;
            },
            success: function (data) {
                overlayControl[0].hidden = true;
                let dataJson = JSON.parse(data);

                if (dataJson.errors.length) {
                    errorsHandler(dataJson.errors);

                    return false;
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
            },
            error: function (jqXHR, textStatus, errorThrown) {
                overlayControl[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        })
    }

    function findLayerByCadNum(cadNum) {
        let layer;
        for (let l in parcelLayer._layers) {
            if (parcelLayer._layers[l].feature.properties.cadNum === cadNum) {
                layer = parcelLayer._layers[l];
            }
        }

        return layer;
    }
});