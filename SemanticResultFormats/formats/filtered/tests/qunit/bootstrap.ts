/// <reference types="qunit" />

import { ViewSelectorTest } from "./Filtered/ViewSelectorTest";
import { ControllerTest } from "./Filtered/ControllerTest";
import { DistanceFilterTest } from "./Filtered/Filter/DistanceFilterTest";
import { ValueFilterTest } from "./Filtered/Filter/ValueFilterTest";
import { QUnitTestHandler } from "./Util/QUnitTestHandler";
import { ViewTest } from "./Filtered/View/ViewTest";
import { MapViewTest } from "./Filtered/View/MapViewTest";

let testclasses = [
	ViewSelectorTest,
	ControllerTest,
	DistanceFilterTest,
	ValueFilterTest,
	ViewTest,
	MapViewTest,
];
let testhandler = new QUnitTestHandler('ext.srf.formats.filtered', testclasses);

testhandler.runTests();
