module.exports = function (errorObj) {
    toastr.options = {"closeButton": true,};
    if (errorObj.status === 404) {
        toastr.error(errorObj.responseJSON, 'Вибачте виникла помилка!', {timeOut: 25000});
    } else if (errorObj.status === 403) {
        toastr.info(errorObj.responseJSON, 'Увага!', {timeOut: 20000});
    } else {
        toastr.error('Виникла помилка, вибачте за незручності!');
    }

    return true;
};