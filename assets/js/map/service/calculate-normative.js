$(document).ready(function () {
    const overlay = $('#feature-card .overlay');
    const $featureCart = $('#feature-card');

    $('body').on('click', '#calculate-parcel', function (e) {
        e.preventDefault();
        hideTooltip();
        let nameFile = $('#shp-card').attr('data-name');
        let normativeYear = $('#normative-year').attr('value');
        let feature = $('#geom-from-json').val();
        let cadNum;
        if (feature === '') {
            cadNum = $('#feature-card-cud-num').html();
        }

        if (nameFile.trim() === '' && (feature.trim() === '' || cadNum.trim() === '')) {
            $featureCart.removeClass('card-success');
            $featureCart.addClass('card-danger');
            errorsHandler(['Відсуті шари Нормативної грошової оцінки!']);

            return false;
        }

        calculate(nameFile, feature, cadNum, normativeYear);
    });

    function calculate(fileName, feature, cadNum, normativeYear) {
        $.ajax({
            url: Routing.generate('calculateNormative'),
            method: 'POST',
            data: {"fileName": fileName, "feature": feature, "cadNum": cadNum, "normativeYear": normativeYear},
            dataType: 'json',
            beforeSend: function () {
                overlay[0].hidden = false;
            },
            success: function (data) {
                overlay[0].hidden = true;
                let dataJson = JSON.parse(data);

                if (dataJson.errors) {
                    errorsHandler(dataJson.errors, 30000);
                    $featureCart.removeClass('card-success');
                    $featureCart.addClass('card-danger');

                    return false
                }
                $featureCart.removeClass('card-danger');
                $featureCart.addClass('card-success');
                createNormativeTable(JSON.parse(data));
                addIntersectLayers(JSON.parse(data));
                toastr.options = {"closeButton": true,};
                toastr.success('Нормативна грошова оцінка успішно порахована!');

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
            '<tr><td class="pl-3" colspan="2">Базова вартість</td><td class="text-center" colspan="3">' + basePriceStr + '</td></tr>' +
            '<tr><td class="pl-3" colspan="2"><span class="text-bold test-success">' + data.zone.name + '</span> економіко-планувальна зона</td><td class="text-center" colspan="3">' +
            data.zone.km2 + '</td></tr>' +
            '</tbody></table></div>');
        $('#feature-card #custom-content-calculate').append(normativeTable);

        $.each(data.local, function (index, value) {
            let percent = Math.round((value.area / data.area) * 100).toFixed() + '%';
            let row = '<tr data-id="' + value.id + '" data-code-local="' + value.code + '"><td class="pl-3 text-primary"><small>' + value.name + '</small></td>' +
                '<td class="text-center pr-1"><small>' + percent + '</small></td><td class="text-center pr-1" style="width: 90px"><small>' +
                value.minVal + ' - ' + value.maxVal + '</small></td><td><input type="text" class="form-control form-control-sm local-value" placeholder="" value="' + value.maxVal + '" style="width: 45px"></td><td class="pr-3 pl-2"><i class="fa fa-' + value.marker + '" aria-hidden="true"></i></td></tr>';
            let str = normativeTable.find('#normativeTable').append(row);
        });
        let multString = getMultLocalAsString(data.calculate.local);

        let price = (Math.round(basePrice * parseFloat(data.zone.km2) * area * 10000 * 100) / 100).toFixed(2);
        let priceStr = price + ' грн.';
        let baseZone = '<tr><td class="pl-3" colspan="2">Вартість в <span class="text-bold test-success">' + data.zone.name + '</span>-й економіко-планувальній зоні</td><td class="text-center pr-1" colspan="3">' + data.calculate.priceZone + ' грн.</td></tr>';
        normativeTable.find('#normativeTable').append(baseZone);
        let localTotal = '<tr><td class="pl-3 local-total-text" colspan="2">Узагальнюючий локальний коефіцієнт ' + multString + '</td><td class="text-center pr-1 local-total" colspan="3">' + data.calculate.priceLocal + '</td></tr>';
        normativeTable.find('#normativeTable').append(localTotal);

        let recomendKf = data.calculate.kf.toFixed(2);

        let purposeIndex = '<tr><td class="pl-3" colspan="5">Коефіцієнт, який характеризує функціональне використання землі</td></tr>';
        let purposeList = $('<tr><td colspan="2"><select id="select2-purpose" class="form-control"></select></td><td id="kf-value" class="text-center pr-1" colspan="3" >' + recomendKf + '</td></tr>');
        let optionSelected = '';

        $.each(data.calculate.purposeArr, function (index, value) {
            if (data.calculate.recommendPurpose == value.subsection) {
                optionSelected = 'selected="selected"';
            }
            let option = '<option ' + optionSelected + ' data-id="' + value.id + '" data-value="' + value.kfValue + '">' + value.subsection + ' ' + value.name + '</option>';
            let str = purposeList.find('#select2-purpose').append(option);
            optionSelected = '';
        });

        normativeTable.find('#normativeTable').append(purposeIndex);
        normativeTable.find('#normativeTable').append(purposeList);

        let totalM2 = '<tr><td id="price-by-meter-title" class="pl-3" colspan="2">Всього за 1 м<sup>2</sup> (' + data.calculate.priceZone + ' * ' + data.calculate.priceLocal + ' * ' + recomendKf + ')</td><td id="price-by-meter" class="text-center pr-1" colspan="3"><strong>' + data.calculate.priceByMeter + ' грн.</strong></td></tr>';
        normativeTable.find('#normativeTable').append(totalM2);
        let total = '<tr><td id="price-total-title" class="pl-3" colspan="2">Всього за ділянку (' + data.calculate.priceByMeter + ' * ' + area + ' га)</td><td id="price-total" class="text-center pr-1" colspan="3"><strong>' + data.calculate.priceTotal + ' грн.</strong></td></tr>';
        normativeTable.find('#normativeTable').append(total);
        let index = '<tr><td class="pl-3" colspan="2">Коефіцієнт індексації починаючи з ' + data.calculate.indexes.year + ' року</td><td class="text-center pr-1" colspan="3">' + data.calculate.indexes.possible + ' </td></tr>';
        normativeTable.find('#normativeTable').append(index);
        let totalWithindex = '<tr><td id="price-total-index-title" class="pl-3" colspan="2">Всього за ділянку з індексацією</td><td id="price-total-index" class="text-center pr-1" colspan="3"><strong>' + data.calculate.priceTotalWithIndex + ' грн.</strong></td></tr>';
        normativeTable.find('#normativeTable').append(totalWithindex);

        $('#select2-purpose').select2();
        $('#select2-purpose').on('select2:select', function () {
            let kfValue = $(this).find(":selected").data("value");
            $('#kf-value').html(kfValue);
            calculatePrice(data, kfValue, area);

        });

        $('.local-value').focusout(function () {
            let kfValue = $('#select2-purpose').find(":selected").data("value");
            let index = $('.local-value').index(this);
            let value = $(this).val();
            $(this).val(value.replace(',', '.'));
            value = value.replace(',', '.');

            if (isNaN(+value)) {
                $(this).val(data.local[index].minVal);
            }

            if (parseFloat($(this).val()) > data.local[index].maxVal) {
                $(this).val(data.local[index].maxVal);
            }
            if (parseFloat($(this).val()) < data.local[index].minVal) {
                $(this).val(data.local[index].minVal);
            }

            calcLocal($('.local-value'));
            calculatePrice(data, kfValue, area);
        });

        function calcLocal(dataLocal) {
            let local = 1;

            $.each(dataLocal, function (index, value) {
                let row = $(this).closest('tr');
                let marker = row.children().last().children();

                if (marker.attr('class').includes('fa-check')) {
                    let code = row.attr('data-code-local');
                    local = local.toFixed(2) * parseFloat($(this).val());
                    data.calculate.local[code].index = parseFloat($(this).val());
                }
            });

            local = parseFloat(local).toFixed(2);
            data.calculate.priceLocal = parseFloat(local);
        }
    }


    function calculatePrice(data, kf, area) {
        $('#price-by-meter-title').html('Всього за 1 м<sup>2</sup> (' + data.calculate.priceZone + ' * ' + data.calculate.priceLocal + ' * ' + kf + ')');

        let priceByMeter = (parseFloat(data.calculate.priceZone) * parseFloat(data.calculate.priceLocal) * parseFloat(kf)).toFixed(2);

        let priceTotal = (parseFloat(priceByMeter) * parseFloat(data.calculate.area).toFixed(0)).toFixed(2);

        let priceTotalWithIndex = (parseFloat(priceTotal) * parseFloat(data.calculate.indexes.possible)).toFixed(2);

        $('#price-by-meter').html('<strong>' + priceByMeter + ' грн.</strong>');
        $('#price-total-title').html('Всього за ділянку (' + priceByMeter + ' * ' + area + ' га)');
        $('#price-total').html('<strong>' + priceTotal + ' грн.</strong>');
        $('#price-total-index').html('<strong>' + priceTotalWithIndex + ' грн.</strong>');
        $('.local-total').html(data.calculate.priceLocal);
        let multString = 'Узагальнюючий локальний коефіцієнт ' + getMultLocalAsString(data.calculate.local);
        $('.local-total-text').html(multString);
    }

    function getMultLocalAsString(data) {
        let str = '(';
        if (!$.isEmptyObject(data)) {
            $.each(data, function (index, value) {
                str += value.index + ' * ';
            });
            str = str.slice(0, -3) + ')';
        }

        return str;
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