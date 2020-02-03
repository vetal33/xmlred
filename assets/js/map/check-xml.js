$(document).ready(function () {
    const overlay = $('#buttons-card .overlay');
    const xmlCard = $('#xml-card');

    overlay[0].hidden = true;

    $('#btn-validate-xml').on('click', function () {
        let nameFile = $('#shp-card').attr('data-name');
        if (nameFile.trim() !== '') {
            checkFile(nameFile);
        }
    });

    function checkFile(fileName) {
        $.ajax({
            url: Routing.generate('verifyXml'),
            method: 'POST',
            data: {"fileName": fileName},
            dataType: 'json',
            beforeSend: function () {
                overlay[0].hidden = false;
            },
            success: function (data) {
                overlay[0].hidden = true;
                let dataJson = JSON.parse(data);
                if (dataJson.validate_errors) {
                    $('#errors-card').remove();
                    createErrorsCard(dataJson.validate_errors);
                    xmlCard.addClass('card-outline card-danger');
                }else{
                    toastr.options = {"closeButton": true, };
                    toastr.success('В цьому XML-файлі відсутні помилки структури!');
                    xmlCard.addClass('card-outline card-success');
                }
            },
            error: function (jqXHR) {
                overlay[0].hidden = true;
                createErrorBox(jqXHR);

            },
        })
    }

    function createErrorBox(errorObj) {
        if (errorObj.status === 404) {
            toastr.error(errorObj.responseJSON, 'Вибачте виникла помилка!',{timeOut: 15000});
        }else if (errorObj.status === 403) {
            toastr.info(errorObj.responseJSON, 'Увага!',{timeOut: 20000});
        } else {
            toastr.error('Виникла помилка, вибачте за незручності!');
        }
    }


    /**
     * Створює таблицю з помилками
     *
     * @param errorsArray
     */

    function createErrorsCard(errorsArray) {
        let errorsCard;
        errorsCard = $('<div class="card card-outline card-danger" id="errors-card" >' +
                            '<div class="card-header border-0">' +
                                '<h3 class="card-title text-danger">Список помилок</h3>' +
                                '<div class="card-tools">' +
                                    '<span data-toggle="tooltip" title="3 New Messages" class="badge badge-danger">' + errorsArray.length + '</span>' +
                                    '<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="card-body p-0">' +
                                '<table class="table table-sm">' +
                                    '<thead><tr><th>Рядок</th><th class="text-center">Опис помилки</th></tr></thead>' +
                                    '<tbody id="errorsTable"></tbody>' +
                                '</table>' +
                            '</div>' +
                            '<div class="overlay v-hidden" hidden="">' +
                                '<i class="fas fa-2x fa-sync-alt fa-spin"></i>' +
                            '</div>' +
                        '</div>');

        $('#xml-card').before(errorsCard);
        $.each(errorsArray, function (index, value) {
            let row = '<tr><td>' + value.line + '</td><td class="text-justify">' + value.message + '</td></tr>';
            let str = errorsCard.find('#errorsTable').append(row);
        });
    }

});