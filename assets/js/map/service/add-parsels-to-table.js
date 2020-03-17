/**
 * Формуємо  і додаємо таблицю до карти
 *
 * @param data
 * @returns {string}
 */
module.exports = function (data) {
    let $table = $('#parcels-list');
    let count = $('#parcels-count');

    $($table).find('tbody').empty();
    $(count).html(data.length);

    if (data.length) {
        $.each(data, function (index, parcel) {
            let htmlTr = '<tr data-cadNum="' + parcel.cadNum + '" ><td>' + parcel.cadNum + '</td><td>' + parcel.area + '</td><td>' +
                '<a href="#" class="btn btn-tool btn-sm d-inline table-zoom" data-bounds="' + parcel.extent + '">' +
                '<i class="fas fa-search"></i></a>' +
                '<a href="#" class="btn btn-tool btn-sm d-inline table-delete" data-target="#modal-sm" data-toggle="modal">' +
                '<i class="fas fa-trash"></i></a></td></td></tr>';
            $($table).find('tbody').append(htmlTr);
        });
    }
};