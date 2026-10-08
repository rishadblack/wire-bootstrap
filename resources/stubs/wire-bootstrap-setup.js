// Managed by `php artisan wire-bootstrap:install` — re-running the command
// regenerates this file. Put your own code in app.js instead.
import * as bootstrap from 'bootstrap';

// Bootstrap's ESM build does not expose itself globally; the wire-* packages
// and data-bs-* attributes need window.bootstrap.
window.bootstrap = bootstrap;

import flatpickr from 'flatpickr';

window.flatpickr = flatpickr;

import Quill from 'quill';

window.Quill = Quill;

import '../../vendor/rishadblack/wire-bootstrap/resources/js/wire-bootstrap.js';
__WB_SIBLING_IMPORTS__
