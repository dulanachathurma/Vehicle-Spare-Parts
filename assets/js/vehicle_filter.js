/**
 * assets/js/vehicle_filter.js - dynamic cascading dropdowns for Vehicle Compatibility
 * Handles cascading for Make -> Model -> Chassis Code across Homepage Hero and Search Sidebar.
 */

(function () {
    'use strict';

    var vehicleTreeCache = null;
    var vehicleRegionsCache = null;

    function initVehicleWidgets() {
        var widgets = document.querySelectorAll('[data-vehicle-widget]');
        if (!widgets.length) {
            return;
        }

        fetchVehicleTree(function (tree, regions) {
            widgets.forEach(function (widget) {
                setupWidget(widget, tree, regions);
            });
        });
    }

    function fetchVehicleTree(callback) {
        if (vehicleTreeCache) {
            callback(vehicleTreeCache, vehicleRegionsCache);
            return;
        }

        var baseUrl = document.querySelector('base') ? document.querySelector('base').href : '';
        var apiUrl = '/catalogue/ajax_vehicles.php?action=all';
        
        // Handle sub-directory if form or dataset provides base-url
        var form = document.querySelector('#catFilterForm') || document.querySelector('.home-hero-vehicle-form');
        if (form && form.getAttribute('data-base-url')) {
            apiUrl = form.getAttribute('data-base-url') + apiUrl;
        }

        fetch(apiUrl)
            .then(function (res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function (json) {
                if (json && json.success && json.data) {
                    if (json.data.tree) {
                        vehicleTreeCache = json.data.tree;
                        vehicleRegionsCache = json.data.regions || null;
                    } else {
                        vehicleTreeCache = json.data;
                        vehicleRegionsCache = null;
                    }
                    callback(vehicleTreeCache, vehicleRegionsCache);
                }
            })
            .catch(function (err) {
                console.error('Failed to load vehicle models:', err);
            });
    }

    function setupWidget(widget, tree, regions) {
        var makeSelect = widget.querySelector('[name="make"]');
        var modelSelect = widget.querySelector('[name="model"]');
        var chassisSelect = widget.querySelector('[name="chassis"]');

        if (!makeSelect || !modelSelect || !chassisSelect) {
            return;
        }

        var initialMake = makeSelect.getAttribute('data-selected') || makeSelect.value || '';
        var initialModel = modelSelect.getAttribute('data-selected') || modelSelect.value || '';
        var initialChassis = chassisSelect.getAttribute('data-selected') || chassisSelect.value || '';

        // Populate makes if empty
        if (makeSelect.options.length <= 1) {
            populateMakes(makeSelect, tree, initialMake, regions);
        }

        // Initialize models and chassis if initial values present
        if (initialMake && tree[initialMake]) {
            populateModels(modelSelect, tree[initialMake], initialModel);
            if (initialModel && tree[initialMake][initialModel]) {
                populateChassis(chassisSelect, tree[initialMake][initialModel], initialChassis);
            } else {
                resetSelect(chassisSelect, 'Any Chassis Code', true);
            }
        } else {
            resetSelect(modelSelect, 'Any Model', true);
            resetSelect(chassisSelect, 'Any Chassis Code', true);
        }

        // Event listener: Make changed
        makeSelect.addEventListener('change', function () {
            var selectedMake = this.value;
            resetSelect(modelSelect, 'Any Model', false);
            resetSelect(chassisSelect, 'Any Chassis Code', true);

            if (selectedMake && tree[selectedMake]) {
                populateModels(modelSelect, tree[selectedMake], '');
                modelSelect.disabled = false;
            } else {
                modelSelect.disabled = true;
            }
        });

        // Event listener: Model changed
        modelSelect.addEventListener('change', function () {
            var selectedMake = makeSelect.value;
            var selectedModel = this.value;
            resetSelect(chassisSelect, 'Any Chassis Code', false);

            if (selectedMake && selectedModel && tree[selectedMake] && tree[selectedMake][selectedModel]) {
                populateChassis(chassisSelect, tree[selectedMake][selectedModel], '');
                chassisSelect.disabled = false;
            } else {
                chassisSelect.disabled = true;
            }
        });
    }

    function populateMakes(select, tree, selectedVal, regions) {
        select.innerHTML = '<option value="">Any Make</option>';
        if (regions && typeof regions === 'object' && Object.keys(regions).length > 0) {
            Object.keys(regions).forEach(function (regionName) {
                var optgroup = document.createElement('optgroup');
                optgroup.label = regionName;
                var makes = regions[regionName] || [];
                makes.forEach(function (make) {
                    var opt = document.createElement('option');
                    opt.value = make;
                    opt.textContent = make;
                    if (make === selectedVal) {
                        opt.selected = true;
                    }
                    optgroup.appendChild(opt);
                });
                select.appendChild(optgroup);
            });
        } else {
            Object.keys(tree).sort().forEach(function (make) {
                var opt = document.createElement('option');
                opt.value = make;
                opt.textContent = make;
                if (make === selectedVal) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }
    }

    function populateModels(select, modelsObj, selectedVal) {
        select.innerHTML = '<option value="">Any Model</option>';
        Object.keys(modelsObj).sort().forEach(function (model) {
            var opt = document.createElement('option');
            opt.value = model;
            opt.textContent = model;
            if (model === selectedVal) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    function populateChassis(select, chassisList, selectedVal) {
        select.innerHTML = '<option value="">Any Chassis Code</option>';
        chassisList.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.chassisCode;
            var label = item.chassisCode;
            if (item.yearRange) {
                label += ' (' + item.yearRange + ')';
            }
            opt.textContent = label;
            if (item.chassisCode === selectedVal) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    function resetSelect(select, defaultLabel, disable) {
        select.innerHTML = '<option value="">' + defaultLabel + '</option>';
        select.disabled = !!disable;
    }

    document.addEventListener('DOMContentLoaded', initVehicleWidgets);
})();
