module.exports = function () {

    /** Створюєм карту MapBox  */
    let mapbox = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
        maxZoom: 19,
        id: 'mapbox/streets-v11',
        accessToken: 'pk.eyJ1IjoidmV0YWwzMyIsImEiOiJjazU2bm9nYmQwNWhtM29wZXM4aW80bzdqIn0.NjzzExdElo0C7JhER04PSQ'
    });

    mapbox.addTo(mymap);

    let Esri_WorldStreetMap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
    });

    Esri_WorldStreetMap.addTo(mymap);

    let osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });

    osm.addTo(mymap);


    let bing_key = 'Ap2Aa1SZydkxBTmchpgIYaIXw-OgT9TxU-UY-bOhsDUJO2WTicJyytnoUjjsWOnr';
    let bing = L.tileLayer.bing(bing_key).addTo(mymap);

    /** Створюєм Публічнку кадастрову карту Укарїни   */
    let kadastr = L.tileLayer.wms("https://map.land.gov.ua/geowebcache/service/wms", {
        layers: 'kadastr',
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        maxNativeZoom: 16,
        maxZoom: 18,
     });


    /**
     * Створюємо набор базових шарів для відображення(можна вибрать лише один)
     * @type {{mapBox: *, bing: *}}
     */
    let baseLayersMap = {
        "bing": bing,
        "esri": Esri_WorldStreetMap,
        "osm": osm,
    };

    /**
     * Створюєм набор додткових шарів для відображення(можна влючить-виключить кожен)
     * @type {{kadastr: *}}
     */
    let overlayMap = {
        "kadastr": kadastr,
    };

    /**  Додаємо базові шари на карту   */
    window.layersControl = L.control.layers(baseLayersMap).addTo(mymap);
    layersControl.addOverlay(kadastr, 'Кадастрова карта');

    return true;
};