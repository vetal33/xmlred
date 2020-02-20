import '../../css/map/map.css';

window.addBaseLayars = require('../map/add-base-layers');
window.servicesThrowErrors = require('../map/service_errors');
window.addZonyToMap = require('../map/add-zony');
window.addLocalToMap = require('../map/add-local');
window.addLandsToMap = require('../map/add-lands');
window.addRegionsToMap = require('../map/add-regions');

import 'bootstrap';
import '../map/style'
import '../map/services';
import 'leaflet';
import '../map/leaflet-bing-layer';
import '../map/map-controls';
import '../map/check-xml';
import '../map/import-json';
import '../map/calculate-normative';


$('[data-toggle="tooltip"]').tooltip({
    placement: 'bottom',
    trigger: 'hover',
});