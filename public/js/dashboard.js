document.addEventListener('DOMContentLoaded', () => {

    const selectElement = document.getElementById('confidi');
    if (selectElement) {
        selectElement.addEventListener('change', function(event) {
            document.getElementById("new-ConfidiSelect-form").submit();
        });
    }

    let data;
    let options;
    let responsiveOptions;

    let nodeTitle;
    let textTitle;

    const charts = document.querySelectorAll('.dashboard-chart');

    charts.forEach(function(chart) {
        data = {
            labels: JSON.parse(chart.dataset.chartLabels),
            series: JSON.parse(chart.dataset.chartSeries)
        };

        options = {
            labelInterpolationFnc: function (value) {
                return value[0]
            }
        };

        responsiveOptions = [
            ['screen and (min-width: 640px)', {
                labelOffset: 0,
                chartPadding: 30,
                labelDirection: 'explode',
                labelInterpolationFnc: function (value) {
                    return value;
                }
            }],
            ['screen and (min-width: 1024px)', {
                labelOffset: 0,
                chartPadding: 60,
                labelDirection: 'explode',
                labelInterpolationFnc: function (value) {
                    return value;
                }
            }]
        ];
        new Chartist.Pie("#"+chart.id, data, options, responsiveOptions);
        nodeTitle = document.createElement("H1");
        textTitle = document.createTextNode(chart.dataset.chartTitle);
        nodeTitle.appendChild(textTitle);

        chart.parentElement.appendChild(nodeTitle);
    });
});







