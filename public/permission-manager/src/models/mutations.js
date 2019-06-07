import Vue from 'vue';

/* 
 * Example of a mutator
const TRACKS_UPDATED = (state, tracks) => {
    state.tracks = tracks;
};*/

const LOADING = (state, loading) => {
    Vue.set(state, 'loading', loading);
};

const STRUCTURE_DATA = (state, data) => {
    Vue.set(state, 'structure', data);
}

const SUCCESS = (state, data) => {
    //set(state, 'loading', false);
    Vue.set(state, 'data', Object.assign(state.data, data));
    //state.data = Object.assign(state.data, data);
    //console.log(state.data);
}

const UPDATE_VALIDATION_ERRORS = (state, validationErrors) => {
    Vue.set (state, 'validationErrors', validationErrors);
}

export default {
    LOADING,
    

    STRUCTURE_DATA,
    SUCCESS,
    UPDATE_VALIDATION_ERRORS,
};