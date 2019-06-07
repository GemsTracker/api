export default {
    setApiBaseUrl({commit}, baseUrl) {
        commit('API_BASE_URL_UPDATES', baseUrl);
    },
    setBaseUrl({commit}, baseUrl) {
        commit('BASE_URL_UPDATES', baseUrl);
    },
};