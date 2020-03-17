$(document).ready(function () {
    const overlay = $('#feature-card .overlay');

    $('body').on('click', '#calculate-parcel', function (e) {
        e.preventDefault();
        let nameFile = $('#shp-card').attr('data-name');
        let feature = $('#geom-from-json').val();
        let cadNum;
        if (feature === '') {
            cadNum = $('#feature-card-cud-num').html();
        }

        if (nameFile.trim() !== '' && (feature.trim() !== '' || cadNum.trim() !== '')) {
            checkFile(nameFile, feature, cadNum);
        } else {
            toastr.options = {"closeButton": true,};
            toastr.error('Відсуті шари Нормативної грошової оцінки!');
        }
    });

    function checkFile(fileName, feature, cadNum) {
        $.ajax({
            url: Routing.generate('calculateNormative'),
            method: 'POST',
            data: {"fileName": fileName, "feature": feature, "cadNum": cadNum},
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

        let basePrice = ($('#general-base-price').attr('data-base-price') !== '') ? Number($('#general-base-price').attr('data-base-price')) : 150.00;
        let basePriceStr = basePrice + '&nbsp;' + 'грн.';
        let normativeTable = $('<div id="calculate" class="p-2"><h6 class="text-truncate ml-3 mt-4">Розрахунок</h6>' +
            '<table class="table table-hover table-sm pl-2 pr-2 bc-gray">' +
            '<tbody id="normativeTable">' +
            '<tr><td class="pl-3">Базова вартість</td><td class="text-center">' + basePriceStr + '</td></tr>' +
            '<tr><td class="pl-3"><span class="text-bold test-success">' + data.zone.name + '</span> економіко-планувальна зона</td><td class="text-center">' +
            data.zone.km2 + '</td></tr>' +
            '</tbody></table></div>');
        $('#feature-card #custom-content-calculate').append(normativeTable);

        $.each(data.local, function (index, value) {
            let percent = Math.round((value.area / data.area) * 100).toFixed() + '%';
            let row = '<tr data-id="' + value.id + '"><td class="pl-3 text-primary"><small>' + value.name + '</small></td><td class="text-center pr-1"><small>' + percent + '</small></td></tr>';
            let str = normativeTable.find('#normativeTable').append(row);
        });

        let price = (Math.round(basePrice * parseFloat(data.zone.km2) * area * 10000 * 100) / 100).toFixed(2);
        let priceStr = price + ' грн.';
        let baseZone = '<tr><td class="pl-3">Вартість в <span class="text-bold test-success">' + data.zone.name + '</span>-й економіко-планувальній зоні</td><td class="text-center pr-1">' + data.calculate.priceZone + ' грн.</td></tr>';
        normativeTable.find('#normativeTable').append(baseZone);
        let localTotal = '<tr><td class="pl-3">Узагальнюючий локальний коефіцієнт</td><td class="text-center pr-1">' + data.calculate.priceLocal + '</td></tr>';
        normativeTable.find('#normativeTable').append(localTotal);
        let purposeIndex = '<tr><td class="pl-3">Коефіцієнт, який характеризує функціональне використання землі</td><td class="text-center pr-1"> 1.0 </td></tr>';
        normativeTable.find('#normativeTable').append(purposeIndex);
        let totalM2 = '<tr><td class="pl-3">Всього за 1 м<sup>2</sup> (' + data.calculate.priceZone + ' * ' + data.calculate.priceLocal + ' * ' + '1.0' + ')</td><td class="text-center pr-1"><strong>' + data.calculate.priceByMeter + ' грн.</strong></td></tr>';
        normativeTable.find('#normativeTable').append(totalM2);
        let total = '<tr><td class="pl-3">Всього за ділянку (' + data.calculate.priceByMeter + ' * ' + area + ' га)</td><td class="text-center pr-1"><strong>' + data.calculate.priceTotal + ' грн.</strong></td></tr>';
        normativeTable.find('#normativeTable').append(total);

    }

    /**
     * Додаємо шари перетину з локальними факторами до карти
     *
     * @param data
     */
    function addIntersectLayers(data) {
        removeLayersGlob('IntersectGeoJSON');
        intersectLocalLayersGroup.clearLayers();

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