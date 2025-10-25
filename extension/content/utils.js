// Shared utility functions for content scripts

// Get frameworks model value used on the page
function getFrameworkModels(input) {
    const wireModel =
        input.getAttribute("wire:model") ||
        input.getAttribute("wire:model.defer");
    if (wireModel) return wireModel;

    const vueModel =
        input.getAttribute("v-model") || input.getAttribute("v-model.lazy");
    if (vueModel) return vueModel;

    const angularModel = input.getAttribute("ng-model");
    if (angularModel) return angularModel;

    const svelteModel = input.getAttribute("bind:value");
    if (svelteModel) return svelteModel;

    const alpineModel = input.getAttribute("x-model");
    if (alpineModel) return alpineModel;

    const knockoutModel = input.getAttribute("data-bind");
    if (knockoutModel) return knockoutModel;

    return null;
}

// Get list of model attributes for different frameworks
function getModelAttributes() {
    return [
        "wire:model",
        "wire:model.defer",
        "v-model",
        "v-model.lazy",
        "ng-model",
        "bind:value",
        "x-model",
        "data-bind",
    ];
}
