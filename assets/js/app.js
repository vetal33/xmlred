const $ = require('jquery');

import '../css/app.css';
import 'admin-lte';


global.toastr = require('toastr');
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
Routing.setRoutingData(routes);
global.Routing = Routing;



