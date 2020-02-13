$(document).ready(function () {
    const overlay = $('#feature-card .overlay');
    /*   const xmlCard = $('#xml-card');*/

    /*    overlay[0].hidden = true;*/

    $('body').on('click', '#feature-from-json', function (e) {
        e.preventDefault();
        let nameFile = $('#shp-card').attr('data-name');
        let feature = $('#geom-from-json').val();
        console.log(feature);
        if (nameFile.trim() !== '' && feature.trim() !== '') {
            checkFile(nameFile, feature);
        }
    });

    /*    $('#feature-from-json').on('click', function (e) {

        });*/

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
                console.log(data);
                if (dataJson.errors) {
                    console.log(dataJson.errors);
                    //$('#errors-card').remove();
                    //createErrorsCard(dataJson.validate_errors);
                    //xmlCard.addClass('card-outline card-danger');
                    toastr.options = {"closeButton": true,};
                    toastr.error(dataJson.errors);
                } else {
                    createNormativeTable(JSON.parse(data))
                    toastr.options = {"closeButton": true,};
                    toastr.success('Нормативна грошова оцінка успішно порахована!');
                    //xmlCard.addClass('card-outline card-success');
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
        console.log(data);
        let area = (Math.round(data.area)/10000).toFixed(4) + ' га';

        $('#feature-card-area').html(area);
        let basePrice = 115.25 + '&nbsp;' + 'грн.';
        let normativeTable = $('<div id="calculate" class="p-2"><h6 class="text-truncate ml-3 mt-4">Розрахунок</h6>' +
            '<table class="table table-sm pl-2 pr-2 bc-gray">' +
            '<tbody id="normativeTable">' +
            '<tr><td class="pl-3">Базова вартість</td><td class="text-center">' + basePrice + '</td><td class="text-center"></td></tr>' +
            '<tr><td class="pl-3"><span class="text-bold test-success">' + data.zone.name + '</span> економіко-планувальна зона</td><td class="text-center">' +
            data.zone.km2 + '</td><td class="text-center"></td></tr>' +
            '</tbody></table></div>');
        $('#feature-card .card-body').append(normativeTable);
        $.each(data.local, function (index, value) {
            console.log(value);
            console.log(value.area);

           /* let dataLoc = JSON.parse(value)
            console.log(dataLoc);*/
            let percent = Math.round((value.area/data.area)*100).toFixed() + ' %';
            let row = '<tr><td class="pl-3 text-primary"><small>' + value.name + '</small></td><td class="text-center pr-1"><small>' + percent + '</small></td></tr>';
            let str = normativeTable.find('#normativeTable').append(row);
        });
    }


});