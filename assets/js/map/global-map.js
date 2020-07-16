import '../../css/map/map.css';

window.addBaseLayars = require('../map/add-base-layers');
window.servicesThrowErrors = require('../map/service/service_errors');

window.addParcelsToMap = require('../map/add-parcels');
window.addZonyToMap = require('../map/add-zony');
window.addLocalToMap = require('../map/add-local');
window.addLandsToMap = require('../map/add-lands');
window.addRegionsToMap = require('../map/add-regions');
window.addFeatureToMap = require('../map/service/add-feature-to-map');
window.addParcelsToTable = require('../map/service/add-parsels-to-table');

import 'bootstrap';

global.turf = require('@turf/turf');

import '../map/style';
import '../map/services';
import '../map/global-functions';
import 'leaflet';
import '../map/leaflet-bing-layer';
import '../map/map-controls';
import '../map/service/check-xml';
import '../map/service/import-json';
import '../map/service/import-xml';
import '../map/service/calculate-normative';
import '../map/service/save-parcel';
import '../map/service/download-shp';
import '../../../node_modules/select2/dist/js/select2';


$('[data-toggle="tooltip"]').tooltip({
    placement: 'bottom',
    trigger: 'hover',
});

