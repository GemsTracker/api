export default {

    /* 
     * Example of a single getter or one with a parameter
    tracks(state)
    {
        return state.tracks;
    },
    track(state) 
    {
        return (trackId) => {
            return state.tracks[trackId];    
        }
    },*/

    all(state)
    {
        return state.data;
    },
    one(state)
    {
        return (variableId) => {
            return state.data[variableId];
        }
    },
    loading(state)
    {
        return state.loading;
    }
}