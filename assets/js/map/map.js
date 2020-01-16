$(document).ready(function () {
    window.mymap = L.map('mapid').setView([48.5, 31], 6);
    let mapbox = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox/streets-v11',
        accessToken: 'pk.eyJ1IjoidmV0YWwzMyIsImEiOiJjazU2bm9nYmQwNWhtM29wZXM4aW80bzdqIn0.NjzzExdElo0C7JhER04PSQ'
    }).addTo(mymap);

    let kadastr = L.tileLayer.wms("https://map.land.gov.ua/geowebcache/service/wms", {
        layers: 'kadastr',
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        attribution: "Weather data © 2012 IEM Nexrad"
    }).addTo(mymap);

    let bing_key = 'Ap2Aa1SZydkxBTmchpgIYaIXw-OgT9TxU-UY-bOhsDUJO2WTicJyytnoUjjsWOnr';

    let bing = L.tileLayer.bing(bing_key).addTo(mymap);

    let baseLayersMap = {
        "bing": bing,
        "mapBox": mapbox,
    };
    let overlayMap = {
        "kadastr": kadastr,
    };


    window.layersControl = L.control.layers(baseLayersMap).addTo(mymap);
    layersControl.addOverlay(kadastr, 'kadastr2');

});
