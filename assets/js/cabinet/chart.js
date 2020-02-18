$(document).ready(function () {
    const chartArea = $('#chart-area');

    let data = {
        labels: ['Red', 'Yellow', 'Blue', 'Green'],
        borderWidth: 1,
        datasets: [{
            data: [10, 16, 24, 20],
            borderColor: [
                'rgb(255,36,20, 0.7)',
                'rgb(242,255,15, 0.7)',
                'rgb(28,102,255, 0.7)',
                'rgba(26,255,30,0.7)',

            ],
        }],
    };

    let areaChart = new Chart(chartArea, {
        type: 'line',
        data: data,
    });
});