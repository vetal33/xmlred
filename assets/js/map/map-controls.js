$(document).ready(function () {

    /**
     * Створюємо кнопку Full extent
     */
    let fullzoomButton = L.Control.extend({
        options: {
            position: 'topleft',
        },
        onAdd: function (map) {
            let container = L.DomUtil.create('a', 'btn btn-default');
            container.innerHTML += '<i class="fas fa-crosshairs"></i>';
            container.type = "button";
            container.title = "Full extent";
            container.onclick = function () {
                mymap.eachLayer(function (layer) {
                    if (layer.nameLayer && layer.nameLayer === "mejaGeoJSON") {
                        mymap.fitBounds(layer.getBounds());
                    }
                });
            };
            return container;
        }
    });

    /**
     * Створюємо кнопку Clear
     */
    let clearButton = L.Control.extend({
        options: {
            position: 'topleft',
        },
        onAdd: function (map) {
            let container = L.DomUtil.create('a', 'btn btn-default');
            container.innerHTML += '<i class="fas fa-eraser"></i>';
            container.type = "button";
            container.title = "Clear selected";
            container.onclick = function () {
                mymap.eachLayer(function (layer) {
                    if (typeof layer.nameLayer !== "undefined" && layer.nameLayer === "Selected") {
                        mymap.removeLayer(layer);
                    }
                });
            };
            return container;
        }
    });

    mymap.addControl(new fullzoomButton());
    mymap.addControl(new clearButton());

    $(".check-map").change(function () {
        let layerClicked = $(this).attr("id");
        switch (layerClicked) {
            case "zony":
                toggleLayer(zonyLayersGroup);
                break;
            case "local":
                toggleLayer(localLayersGroup);
                break;
            case "lands":
                toggleLayer(landsLayersGroup);
                break;
        }
    });

    /**
     * Перемикаю групу шарів, використовуючи checkbox в таблиці
     *
     * @param layersGroupName
     */
    function toggleLayer(layersGroupName) {
        if (mymap.hasLayer(layersGroupName)) {
            mymap.removeLayer(layersGroupName);
        } else {
            mymap.addLayer(layersGroupName);
        }
    }
});