$(document).ready(function () {

    window.removeLayersGlob = function (name) {
        mymap.eachLayer(function (layer) {
            if (typeof layer.nameLayer !== "undefined" && layer.nameLayer === name) {
                mymap.removeLayer(layer);
            }
        });
    };

    window.setParcelValueInTable = function (layer = '') {
        const $area = $('#feature-card-area');
        const $cadNub = $('#feature-card-cud-num');
        const $purpose = $('#feature-purpose');

        if (layer) {
            $($area).html(layer.feature.properties.area);
            $($cadNub).html(layer.feature.properties.cadnum);
            $($purpose).html(layer.feature.properties.purpose);
        } else {
            $($area).html('');
            $($cadNub).html('');
            $($purpose).html('');
        }
    };

    window.errorsHandler = function (errors, timeout = 5000) {
        if (errors.length) {
            toastr.options = {"closeButton": true,};
            toastr.error(errors[0], 'Вибачте виникла помилка!', {timeOut: timeout});
        }
    };

    window.setDataToParcelTable = function (data, bounds) {
        $('#geom-from-json').val(data.wkt);
        $('#geom-from-json').attr("data-bounds", bounds);

        let area = (Math.round(data.area) / 10000).toFixed(4);
        let areaStr = area + ' га';

        $('#feature-card-area').html(areaStr);

        if (typeof data.pub !== 'undefined') {
            $('#feature-card-cud-num').html(data.pub[0].cadnum);
            $('#feature-purpose').html(data.pub[0].purpose);
        } else {
            $('#feature-card-cud-num').html('не визначено');
        }

        if ($('#shp-card').attr('data-name') !== '') {
            $('#calculate-parcel').removeClass('disabled');
        }
        $('#feature-card').removeClass('d-none');
        $('#save-parcel').removeClass('disabled');
    };

    window.sumPointsInGroup = function (arrayGroup) {
        let sum = 0;
        arrayGroup.forEach(function (item) {
            if (item.points) sum += parseInt(item.points);
        });

        return sum;
    };

    window.hideTooltip = function () {
        setTimeout(function () {
            $('[data-toggle="tooltip"]').tooltip('hide');
        }, 2000);
    };

});