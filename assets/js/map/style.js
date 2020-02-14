$(document).ready(function () {

    /**
     * Стиль для межі населеного пункту
     *
     * @type {{fillColor: string, color: string, fillOpacity: number, weight: number, opacity: number}}
     */
    window.boundaryStyle = {
        "color": '#ff735b',
        "weight": 7,
        "opacity": 1,
        "fillOpacity": 0.05,
        "fillColor": '#5bff10',
    };

    /**
     * Стиль для ділянки грунту під час виділення
     *
     * @type {{color: string, weight: number, opacity: number}}
     */

    window.selectlandsStyle = {
        "color": '#ffffff',
        "weight": 1,
        "opacity": 1,
    };

    /**
     * Стиль для імпортованої ділянки з json
     *
     * @type {{fillColor: string, color: string, fillOpacity: number, weight: number, opacity: number}}
     */
    window.addFeatureFromJsonStyle = {
        "color": '#290a30',
        "weight": 1,
        "opacity": 1,
        "fillOpacity": 0.4,
        "fillColor": '#31ffc0',
    };

    /**
     * Стиль для ділянки перетину з локальним фактором
     *
     * @type {{fillColor: string, color: string, fillOpacity: number, weight: number, opacity: number}}
     */
    window.intersectLocalsStyle = {
        "color": '#301005',
        "weight": 1,
        "opacity": 0,
        "fillOpacity": 0,
        "fillColor": '#8dff14',
    };

    /**
     * Стиль для ділянки перетину з локальним фактором під час наведення
     *
     * @type {{fillColor: string, color: string, fillOpacity: number, weight: number, opacity: number}}
     */
    window.intersectLocalsSelectedStyle = {
        "color": '#290a30',
        "weight": 1,
        "opacity": 1,
        "fillOpacity": 0.9,
        "fillColor": '#ff8e09',
    };
});
