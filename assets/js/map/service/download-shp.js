$(document).ready(function () {
    const btnDownloadShp = $('#btn-download-shp');
    const overlayControl = $('#buttons-card .overlay');

    $(btnDownloadShp).click(function () {
        $.ajax({
            url: Routing.generate('downloadShp'),
            type: 'post',
            data: {'name': $(btnDownloadShp).attr('href')},
            beforeSend: function () {
                overlayControl[0].hidden = false;
            },
            success: function (response) {
                overlayControl[0].hidden = true;
                window.location = '/load';
            },
            error: function (jqXHR) {
                overlayControl[0].hidden = true;
                servicesThrowErrors(jqXHR);
            },
        });
    });
});