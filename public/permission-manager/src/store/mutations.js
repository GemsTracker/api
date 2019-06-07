const API_BASE_URL_UPDATES = (state, baseUrl) => {
    state.apiBaseUrl = baseUrl;
};
const BASE_URL_UPDATES = (state, baseUrl) => {
    state.baseUrl = baseUrl;
};


export default {
    API_BASE_URL_UPDATES,
    BASE_URL_UPDATES,
};