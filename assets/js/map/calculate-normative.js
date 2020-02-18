$(document).ready(function () {
    const overlay = $('#feature-card .overlay');

    $('body').on('click', '#feature-from-json', function (e) {
        e.preventDefault();
        let nameFile = $('#shp-card').attr('data-name');
        let feature = $('#geom-from-json').val();

        if (nameFile.trim() !== '' && feature.trim() !== '') {
            checkFile(nameFile, feature);
        } else {
            toastr.options = {"closeButton": true,};
            toastr.error('Відсуті шари Нормативної грошової оцінки!');
        }
    });

    function checkFile(fileName, feature) {
        $.ajax({
            url: Routing.generate('calculateNormative'),
            method: 'POST',
            data: {"fileName": fileName, "feature": feature},
            dataType: 'json',
            beforeSend: function () {
                overlay[0].hidden = false;
            },
            success: function (data) {
                overlay[0].hidden = true;
                let dataJson = JSON.parse(data);

                if (dataJson.errors) {
                    toastr.options = {"closeButton": true,};
                    toastr.error(dataJson.errors);
                } else {
                    createNormativeTable(JSON.parse(data));
                    addIntersectLayers(JSON.parse(data));
                    toastr.options = {"closeButton": true,};
                    toastr.success('Нормативна грошова оцінка успішно порахована!');
                }
            },
            error: function (jqXHR) {
                overlay[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        })
    }

    /**
     * Створює таблицю з оцінкою земельної ділянки
     *
     * @param data
     */

    function createNormativeTable(data) {
        $('#calculate').remove();
        let area = (Math.round(data.area) / 10000).toFixed(4);
        let areaStr = area + ' га';

        $('#feature-card-area').html(areaStr);

        if (typeof data.pub !== 'undefined') {
            $('#feature-card-cud-num').html(data.pub[0].cadnum);
        } else {
            $('#feature-card-cud-num').html('не визначено');
        }

        let basePrice = ($('#general-base-price').attr('data-base-price') !== '') ? Number($('#general-base-price').attr('data-base-price')) : 150.00;
        let basePriceStr = basePrice + '&nbsp;' + 'грн.';
        let normativeTable = $('<div id="calculate" class="p-2"><h6 class="text-truncate ml-3 mt-4">Розрахунок</h6>' +
            '<table class="table table-hover table-sm pl-2 pr-2 bc-gray">' +
            '<tbody id="normativeTable">' +
            '<tr><td class="pl-3">Базова вартість</td><td class="text-center">' + basePriceStr + '</td></tr>' +
            '<tr><td class="pl-3"><span class="text-bold test-success">' + data.zone.name + '</span> економіко-планувальна зона</td><td class="text-center">' +
            data.zone.km2 + '</td></tr>' +
            '</tbody></table></div>');
        $('#feature-card .card-body').append(normativeTable);

        $.each(data.local, function (index, value) {
            let percent = Math.round((value.area / data.area) * 100).toFixed() + '%';
            let row = '<tr data-id="' + value.id + '"><td class="pl-3 text-primary"><small>' + value.name + '</small></td><td class="text-center pr-1"><small>' + percent + '</small></td></tr>';
            let str = normativeTable.find('#normativeTable').append(row);
        });
        let price = (Math.round(basePrice * parseFloat(data.zone.km2) * area * 10000 * 100) / 100).toFixed(2);
        let priceStr = price + ' грн.';
        let total = '<tr><td class="pl-3" colspan="2">Разом: ' + basePrice + ' * ' + data.zone.km2 + ' * ' + '1.0' + ' * ' + area + ' = <strong>' + priceStr + '</strong></td></tr>';
        normativeTable.find('#normativeTable').append(total);
    }

    /**
     * Додаємо шари перетину з локальними факторами до карти
     *
     * @param data
     */
    function addIntersectLayers(data) {
        let geojson;

        let new_data = data.local.map(function (item) {
            let coord = JSON.parse(item.geom);

            return item_new = {
                "type": "Feature",
                "properties": {
                    "code": item.code,
                    "id": item.id,
                },
                "geometry": {
                    "type": "Polygon",
                    "coordinates": coord.coordinates,
                },
            };
        });

        geojson = L.geoJson(new_data, {
            style: intersectLocalsStyle,
            onEachFeature: onEachFeature,
        });

        /** Додаємо групу до карти    */
        intersectLocalLayersGroup.addTo(mymap);
    }

    function onEachFeature(feature, layer) {
        layer.nameLayer = "IntersectGeoJSON";
        intersectLocalLayersGroup.addLayer(layer);
    }
});